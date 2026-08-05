<?php
// control_presupuestario_controller.php - Lógica de Negocio (V4.8 - Criterios y CM IDs)
require_once __DIR__ . '/config.php';

// 1. SEGURIDAD Y SESIÓN
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

// SOLO ROL PRESUPUESTO O ADMIN O SYSADMIN
$rol = $_SESSION['user_rol'] ?? '';
if ($rol !== 'PRESUPUESTO' && $rol !== 'ADMIN_MUNICIPAL' && $rol !== 'SYSADMIN') {
    die("Acceso Denegado. Módulo exclusivo de VB Presupuestario.");
}

$user_id = $_SESSION['user_id'];
$mensaje = ''; $tipo_mensaje = '';
$vista = $_GET['view'] ?? 'pendientes'; // 'pendientes', 'procesados', 'revisar'

// =====================================================================
// MANEJO DE ACCIONES (POST) - MOTOR DE FLUJOS DINÁMICO
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $exp_id = isset($_POST['expediente_id']) ? (int)$_POST['expediente_id'] : null;
        $accion = $_POST['accion'] ?? '';
        $transicion_id = $_POST['transicion_id'] ?? null;
        
        $pdo->beginTransaction();

        $stmtEst = $pdo->prepare("SELECT estado_actual FROM expedientes WHERE id = ?");
        $stmtEst->execute([$exp_id]);
        $estado_actual = $stmtEst->fetchColumn();

        // Si se recibe transicion_id, determinar la acción lógica
        $trans = null;
        if ($transicion_id) {
            $stmtT = $pdo->prepare("SELECT * FROM flujos_definicion WHERE id = ?");
            $stmtT->execute([$transicion_id]);
            $trans = $stmtT->fetch();
            if ($trans) {
                $accion = strtolower($trans['accion_codigo']); // 'aprobar', 'devolver', 'rechazar'
            }
        }

        // A. VISAR (APROBAR Y/O COMPROMETER FONDOS)
        if ($accion === 'aprobar') {
            if ($estado_actual === 'EN_VALIDACION_PRESUPUESTARIA') {
                $comentario = "Certificado de Disponibilidad Presupuestaria (CDP) generado. Visación presupuestaria aprobada.";
                
            } elseif ($estado_actual === 'EN_VALIDACION_PRESUPUESTARIA_FINAL') {
                // Subir Borrador CDP si fue adjuntado
                if (!empty($_FILES['archivo_cdp_borrador']['name'])) {
                    $ext_borrador = strtolower(pathinfo($_FILES['archivo_cdp_borrador']['name'], PATHINFO_EXTENSION));
                    if ($ext_borrador !== 'pdf') throw new Exception("El Borrador de CDP debe estar en formato PDF.");
                    
                    $anio = date('Y');
                    $dir = __DIR__ . "/uploads/$anio/exp_$exp_id/";
                    if (!file_exists($dir)) mkdir($dir, 0777, true);

                    $name_borrador = "CDP_BORRADOR_" . time() . ".pdf";
                    $ruta_borrador = "uploads/$anio/exp_$exp_id/$name_borrador";
                    if (move_uploaded_file($_FILES['archivo_cdp_borrador']['tmp_name'], $dir . $name_borrador)) {
                        $pdo->prepare("INSERT INTO expedientes_documentos (expediente_id, subido_por_id, tipo_doc, ruta_archivo, nombre_original) VALUES (?, ?, 'CDP_BORRADOR', ?, ?)")
                            ->execute([$exp_id, $user_id, $ruta_borrador, $_FILES['archivo_cdp_borrador']['name']]);
                    }
                }

                // Subir Situación de Gastos si fue adjuntada
                if (!empty($_FILES['archivo_situacion_gastos']['name'])) {
                    $ext_situacion = strtolower(pathinfo($_FILES['archivo_situacion_gastos']['name'], PATHINFO_EXTENSION));
                    if ($ext_situacion !== 'pdf') throw new Exception("La Situación Presupuestaria de Gastos debe estar en formato PDF.");

                    $anio = date('Y');
                    $dir = __DIR__ . "/uploads/$anio/exp_$exp_id/";
                    if (!file_exists($dir)) mkdir($dir, 0777, true);

                    $name_situacion = "SITUACION_GASTOS_" . time() . ".pdf";
                    $ruta_situacion = "uploads/$anio/exp_$exp_id/$name_situacion";
                    if (move_uploaded_file($_FILES['archivo_situacion_gastos']['tmp_name'], $dir . $name_situacion)) {
                        $pdo->prepare("INSERT INTO expedientes_documentos (expediente_id, subido_por_id, tipo_doc, ruta_archivo, nombre_original) VALUES (?, ?, 'SITUACION_PRESUPUESTARIA', ?, ?)")
                            ->execute([$exp_id, $user_id, $ruta_situacion, $_FILES['archivo_situacion_gastos']['name']]);
                    }
                }

                $comentario = "Visación final por gasto real aprobada. Borrador de CDP y Situación de Gastos cargados. Expediente enviado a Finanzas para firma.";
            }

            // Subir CDP si fue adjuntado
            if (!empty($_FILES['archivo_cdp']['name'])) {
                $ext = strtolower(pathinfo($_FILES['archivo_cdp']['name'], PATHINFO_EXTENSION));
                if($ext !== 'pdf') throw new Exception("El archivo del CDP debe ser un PDF.");
                
                $anio = date('Y');
                $dir = __DIR__ . "/uploads/$anio/exp_$exp_id/";
                if (!file_exists($dir)) mkdir($dir, 0777, true);
                
                $name = "CDP_OFICIAL_" . time() . ".pdf";
                if (move_uploaded_file($_FILES['archivo_cdp']['tmp_name'], $dir . $name)) {
                    $ruta = "uploads/$anio/exp_$exp_id/$name";
                    $pdo->prepare("INSERT INTO expedientes_documentos (expediente_id, subido_por_id, tipo_doc, ruta_archivo, nombre_original) VALUES (?, ?, 'OTRO', ?, ?)")
                        ->execute([$exp_id, $user_id, $ruta, $_FILES['archivo_cdp']['name']]);
                }
            }

            $pdo->prepare("UPDATE expedientes SET fecha_visa_presupuesto = NOW() WHERE id = ?")->execute([$exp_id]);

            if ($transicion_id) {
                $nuevo_estado = ejecutar_transicion_por_id($pdo, $exp_id, $user_id, $transicion_id, $_POST['motivo_rechazo'] ?: $comentario);
            } else {
                $nuevo_estado = avanzar_flujo($pdo, $exp_id, $user_id, $comentario);
            }

            $stmtNd = $pdo->prepare("SELECT nombre FROM estados_tramite WHERE codigo = ?");
            $stmtNd->execute([$nuevo_estado]);
            $nombre_dest = $stmtNd->fetchColumn();
            $mensaje = "Visación presupuestaria exitosa. El trámite avanzó a: $nombre_dest.";
            $tipo_mensaje = "success";
            $vista = 'pendientes';
        } 
        
        // B. RECHAZAR / DEVOLVER (LIBERAR FONDOS)
        elseif (in_array($accion, ['rechazar', 'devolver'])) {
            $motivo = trim($_POST['motivo_rechazo']);
            if (empty($motivo)) throw new Exception("Debe ingresar un motivo para la observación/rechazo.");

            if ($transicion_id) {
                $nuevo_estado = ejecutar_transicion_por_id($pdo, $exp_id, $user_id, $transicion_id, $motivo);
                $stmtNd = $pdo->prepare("SELECT nombre FROM estados_tramite WHERE codigo = ?");
                $stmtNd->execute([$nuevo_estado]);
                $nombre_dest = $stmtNd->fetchColumn();
                $mensaje = "Acción '" . htmlspecialchars($trans['accion_label']) . "' procesada correctamente. Solicitud enviada a: $nombre_dest.";
            } else {
                if ($accion === 'rechazar') {
                    rechazar_flujo($pdo, $exp_id, $user_id, "Rechazado definitivamente por Presupuesto: " . $motivo);
                    $mensaje = "Solicitud rechazada. Fondos liberados.";
                } else {
                    devolver_flujo($pdo, $exp_id, $user_id, "Devuelto por Presupuesto: " . $motivo);
                    $mensaje = "Solicitud devuelta para corrección. Fondos liberados.";
                }
            }
            $tipo_mensaje = ($accion === 'devolver') ? 'warning' : 'error';
            $vista = 'pendientes';
        }
        
        // C. SOLICITAR CDP A FINANZAS
        elseif ($accion === 'solicitar_cdp') {
            if (!$transicion_id) {
                throw new Exception("Transición de solicitud de CDP no válida.");
            }

            if (empty($_FILES['archivo_cdp_borrador']['name'])) {
                throw new Exception("Debe adjuntar obligatoriamente el Borrador de CDP (Paso 1).");
            }
            if (empty($_FILES['archivo_situacion_gastos']['name'])) {
                throw new Exception("Debe adjuntar obligatoriamente el documento de Situación Presupuestaria de Gastos (Paso 2).");
            }

            $ext_borrador = strtolower(pathinfo($_FILES['archivo_cdp_borrador']['name'], PATHINFO_EXTENSION));
            $ext_situacion = strtolower(pathinfo($_FILES['archivo_situacion_gastos']['name'], PATHINFO_EXTENSION));

            if ($ext_borrador !== 'pdf' || $ext_situacion !== 'pdf') {
                throw new Exception("Ambos documentos adjuntos deben estar en formato PDF.");
            }

            $anio = date('Y');
            $dir = __DIR__ . "/uploads/$anio/exp_$exp_id/";
            if (!file_exists($dir)) mkdir($dir, 0777, true);

            // 1. Subir Borrador CDP
            $name_borrador = "CDP_BORRADOR_" . time() . ".pdf";
            $ruta_borrador = "uploads/$anio/exp_$exp_id/$name_borrador";
            if (!move_uploaded_file($_FILES['archivo_cdp_borrador']['tmp_name'], $dir . $name_borrador)) {
                throw new Exception("Error al guardar el Borrador de CDP.");
            }

            // 2. Subir Situación de Gastos
            $name_situacion = "SITUACION_GASTOS_" . time() . ".pdf";
            $ruta_situacion = "uploads/$anio/exp_$exp_id/$name_situacion";
            if (!move_uploaded_file($_FILES['archivo_situacion_gastos']['tmp_name'], $dir . $name_situacion)) {
                throw new Exception("Error al guardar la Situación Presupuestaria de Gastos.");
            }

            // 3. Registrar en DB
            $pdo->prepare("INSERT INTO expedientes_documentos (expediente_id, subido_por_id, tipo_doc, ruta_archivo, nombre_original) VALUES (?, ?, 'CDP_BORRADOR', ?, ?)")
                ->execute([$exp_id, $user_id, $ruta_borrador, $_FILES['archivo_cdp_borrador']['name']]);

            $pdo->prepare("INSERT INTO expedientes_documentos (expediente_id, subido_por_id, tipo_doc, ruta_archivo, nombre_original) VALUES (?, ?, 'SITUACION_PRESUPUESTARIA', ?, ?)")
                ->execute([$exp_id, $user_id, $ruta_situacion, $_FILES['archivo_situacion_gastos']['name']]);

            $comentario = "Borrador de CDP y Situación Presupuestaria de Gastos cargados. Solicitud enviada a Finanzas.";
            $nuevo_estado = ejecutar_transicion_por_id($pdo, $exp_id, $user_id, $transicion_id, $comentario);
            
            $stmtNd = $pdo->prepare("SELECT nombre FROM estados_tramite WHERE codigo = ?");
            $stmtNd->execute([$nuevo_estado]);
            $nombre_dest = $stmtNd->fetchColumn();
            
            $mensaje = "Solicitud enviada a Finanzas para firma de CDP. Estado actual: $nombre_dest.";
            $tipo_mensaje = "success";
            $vista = 'pendientes';
        }

        $pdo->commit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// =====================================================================
