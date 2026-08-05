<?php
// adquisiciones_controller.php - Lógica de Negocio (V5.3 - Intercepción ID Portal)
require_once __DIR__ . '/config.php';

// 1. SEGURIDAD
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$rol = $_SESSION['user_rol'] ?? '';
if ($rol !== 'ADQUISICIONES' && $rol !== 'ADMIN_MUNICIPAL' && $rol !== 'SYSADMIN') die("Acceso Denegado.");

$user_id = $_SESSION['user_id'];
$mensaje = ''; $tipo_mensaje = '';
$vista = $_GET['view'] ?? 'lista';

// ==============================================================================
// MANEJO DE ACCIONES (POST)
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        $exp_id = isset($_POST['expediente_id']) ? (int)$_POST['expediente_id'] : null;
        $accion = $_POST['accion'] ?? ''; 
        
        if ($exp_id && $accion !== 'crear_proveedor') {
            $stmtEst = $pdo->prepare("SELECT et.rol_responsable FROM expedientes e JOIN estados_tramite et ON e.estado_actual = et.codigo WHERE e.id = ?");
            $stmtEst->execute([$exp_id]);
            $rol_resp = $stmtEst->fetchColumn();
            if ($rol_resp !== 'ADQUISICIONES') {
                throw new Exception("El expediente ya fue procesado y no se encuentra disponible para gestión en Adquisiciones.");
            }
        }
        
        $anio = date('Y');
        $dir = __DIR__ . "/uploads/$anio/exp_$exp_id/";
        if ($exp_id && !file_exists($dir)) mkdir($dir, 0777, true);

        // A. CREAR PROVEEDOR
        if ($accion === 'crear_proveedor') {
            $rut = trim($_POST['rut']);
            $razon = trim($_POST['razon_social']);
            $stmtChk = $pdo->prepare("SELECT id FROM proveedores WHERE rut = ?");
            $stmtChk->execute([$rut]);
            if ($stmtChk->fetch()) throw new Exception("El proveedor ya existe.");

            $pdo->prepare("INSERT INTO proveedores (rut, razon_social, giro, direccion, telefono) VALUES (?, ?, ?, ?, ?)")
                ->execute([$rut, $razon, $_POST['giro'], $_POST['direccion'], $_POST['telefono']]);
            $mensaje = "Proveedor registrado exitosamente."; $tipo_mensaje = "success";
            if (isset($_POST['return_id'])) { $vista = 'gestionar'; $_GET['id'] = $_POST['return_id']; }
        }

        // B. PASO 1: INGRESAR ID DEL PORTAL ANTES DE COTIZAR
        if ($accion === 'ingresar_id_portal') {
            $id_compra_agil = trim($_POST['id_compra_agil'] ?? '');
            $id_licitacion = trim($_POST['id_licitacion'] ?? '');
            $id_referencia = trim($_POST['id_referencia'] ?? '');
            
            $comentario = "Requerimiento recepcionado e ingresado a portal.";
            
            if ($id_compra_agil) {
                $pdo->prepare("UPDATE expedientes SET id_compra_agil = ? WHERE id = ?")->execute([$id_compra_agil, $exp_id]);
                $comentario .= " ID Compra Ágil: " . $id_compra_agil;
            } elseif ($id_licitacion) {
                $pdo->prepare("UPDATE expedientes SET id_licitacion = ? WHERE id = ?")->execute([$id_licitacion, $exp_id]);
                $comentario .= " ID Licitación: " . $id_licitacion;
            } elseif ($id_referencia) {
                // Guarda en uno u otro como genérico si es necesario, o solo lo loguea
                $comentario .= " ID Referencia: " . $id_referencia;
            }

            $nuevo_destino = avanzar_flujo($pdo, $exp_id, $user_id, $comentario);
            $stmtNd = $pdo->prepare("SELECT nombre FROM estados_tramite WHERE codigo = ?");
            $stmtNd->execute([$nuevo_destino]);
            $nombre_dest = $stmtNd->fetchColumn();

            $mensaje = "ID de portal guardado. El trámite avanzó a: $nombre_dest.";
            $tipo_mensaje = "success";
            $vista = 'lista';
        }

        // C. PASO 2: SUBIR COTIZACIONES / BASES Y AVANZAR
        if (in_array($accion, ['subir_cotizaciones', 'emitir_oc', 'publicar_licitacion', 'accion_generica'])) {
            
            // 1. Manejo de Archivos
            if (isset($_FILES['archivo_cotizacion']) && $_FILES['archivo_cotizacion']['error'] !== UPLOAD_ERR_NO_FILE) {
                $ext = validar_subida_archivo($_FILES['archivo_cotizacion'], null, ['pdf', 'zip', 'rar']);
                if ($ext) {
                    $name = "COTIZACIONES_" . time() . "." . $ext;
                    if(move_uploaded_file($_FILES['archivo_cotizacion']['tmp_name'], $dir . $name)){
                        $pdo->prepare("INSERT INTO expedientes_documentos (expediente_id, subido_por_id, tipo_doc, ruta_archivo, nombre_original) VALUES (?, ?, 'COTIZACION_RESPALDO', ?, ?)")
                            ->execute([$exp_id, $user_id, "uploads/$anio/exp_$exp_id/$name", $_FILES['archivo_cotizacion']['name']]);
                    }
                }
            }

            if (isset($_FILES['archivo_oc']) && $_FILES['archivo_oc']['error'] !== UPLOAD_ERR_NO_FILE) {
                $ext = validar_subida_archivo($_FILES['archivo_oc'], null, ['pdf']);
                if ($ext) {
                    $name = "ORDEN_COMPRA_" . time() . ".pdf";
                    if(move_uploaded_file($_FILES['archivo_oc']['tmp_name'], $dir . $name)){
                        $pdo->prepare("INSERT INTO expedientes_documentos (expediente_id, subido_por_id, tipo_doc, ruta_archivo, nombre_original) VALUES (?, ?, 'OTRO', ?, ?)")
                            ->execute([$exp_id, $user_id, "uploads/$anio/exp_$exp_id/$name", $_FILES['archivo_oc']['name']]);
                    }
                }
            }

            if (isset($_FILES['archivo_decreto']) && $_FILES['archivo_decreto']['error'] !== UPLOAD_ERR_NO_FILE) {
                $ext = validar_subida_archivo($_FILES['archivo_decreto'], null, ['pdf']);
                if ($ext) {
                    $name = "DECRETO_ALCALDICIO_" . time() . ".pdf";
                    if(move_uploaded_file($_FILES['archivo_decreto']['tmp_name'], $dir . $name)){
                        $pdo->prepare("INSERT INTO expedientes_documentos (expediente_id, subido_por_id, tipo_doc, ruta_archivo, nombre_original) VALUES (?, ?, 'DECRETO_ALCALDICIO', ?, ?)")
                            ->execute([$exp_id, $user_id, "uploads/$anio/exp_$exp_id/$name", $_FILES['archivo_decreto']['name']]);
                    }
                }
            }

            if (isset($_FILES['archivo_bases']) && $_FILES['archivo_bases']['error'] !== UPLOAD_ERR_NO_FILE) {
                $ext = validar_subida_archivo($_FILES['archivo_bases'], null, ['pdf', 'zip', 'rar', 'doc', 'docx']);
                if ($ext) {
                    $name = "BASES_LICITACION_" . time() . "." . $ext;
                    if(move_uploaded_file($_FILES['archivo_bases']['tmp_name'], $dir . $name)){
                        $pdo->prepare("INSERT INTO expedientes_documentos (expediente_id, subido_por_id, tipo_doc, ruta_archivo, nombre_original) VALUES (?, ?, 'OTRO', ?, ?)")
                            ->execute([$exp_id, $user_id, "uploads/$anio/exp_$exp_id/$name", $_FILES['archivo_bases']['name']]);
                    }
                }
            }

            // 2. Guardar datos de adjudicación final si vienen en el POST
            if (isset($_POST['proveedor_id']) && !empty($_POST['proveedor_id']) && isset($_POST['monto_definitivo'])) {
                $prov_id = $_POST['proveedor_id'];
                $monto_final = str_replace('.', '', $_POST['monto_definitivo']);
                $pdo->prepare("UPDATE expedientes SET proveedor_adjudicado_id = ?, monto_definitivo = ?, fecha_adjudicacion = NOW() WHERE id = ?")->execute([$prov_id, $monto_final, $exp_id]);
            }

            // 3. Guardar Número de Orden de Compra, Decreto Alcaldicio y Conv. Marco OC
            if ($accion === 'emitir_oc') {
                $oc_num = trim($_POST['orden_compra_numero'] ?? $_POST['conv_marco_oc'] ?? '');
                $dec_num = trim($_POST['decreto_alcaldicio_numero'] ?? '');
                $conv_oc = trim($_POST['conv_marco_oc'] ?? $oc_num);

                $pdo->prepare("UPDATE expedientes SET orden_compra_numero = ?, decreto_alcaldicio_numero = ?, conv_marco_oc = ? WHERE id = ?")
                    ->execute([$oc_num, $dec_num, $conv_oc, $exp_id]);
            }

            $comentario = $_POST['comentario_flujo'] ?? 'Gestión de Adquisiciones completada. Archivos subidos.';
            $nuevo_destino = avanzar_flujo($pdo, $exp_id, $user_id, $comentario);
            $stmtNd = $pdo->prepare("SELECT nombre FROM estados_tramite WHERE codigo = ?");
            $stmtNd->execute([$nuevo_destino]);
            $nombre_dest = $stmtNd->fetchColumn();

            $mensaje = "Gestión procesada. El trámite avanzó a: $nombre_dest.";
            $tipo_mensaje = "success";
            $vista = 'lista';
        }

       // D. ORDEN ACEPTADA
        if ($accion === 'oc_aceptada') {
            avanzar_flujo($pdo, $exp_id, $user_id, "El proveedor aceptó la Orden de Compra en el portal. Trámite finalizado.");
            $mensaje = "¡Proceso cerrado correctamente!"; $tipo_mensaje = "success"; $vista = 'lista';
        }

        // E. ORDEN RECHAZADA (Devuelve a evaluación)
        if ($accion === 'oc_rechazada') {
            $motivo_rechazo = trim($_POST['motivo_rechazo_proveedor']);
            if(empty($motivo_rechazo)) throw new Exception("Debe indicar el motivo del rechazo.");

            $stmtTc = $pdo->prepare("SELECT tc.codigo FROM expedientes e JOIN tipos_compra tc ON e.tipo_compra_id = tc.id WHERE e.id = ?");
            $stmtTc->execute([$exp_id]);
            $tc_cod = $stmtTc->fetchColumn();
            $nuevo_estado = in_array(strtoupper($tc_cod), ['AGIL', 'COMPRA_AGIL', 'LICITACION']) ? 'EN_EVALUACION_OFERTAS' : 'EN_CORRECCION'; 
            
            $pdo->prepare("UPDATE expedientes SET proveedor_adjudicado_id = NULL, monto_definitivo = NULL, estado_actual = ? WHERE id = ?")->execute([$nuevo_estado, $exp_id]);
            
            $pdo->prepare("INSERT INTO expedientes_historial (expediente_id, usuario_id, accion, estado_anterior, estado_nuevo, comentario) VALUES (?, ?, 'RECHAZO_PROVEEDOR', 'ESPERANDO_ACEPTACION_OC', ?, ?)")
                ->execute([$exp_id, $user_id, $nuevo_estado, "Proveedor rechazó OC. Motivo: $motivo_rechazo. Se devuelve a evaluación para readjudicar (OPI Vigente)."]);

            $mensaje = "Se devolvió para readjudicar con otro proveedor. La OPI sigue vigente."; $tipo_mensaje = "warning"; $vista = 'lista';
        }

        // F. ANULAR OPI DEFINITIVAMENTE
        if ($accion === 'anular_opi_definitiva') {
            $motivo = trim($_POST['motivo_anulacion']);
            if(empty($motivo)) throw new Exception("Debe indicar por qué se cae la compra definitivamente.");

            $stmtEst = $pdo->prepare("SELECT estado_actual FROM expedientes WHERE id = ?");
            $stmtEst->execute([$exp_id]);
            $estado_actual = $stmtEst->fetchColumn();
            
            $pdo->prepare("UPDATE expedientes SET estado_actual = 'ANULADO', observacion_cierre = ? WHERE id = ?")
                ->execute(["Compra Fracasada: " . $motivo, $exp_id]);
                
            $pdo->prepare("INSERT INTO expedientes_historial (expediente_id, usuario_id, accion, estado_anterior, estado_nuevo, comentario) VALUES (?, ?, 'ANULAR', ?, 'ANULADO', ?)")
                ->execute([$exp_id, $user_id, $estado_actual, "OPI ANULADA DEFINITIVAMENTE. Motivo: " . $motivo]);
            
            // Liberar presupuesto si aplica
            liberar_presupuesto_si_aplica($pdo, $exp_id, 'ANULADO');
            
            $mensaje = "El proceso de compra fue ANULADO definitivamente. La OPI fue quemada en el sistema para auditoría.";
            $tipo_mensaje = "error";
            $vista = 'lista';
        }
        
        // G. DEVOLVER ANTECEDENTES NORMAL
        if ($accion === 'devolver') {
            devolver_flujo($pdo, $exp_id, $user_id, $_POST['motivo']);
            $mensaje = "Solicitud devuelta para corrección."; $tipo_mensaje = "warning"; $vista = 'lista';
        }

        if ($pdo->inTransaction()) $pdo->commit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $mensaje = "Error: " . $e->getMessage(); $tipo_mensaje = "error";
    }
}

