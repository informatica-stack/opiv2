<?php
// mis_solicitudes_controller.php - Lógica de Negocio (V5.3 - Modulares e Ítems)
require_once __DIR__ . '/config.php';

// 1. SEGURIDAD
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$mensaje = '';
$tipo_mensaje = '';

// ==============================================================================
// 2. MANEJO DE ACCIONES (POST/GET)
// ==============================================================================

// Integración del Nuevo Controlador de Adjudicación
require_once __DIR__ . '/modal_adjudicacion_controller.php';

// A. DESCARGAR TODOS LOS ADJUNTOS EN ZIP
if (isset($_GET['descargar_zip'])) {
    $exp_id = (int)$_GET['descargar_zip'];
    $check = $pdo->prepare("SELECT codigo_interno FROM expedientes WHERE id = ? AND usuario_creador_id = ?");
    $check->execute([$exp_id, $user_id]);
    $exp = $check->fetch();
    
    if ($exp) {
        $stmtDocs = $pdo->prepare("SELECT ruta_archivo, nombre_original FROM expedientes_documentos WHERE expediente_id = ?");
        $stmtDocs->execute([$exp_id]);
        $docs = $stmtDocs->fetchAll();
        
        if (count($docs) > 0) {
            $zip = new ZipArchive();
            $zipName = "Adjuntos_" . $exp['codigo_interno'] . ".zip";
            $zipPath = sys_get_temp_dir() . '/' . $zipName;
            
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                foreach ($docs as $doc) {
                    $filePath = __DIR__ . '/' . $doc['ruta_archivo'];
                    if (file_exists($filePath)) $zip->addFile($filePath, $doc['nombre_original']);
                }
                $zip->close();
                
                header('Content-Type: application/zip');
                header('Content-disposition: attachment; filename=' . $zipName);
                header('Content-Length: ' . filesize($zipPath));
                readfile($zipPath);
                unlink($zipPath);
                exit;
            } else {
                $mensaje = "Error al generar el archivo comprimido.";
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "No hay documentos adjuntos para descargar.";
            $tipo_mensaje = "warning";
        }
    }
}

