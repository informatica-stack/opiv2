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

            // 3. Guardar Número de Orden de Compra
            if ($accion === 'emitir_oc' && !empty($_POST['orden_compra_numero'])) {
                $pdo->prepare("UPDATE expedientes SET orden_compra_numero = ? WHERE id = ?")->execute([trim($_POST['orden_compra_numero']), $exp_id]);
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
// CONSULTAS GET (CARGA DE VISTAS)
// ==============================================================================

$sqlPend = "
    SELECT e.*, u.nombre_completo as solicitante, un.nombre as unidad, tc.nombre as tipo_compra, p.clase_css, p.nombre as prioridad, et.nombre as estado_nombre
    FROM expedientes e
    JOIN usuarios u ON e.usuario_creador_id = u.id
    JOIN unidades un ON e.unidad_origen_id = un.id
    JOIN tipos_compra tc ON e.tipo_compra_id = tc.id
    JOIN prioridades p ON e.prioridad_id = p.id
    JOIN estados_tramite et ON e.estado_actual = et.codigo
    WHERE et.rol_responsable = 'ADQUISICIONES' 
    ORDER BY p.id DESC, e.created_at ASC
";
$pendientes = $pdo->query($sqlPend)->fetchAll();

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