// ==============================================================================
// CONSULTAS GET (CARGA DE VISTAS HOMOLOGADAS)
// ==============================================================================

$f_q = trim($_GET['f_q'] ?? $_GET['q'] ?? '');
$f_tipo = trim($_GET['f_tipo'] ?? '');
$f_estado = trim($_GET['f_estado'] ?? '');
$f_desde = trim($_GET['f_desde'] ?? '');
$f_hasta = trim($_GET['f_hasta'] ?? '');

// CONTADORES PARA PESTAÑAS
$count_pendientes = $pdo->query("SELECT COUNT(*) FROM expedientes e JOIN estados_tramite et ON e.estado_actual = et.codigo WHERE et.rol_responsable = 'ADQUISICIONES'")->fetchColumn();

$stmtProcCount = $pdo->prepare("
    SELECT COUNT(DISTINCT e.id) 
    FROM expedientes e 
    JOIN expedientes_historial eh ON e.id = eh.expediente_id 
    JOIN estados_tramite et ON e.estado_actual = et.codigo 
    WHERE eh.usuario_id = ? 
      AND et.rol_responsable != 'ADQUISICIONES' 
      AND (eh.estado_anterior IN ('EN_COTIZACION_ADQ', 'EN_GESTION_ADQUISICIONES', 'EN_EMISION_OC', 'ESPERANDO_ACEPTACION_OC') OR eh.accion IN ('INGRESAR_ID_PORTAL', 'SUBIR_COTIZACIONES', 'EMITIR_OC', 'PUBLICAR_LICITACION', 'GESTION_ADQUISICIONES'))
");
$stmtProcCount->execute([$user_id]);
$count_procesadas = $stmtProcCount->fetchColumn();

$count_todas = $pdo->query("
    SELECT COUNT(DISTINCT e.id) 
    FROM expedientes e 
    JOIN estados_tramite et ON e.estado_actual = et.codigo 
    WHERE et.rol_responsable = 'ADQUISICIONES' 
       OR e.id IN (
           SELECT expediente_id FROM expedientes_historial 
           WHERE estado_anterior IN ('EN_COTIZACION_ADQ', 'EN_GESTION_ADQUISICIONES', 'EN_EMISION_OC', 'ESPERANDO_ACEPTACION_OC') 
              OR accion IN ('INGRESAR_ID_PORTAL', 'SUBIR_COTIZACIONES', 'EMITIR_OC', 'PUBLICAR_LICITACION', 'GESTION_ADQUISICIONES')
       )
")->fetchColumn();

$solicitudes = [];
$pendientes = [];

if ($vista !== 'gestionar') {
    $where = [];
    $params = [];

    if ($vista === 'procesadas') {
        $where[] = "et.rol_responsable != 'ADQUISICIONES'";
        $where[] = "EXISTS (
            SELECT 1 FROM expedientes_historial eh 
            WHERE eh.expediente_id = e.id 
              AND eh.usuario_id = :hist_uid 
              AND (eh.estado_anterior IN ('EN_COTIZACION_ADQ', 'EN_GESTION_ADQUISICIONES', 'EN_EMISION_OC', 'ESPERANDO_ACEPTACION_OC') OR eh.accion IN ('INGRESAR_ID_PORTAL', 'SUBIR_COTIZACIONES', 'EMITIR_OC', 'PUBLICAR_LICITACION', 'GESTION_ADQUISICIONES'))
        )";
        $params[':hist_uid'] = $user_id;
    } elseif ($vista === 'todas') {
        $where[] = "(et.rol_responsable = 'ADQUISICIONES' OR EXISTS (
            SELECT 1 FROM expedientes_historial eh 
            WHERE eh.expediente_id = e.id 
              AND (eh.estado_anterior IN ('EN_COTIZACION_ADQ', 'EN_GESTION_ADQUISICIONES', 'EN_EMISION_OC', 'ESPERANDO_ACEPTACION_OC') OR eh.accion IN ('INGRESAR_ID_PORTAL', 'SUBIR_COTIZACIONES', 'EMITIR_OC', 'PUBLICAR_LICITACION', 'GESTION_ADQUISICIONES'))
        ))";
    } else {
        if (!in_array($vista, ['lista', 'pendientes'])) $vista = 'pendientes';
        $where[] = "et.rol_responsable = 'ADQUISICIONES'";
    }

    if ($f_q) {
        $where[] = "(e.codigo_interno LIKE :q OR e.motivo_compra LIKE :q OR e.titulo_compra LIKE :q)";
        $params[':q'] = "%$f_q%";
    }
    if ($f_tipo) { $where[] = "e.tipo_compra_id = :tipo"; $params[':tipo'] = $f_tipo; }
    if ($f_estado) { $where[] = "e.estado_actual = :est"; $params[':est'] = $f_estado; }
    if ($f_desde) { $where[] = "DATE(e.created_at) >= :desde"; $params[':desde'] = $f_desde; }
    if ($f_hasta) { $where[] = "DATE(e.created_at) <= :hasta"; $params[':hasta'] = $f_hasta; }

    $where_sql = implode(" AND ", $where);
    if ($where_sql) $where_sql = "WHERE " . $where_sql;

    $sqlLista = "
        SELECT 
            e.*, 
            u.nombre_completo as solicitante,
            un.nombre as unidad_nombre,
            tc.nombre as tipo_compra_nom,
            p.nombre as prioridad_nombre,
            p.clase_css as prioridad_css,
            cc.nombre as cc_nombre,
            cc.codigo_cuenta as cc_codigo,
            et.nombre as estado_nombre,
            ru.nombre as rango_utm_nombre,
            (SELECT GROUP_CONCAT(CONCAT(ruta_archivo, '::', IFNULL(nombre_original, 'Adjunto'), '::', tipo_doc, '::', DATE_FORMAT(fecha_subida, '%d/%m/%Y %H:%i')) SEPARATOR '||') 
             FROM expedientes_documentos ed WHERE ed.expediente_id = e.id) as docs_adjuntos
        FROM expedientes e
        JOIN usuarios u ON e.usuario_creador_id = u.id
        JOIN unidades un ON e.unidad_origen_id = un.id
        JOIN tipos_compra tc ON e.tipo_compra_id = tc.id
        JOIN prioridades p ON e.prioridad_id = p.id
        JOIN centros_costo cc ON e.centro_costo_id = cc.id
        JOIN estados_tramite et ON e.estado_actual = et.codigo
        LEFT JOIN rangos_utm ru ON e.rango_utm_id = ru.id
        $where_sql
        ORDER BY p.id DESC, e.created_at DESC
    ";
    $stmtL = $pdo->prepare($sqlLista);
    $stmtL->execute($params);
    $solicitudes = $stmtL->fetchAll(PDO::FETCH_ASSOC);

    foreach ($solicitudes as &$row) {
        $stmtItems = $pdo->prepare("SELECT id, descripcion, cantidad, precio_unitario, unidad_medida FROM expedientes_items WHERE expediente_id = ?");
        $stmtItems->execute([$row['id']]);
        $row['items_detalle'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($row);

    $pendientes = $solicitudes;
}

$tipos_compra_filtro = $pdo->query("SELECT id, nombre FROM tipos_compra WHERE activo=1 ORDER BY nombre")->fetchAll();
$estados_filtro = $pdo->query("SELECT codigo, nombre FROM estados_tramite ORDER BY nombre")->fetchAll();

if (!function_exists('color_estado')) {
    function color_estado($estado_codigo) {
        if (in_array($estado_codigo, ['BORRADOR', 'EN_REVISION_JEFATURA'])) return 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle';
        if (in_array($estado_codigo, ['RECHAZADO', 'ANULADO'])) return 'bg-danger-subtle text-danger-emphasis text-decoration-line-through border border-danger-subtle';
        if ($estado_codigo === 'FINALIZADO') return 'bg-success-subtle text-success-emphasis fw-bold border border-success-subtle';
        if (in_array($estado_codigo, ['EN_COTIZACION_ADQ', 'EN_GESTION_ADQUISICIONES'])) return 'bg-info-subtle text-info-emphasis fw-bold border border-info-subtle';
        if ($estado_codigo === 'ESPERANDO_ACEPTACION_OC') return 'bg-warning-subtle text-warning-emphasis fw-bold border border-warning-subtle';
        return 'bg-primary-subtle text-primary-emphasis border border-primary-subtle';
    }
}

if ($vista === 'gestionar' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $stmt = $pdo->prepare("
        SELECT e.*, u.nombre_completo as solicitante, un.nombre as unidad, cc.nombre as centro_costo, tc.nombre as tipo_compra_nom, tc.codigo as tipo_compra_cod, prov.razon_social as proveedor_nombre, prov.rut as proveedor_rut, et.nombre as estado_nombre, et.rol_responsable
        FROM expedientes e 
        JOIN usuarios u ON e.usuario_creador_id = u.id 
        JOIN unidades un ON e.unidad_origen_id = un.id 
        JOIN centros_costo cc ON e.centro_costo_id = cc.id 
        JOIN tipos_compra tc ON e.tipo_compra_id = tc.id
        JOIN estados_tramite et ON e.estado_actual = et.codigo
        LEFT JOIN proveedores prov ON e.proveedor_adjudicado_id = prov.id
        WHERE e.id = ?
    ");
    $stmt->execute([$id]); 
    $exp = $stmt->fetch();
    if (!$exp) die("Error de datos o expediente no encontrado.");
    
    $es_accionable = (($exp['rol_responsable'] ?? '') === 'ADQUISICIONES');
    
    $stmtItems = $pdo->prepare("
        SELECT ei.*, cm.codigo as cuenta_codigo 
        FROM expedientes_items ei 
        LEFT JOIN presupuestos_asignados pa ON ei.presupuesto_asignado_id = pa.id 
        LEFT JOIN cuentas_maestras cm ON pa.cuenta_maestra_id = cm.id 
        WHERE ei.expediente_id = ?
    ");
    $stmtItems->execute([$id]);
    $items = $stmtItems->fetchAll();
    
    $stmtCrit = $pdo->prepare("SELECT * FROM expedientes_criterios WHERE expediente_id = ? ORDER BY numero_criterio ASC");
    $stmtCrit->execute([$id]);
    $criterios = $stmtCrit->fetchAll();
    
    $stmtDocs = $pdo->prepare("SELECT * FROM expedientes_documentos WHERE expediente_id = ? ORDER BY fecha_subida DESC");
    $stmtDocs->execute([$id]);
    $docs = $stmtDocs->fetchAll();
    $proveedores = $pdo->query("SELECT * FROM proveedores ORDER BY razon_social ASC")->fetchAll();
}

function money($v) { return '$ ' . number_format($v, 0, ',', '.'); }
?>