// CONSULTAS GET (CARGA DE VISTAS HOMOLOGADAS)
// =====================================================================

$f_q = trim($_GET['f_q'] ?? $_GET['q'] ?? '');
$f_tipo = trim($_GET['f_tipo'] ?? '');
$f_estado = trim($_GET['f_estado'] ?? '');
$f_desde = trim($_GET['f_desde'] ?? '');
$f_hasta = trim($_GET['f_hasta'] ?? '');

// CONTADORES PARA PESTAÑAS
$count_pendientes_inicial = $pdo->query("SELECT COUNT(*) FROM expedientes WHERE estado_actual = 'EN_VALIDACION_PRESUPUESTARIA'")->fetchColumn();
$count_pendientes_final = $pdo->query("SELECT COUNT(*) FROM expedientes WHERE estado_actual IN ('EN_VALIDACION_PRESUPUESTARIA_FINAL', 'ESPERANDO_CDP_FINANZAS', 'ESPERANDO_CDP_FINANZAS_FINAL')")->fetchColumn();

$stmtProcCount = $pdo->prepare("SELECT COUNT(DISTINCT e.id) FROM expedientes e JOIN expedientes_historial eh ON e.id = eh.expediente_id WHERE eh.usuario_id = ? AND eh.accion IN ('APROBAR', 'RECHAZAR', 'DEVOLVER') AND eh.estado_anterior LIKE '%PRESUPUESTARIA%'");
$stmtProcCount->execute([$user_id]);
$count_procesados = $stmtProcCount->fetchColumn();

