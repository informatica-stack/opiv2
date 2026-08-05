<?php
// finanzas_controller.php - Controlador del Módulo de Finanzas (V1.0)
require_once __DIR__ . '/config.php';

// 1. SEGURIDAD Y SESIÓN
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

// SOLO ROL FINANZAS O ADMIN O SYSADMIN
$rol = $_SESSION['user_rol'] ?? '';
if ($rol !== 'FINANZAS' && $rol !== 'ADMIN_MUNICIPAL' && $rol !== 'SYSADMIN') {
    die("Acceso Denegado. Módulo exclusivo de Dirección de Administración y Finanzas (CDP).");
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
        if ($estado_actual !== 'ESPERANDO_CDP_FINANZAS' && $estado_actual !== 'ESPERANDO_CDP_FINANZAS_FINAL') {
            throw new Exception("El expediente no se encuentra en estado de espera de CDP.");
        }

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

        // A. SUBIR CDP Y APROBAR (ENVIAR A ADMINISTRADOR)
        if ($accion === 'aprobar') {
            if (empty($_FILES['archivo_cdp']['name'])) {
                throw new Exception("Debe adjuntar obligatoriamente el Certificado de Disponibilidad Presupuestaria (CDP).");
            }

            // Validar y subir archivo
            $ext = strtolower(pathinfo($_FILES['archivo_cdp']['name'], PATHINFO_EXTENSION));
            if($ext !== 'pdf') throw new Exception("El archivo del CDP debe ser un PDF.");
            
            $anio = date('Y');
            $dir = __DIR__ . "/uploads/$anio/exp_$exp_id/";
            if (!file_exists($dir)) mkdir($dir, 0777, true);
            
            $name = "CDP_FIRMADO_FINANZAS_" . time() . ".pdf";
            $ruta = "uploads/$anio/exp_$exp_id/$name";
            
            if (!move_uploaded_file($_FILES['archivo_cdp']['tmp_name'], $dir . $name)) {
                throw new Exception("Error al guardar el archivo en el servidor.");
            }

            // Registrar Documento
            $pdo->prepare("INSERT INTO expedientes_documentos (expediente_id, subido_por_id, tipo_doc, ruta_archivo, nombre_original) VALUES (?, ?, 'CDP_BORRADOR', ?, ?)")
                ->execute([$exp_id, $user_id, $ruta, $_FILES['archivo_cdp']['name']]);

            $comentario = "Certificado de Disponibilidad Presupuestaria (CDP) cargado exitosamente desde SMC por Finanzas.";

            if ($transicion_id) {
                $nuevo_estado = ejecutar_transicion_por_id($pdo, $exp_id, $user_id, $transicion_id, $comentario);
            } else {
                $nuevo_estado = avanzar_flujo($pdo, $exp_id, $user_id, $comentario);
            }

            $stmtNd = $pdo->prepare("SELECT nombre FROM estados_tramite WHERE codigo = ?");
            $stmtNd->execute([$nuevo_estado]);
            $nombre_dest = $stmtNd->fetchColumn();
            $mensaje = "CDP adjuntado con éxito. Requerimiento devuelto a: $nombre_dest.";
            $tipo_mensaje = "success";
            $vista = 'pendientes';
        } 
        
        // B. DEVOLVER / RECHAZAR
        elseif (in_array($accion, ['rechazar', 'devolver'])) {
            $motivo = trim($_POST['motivo_rechazo']);
            if (empty($motivo)) throw new Exception("Debe ingresar un motivo para la devolución u observación.");

            if ($transicion_id) {
                $nuevo_estado = ejecutar_transicion_por_id($pdo, $exp_id, $user_id, $transicion_id, $motivo);
                $stmtNd = $pdo->prepare("SELECT nombre FROM estados_tramite WHERE codigo = ?");
                $stmtNd->execute([$nuevo_estado]);
                $nombre_dest = $stmtNd->fetchColumn();
                $mensaje = "Acción procesada correctamente. Requerimiento enviado a: $nombre_dest.";
            } else {
                if ($accion === 'rechazar') {
                    rechazar_flujo($pdo, $exp_id, $user_id, "Rechazado por Finanzas: " . $motivo);
                    $mensaje = "Solicitud rechazada definitivamente por Finanzas.";
                } else {
                    devolver_flujo($pdo, $exp_id, $user_id, "Devuelto por Finanzas: " . $motivo);
                    $mensaje = "Solicitud devuelta para corrección.";
                }
            }
            $tipo_mensaje = ($accion === 'devolver') ? 'warning' : 'error';
            $vista = 'pendientes';
        }

        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollBack();
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
$count_pendientes = $pdo->query("SELECT COUNT(*) FROM expedientes WHERE estado_actual IN ('ESPERANDO_CDP_FINANZAS', 'ESPERANDO_CDP_FINANZAS_FINAL')")->fetchColumn();

$stmtProcCount = $pdo->prepare("SELECT COUNT(DISTINCT e.id) FROM expedientes e JOIN expedientes_historial eh ON e.id = eh.expediente_id WHERE eh.usuario_id = ? AND eh.accion IN ('APROBAR', 'RECHAZAR', 'DEVOLVER') AND eh.estado_anterior LIKE '%FINANZAS%'");
$stmtProcCount->execute([$user_id]);
$count_procesados = $stmtProcCount->fetchColumn();

$count_todas = $pdo->query("SELECT COUNT(*) FROM expedientes WHERE estado_actual LIKE '%FINANZAS%' OR id IN (SELECT expediente_id FROM expedientes_historial WHERE estado_anterior LIKE '%FINANZAS%')")->fetchColumn();

$solicitudes = [];
$pendientes = [];
$procesados = [];

if ($vista !== 'revisar') {
    $where = [];
    $params = [];

    if ($vista === 'procesados') {
        $where[] = "EXISTS (SELECT 1 FROM expedientes_historial eh WHERE eh.expediente_id = e.id AND eh.usuario_id = :hist_uid AND eh.accion IN ('APROBAR', 'RECHAZAR', 'DEVOLVER') AND eh.estado_anterior LIKE '%FINANZAS%')";
        $params[':hist_uid'] = $user_id;
    } elseif ($vista === 'todas') {
        $where[] = "(e.estado_actual LIKE '%FINANZAS%' OR EXISTS (SELECT 1 FROM expedientes_historial eh WHERE eh.expediente_id = e.id AND eh.estado_anterior LIKE '%FINANZAS%'))";
    } else {
        $vista = 'pendientes';
        $where[] = "e.estado_actual IN ('ESPERANDO_CDP_FINANZAS', 'ESPERANDO_CDP_FINANZAS_FINAL')";
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

    if ($vista === 'pendientes') $pendientes = $solicitudes;
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

    $es_accionable = ($expediente['estado_actual'] === 'ESPERANDO_CDP_FINANZAS' || $expediente['estado_actual'] === 'ESPERANDO_CDP_FINANZAS_FINAL');

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
}

function money($v) { return '$ ' . number_format($v, 0, ',', '.'); }
?>