// B. ANULAR SOLICITUD
if (isset($_GET['anular_id'])) {
    try {
        $anular_id = $_GET['anular_id'];
        $stmtCheck = $pdo->prepare("SELECT id, estado_actual FROM expedientes WHERE id = ? AND usuario_creador_id = ? AND estado_actual IN ('BORRADOR', 'EN_REVISION_JEFATURA', 'EN_CORRECCION')");
        $stmtCheck->execute([$anular_id, $user_id]);
        $exp = $stmtCheck->fetch();
        
        if ($exp) {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE expedientes SET estado_actual = 'ANULADO', observacion_cierre = 'Cancelado por el usuario' WHERE id = ?")->execute([$anular_id]);
            $pdo->prepare("INSERT INTO expedientes_historial (expediente_id, usuario_id, accion, estado_anterior, estado_nuevo, comentario) VALUES (?, ?, 'ANULAR', ?, 'ANULADO', 'Cancelado por el usuario creador')")->execute([$anular_id, $user_id, $exp['estado_actual']]);
            $pdo->commit();
            $mensaje = "Solicitud anulada correctamente.";
            $tipo_mensaje = "success";
        } else {
            throw new Exception("No se puede anular: El trámite ya fue visado o no tiene permisos.");
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $mensaje = $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// ==============================================================================
// 3. DATOS MAESTROS Y FILTROS
// ==============================================================================

$proveedores_db = $pdo->query("SELECT id, rut, razon_social FROM proveedores ORDER BY razon_social ASC")->fetchAll(PDO::FETCH_ASSOC);

$f_q = $_GET['f_q'] ?? '';
$f_tipo = $_GET['f_tipo'] ?? '';
$f_estado = $_GET['f_estado'] ?? '';
$f_desde = $_GET['f_desde'] ?? '';
$f_hasta = $_GET['f_hasta'] ?? '';

$page = max(1, (int)($_GET['p'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$where = ["e.usuario_creador_id = :uid"];
$params = [':uid' => $user_id];

if ($f_q) { 
    $where[] = "(e.codigo_interno LIKE :q OR e.motivo_compra LIKE :q OR e.titulo_compra LIKE :q)"; 
    $params[':q'] = "%$f_q%"; 
}
if ($f_tipo) { $where[] = "e.tipo_compra_id = :tipo"; $params[':tipo'] = $f_tipo; }
if ($f_estado) { $where[] = "e.estado_actual = :est"; $params[':est'] = $f_estado; }
if ($f_desde) { $where[] = "DATE(e.created_at) >= :desde"; $params[':desde'] = $f_desde; }
if ($f_hasta) { $where[] = "DATE(e.created_at) <= :hasta"; $params[':hasta'] = $f_hasta; }

$where_sql = implode(" AND ", $where);

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM expedientes e WHERE $where_sql");
$stmtTotal->execute($params);
$total_records = $stmtTotal->fetchColumn();
$total_pages = ceil($total_records / $limit);

$sql = "
    SELECT 
        e.*, 
        tc.nombre as tipo_nombre,
        p.nombre as prioridad_nombre,
        p.clase_css as prioridad_css,
        cc.nombre as cc_nombre,
        et.nombre as estado_nombre,
        (SELECT GROUP_CONCAT(CONCAT(ruta_archivo, '::', IFNULL(nombre_original, 'Adjunto'), '::', tipo_doc, '::', DATE_FORMAT(fecha_subida, '%d/%m/%Y %H:%i')) SEPARATOR '||') 
         FROM expedientes_documentos ed WHERE ed.expediente_id = e.id) as docs_adjuntos
    FROM expedientes e
    JOIN tipos_compra tc ON e.tipo_compra_id = tc.id
    JOIN prioridades p ON e.prioridad_id = p.id
    JOIN centros_costo cc ON e.centro_costo_id = cc.id
    JOIN estados_tramite et ON e.estado_actual = et.codigo
    WHERE $where_sql
    ORDER BY e.created_at DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$mis_solicitudes = $stmt->fetchAll();

// Adjuntar los ítems en tiempo real a TODOS los expedientes en la vista para el Modal Ver Items
foreach ($mis_solicitudes as &$row) {
    $stmtItems = $pdo->prepare("SELECT id, descripcion, cantidad, precio_unitario, unidad_medida FROM expedientes_items WHERE expediente_id = ?");
    $stmtItems->execute([$row['id']]);
    $row['items_detalle'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
}
unset($row);

$tipos_compra_filtro = $pdo->query("SELECT id, nombre FROM tipos_compra WHERE activo=1 ORDER BY nombre")->fetchAll();
$estados_filtro = $pdo->query("SELECT codigo, nombre FROM estados_tramite ORDER BY nombre")->fetchAll();

function color_estado($estado_codigo) {
    if (in_array($estado_codigo, ['BORRADOR', 'EN_REVISION_JEFATURA'])) return 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle';
    if (in_array($estado_codigo, ['RECHAZADO', 'ANULADO'])) return 'bg-danger-subtle text-danger-emphasis text-decoration-line-through border border-danger-subtle';
    if ($estado_codigo === 'FINALIZADO') return 'bg-success-subtle text-success-emphasis fw-bold border border-success-subtle';
    if ($estado_codigo === 'RECEPCIONADO_POR_ADQUISICIONES') return 'bg-info-subtle text-info-emphasis fw-bold border border-info-subtle';
    if ($estado_codigo === 'EN_EVALUACION_OFERTAS') return 'bg-warning-subtle text-warning-emphasis fw-bold border border-warning-subtle';
    if ($estado_codigo === 'EN_CORRECCION') return 'bg-danger-subtle text-danger-emphasis fw-bold border border-danger-subtle'; 
    return 'bg-primary-subtle text-primary-emphasis border border-primary-subtle';
}
function money($v) { return '$ ' . number_format($v, 0, ',', '.'); }

$query_string = $_GET; unset($query_string['p']); unset($query_string['anular_id']); unset($query_string['descargar_zip']); 
$base_url = '?' . http_build_query($query_string) . '&p=';
?>