$count_todas = $pdo->query("SELECT COUNT(*) FROM expedientes WHERE estado_actual LIKE '%PRESUPUESTARIA%' OR estado_actual LIKE '%CDP%' OR id IN (SELECT expediente_id FROM expedientes_historial WHERE estado_anterior LIKE '%PRESUPUESTARIA%')")->fetchColumn();

$solicitudes = [];
$pendientes = [];
$procesados = [];

if ($vista !== 'revisar') {
    $where = [];
    $params = [];

    if ($vista === 'pendientes_final') {
        $where[] = "e.estado_actual IN ('EN_VALIDACION_PRESUPUESTARIA_FINAL', 'ESPERANDO_CDP_FINANZAS', 'ESPERANDO_CDP_FINANZAS_FINAL')";
    } elseif ($vista === 'procesados') {
        $where[] = "EXISTS (SELECT 1 FROM expedientes_historial eh WHERE eh.expediente_id = e.id AND eh.usuario_id = :hist_uid AND eh.accion IN ('APROBAR', 'RECHAZAR', 'DEVOLVER') AND eh.estado_anterior LIKE '%PRESUPUESTARIA%')";
        $params[':hist_uid'] = $user_id;
    } elseif ($vista === 'todas') {
        $where[] = "(e.estado_actual LIKE '%PRESUPUESTARIA%' OR e.estado_actual LIKE '%CDP%' OR EXISTS (SELECT 1 FROM expedientes_historial eh WHERE eh.expediente_id = e.id AND eh.estado_anterior LIKE '%PRESUPUESTARIA%'))";
    } else {
        if ($vista !== 'pendientes_inicial') $vista = 'pendientes_inicial';
        $where[] = "e.estado_actual = 'EN_VALIDACION_PRESUPUESTARIA'";
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

    // Asignación para compatibilidad
    if (in_array($vista, ['pendientes', 'pendientes_inicial', 'pendientes_final'])) $pendientes = $solicitudes;
    if ($vista === 'procesados') $procesados = $solicitudes;
}

$tipos_compra_filtro = $pdo->query("SELECT id, nombre FROM tipos_compra WHERE activo=1 ORDER BY nombre")->fetchAll();
$estados_filtro = $pdo->query("SELECT codigo, nombre FROM estados_tramite ORDER BY nombre")->fetchAll();

if (!function_exists('color_estado')) {
    function color_estado($estado_codigo) {
        if (in_array($estado_codigo, ['BORRADOR', 'EN_REVISION_JEFATURA'])) return 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle';
        if (in_array($estado_codigo, ['RECHAZADO', 'ANULADO'])) return 'bg-danger-subtle text-danger-emphasis text-decoration-line-through border border-danger-subtle';
        if ($estado_codigo === 'FINALIZADO') return 'bg-success-subtle text-success-emphasis fw-bold border border-success-subtle';
        if (in_array($estado_codigo, ['EN_COTIZACION_ADQ', 'EN_GESTION_ADQUISICIONES'])) return 'bg-info-subtle text-info-emphasis fw-bold border border-info-subtle';
        if (in_array($estado_codigo, ['EN_VALIDACION_PRESUPUESTARIA', 'EN_VALIDACION_PRESUPUESTARIA_FINAL'])) return 'bg-primary-subtle text-primary-emphasis fw-bold border border-primary-subtle';
        if (in_array($estado_codigo, ['ESPERANDO_CDP_FINANZAS', 'ESPERANDO_CDP_FINANZAS_FINAL'])) return 'bg-warning-subtle text-warning-emphasis fw-bold border border-warning-subtle';
        return 'bg-primary-subtle text-primary-emphasis border border-primary-subtle';
    }
}

// DETALLE DE REVISIÓN
if ($vista === 'revisar' && isset($_GET['id'])) {
    $stmtHead = $pdo->prepare("
        SELECT e.*, u.nombre_completo as solicitante, un.nombre as unidad, cc.nombre as centro_costo, tc.nombre as tipo_compra_nom, tc.codigo as tipo_compra_cod, p.nombre as prioridad_nom, p.clase_css, et.nombre as estado_nombre, et.rol_responsable, prov.razon_social as proveedor_nombre, prov.rut as proveedor_rut
        FROM expedientes e
        JOIN usuarios u ON e.usuario_creador_id = u.id
        JOIN unidades un ON e.unidad_origen_id = un.id
        JOIN centros_costo cc ON e.centro_costo_id = cc.id
        JOIN tipos_compra tc ON e.tipo_compra_id = tc.id
        JOIN prioridades p ON e.prioridad_id = p.id
        JOIN estados_tramite et ON e.estado_actual = et.codigo
        LEFT JOIN proveedores prov ON e.proveedor_adjudicado_id = prov.id
        WHERE e.id = ?
    ");
    $stmtHead->execute([$_GET['id']]);
    $expediente = $stmtHead->fetch();
    if (!$expediente) die("Expediente no encontrado.");

    $es_accionable = in_array($expediente['estado_actual'], ['EN_VALIDACION_PRESUPUESTARIA', 'EN_VALIDACION_PRESUPUESTARIA_FINAL']);
    $es_fase_inicial = in_array($expediente['estado_actual'], ['EN_VALIDACION_PRESUPUESTARIA', 'ESPERANDO_CDP_FINANZAS']);

    $stmtItems = $pdo->prepare("
        SELECT ei.*, cm.codigo as cuenta_codigo, cm.nombre as cuenta_nombre, ag.codigo as ag_codigo
        FROM expedientes_items ei
        JOIN presupuestos_asignados pa ON ei.presupuesto_asignado_id = pa.id
        JOIN cuentas_maestras cm ON pa.cuenta_maestra_id = cm.id
        LEFT JOIN areas_gestion ag ON pa.area_gestion_id = ag.id
        WHERE ei.expediente_id = ?
    ");
    $stmtItems->execute([$_GET['id']]);
    $items = $stmtItems->fetchAll();

    $stmtDocs = $pdo->prepare("SELECT * FROM expedientes_documentos WHERE expediente_id = ? ORDER BY fecha_subida DESC");
    $stmtDocs->execute([$_GET['id']]);
    $docs = $stmtDocs->fetchAll();

    // Verificar si existe el CDP firmado subido por Finanzas
    $has_cdp_finanzas = false;
    $doc_cdp_firmado = null;
    foreach ($docs as $d) {
        if ($d['tipo_doc'] === 'CDP_FIRMADO_FINANZAS' || 
            $d['tipo_doc'] === 'CDP_OFICIAL_FINANZAS' || 
            $d['tipo_doc'] === 'CDP_OFICIAL' || 
            strpos($d['ruta_archivo'], 'CDP_FIRMADO_FINANZAS') !== false ||
            strpos($d['ruta_archivo'], 'CDP_OFICIAL_FINANZAS') !== false) {
            $has_cdp_finanzas = true;
            $doc_cdp_firmado = $d;
            break;
        }
    }

    // Traer Criterios de Evaluación para Licitaciones
    $stmtCrit = $pdo->prepare("SELECT * FROM expedientes_criterios WHERE expediente_id = ? ORDER BY numero_criterio ASC");
    $stmtCrit->execute([$_GET['id']]);
    $criterios = $stmtCrit->fetchAll(PDO::FETCH_ASSOC);
}

function money($v) { return '$ ' . number_format($v, 0, ',', '.'); }
?>