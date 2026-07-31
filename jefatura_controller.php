<?php
// jefatura_controller.php - Lógica de Negocio (V4.6 - Criterios y CM)
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$es_jefe = $_SESSION['es_jefe'] ?? 0;
$rol = $_SESSION['user_rol'] ?? '';

if ($es_jefe != 1 && $rol !== 'JEFE_UNIDAD' && $rol !== 'ADMIN_MUNICIPAL' && $rol !== 'SYSADMIN') {
    die("Acceso Denegado. Este módulo es exclusivo para Jefaturas de Unidad.");
}

$unidad_id = $_SESSION['user_unidad'];
$user_id = $_SESSION['user_id'];
$mensaje = '';
$tipo_mensaje = '';
$vista = $_GET['view'] ?? 'lista';

// =====================================================================
// MANEJO DE ACCIONES (POST) - MOTOR DE FLUJOS DINÁMICO
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        $id = isset($_POST['expediente_id']) ? (int)$_POST['expediente_id'] : null;

        // Verificación de seguridad de pertenencia
        $sqlCheck = "SELECT id FROM expedientes WHERE id = ? AND estado_actual = 'EN_REVISION_JEFATURA'";
        if ($rol !== 'ADMIN_MUNICIPAL') { $sqlCheck .= " AND unidad_origen_id = $unidad_id"; }
        
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$id]);
        if (!$stmtCheck->fetch()) throw new Exception("El expediente no está disponible para su revisión.");

        $accion = $_POST['accion'] ?? '';
        $transicion_id = $_POST['transicion_id'] ?? null;

        if ($transicion_id) {
            $motivo = trim($_POST['motivo_rechazo'] ?? '');
            
            $stmtT = $pdo->prepare("SELECT * FROM flujos_definicion WHERE id = ?");
            $stmtT->execute([$transicion_id]);
            $trans = $stmtT->fetch();
            
            if ($trans && ($trans['accion_codigo'] === 'DEVOLVER' || $trans['accion_codigo'] === 'RECHAZAR' || $trans['requiere_comentario']) && empty($motivo)) {
                throw new Exception("Debe ingresar un motivo o comentario para ejecutar esta acción.");
            }

            $nuevo_destino = ejecutar_transicion_por_id($pdo, $id, $user_id, $transicion_id, $motivo);
            $stmtNd = $pdo->prepare("SELECT nombre FROM estados_tramite WHERE codigo = ?");
            $stmtNd->execute([$nuevo_destino]);
            $nombre_dest = $stmtNd->fetchColumn();
            
            $mensaje = "Acción '" . htmlspecialchars($trans['accion_label']) . "' procesada correctamente. Solicitud enviada a: $nombre_dest.";
            $tipo_mensaje = ($trans['accion_codigo'] === 'APROBAR') ? 'success' : (($trans['accion_codigo'] === 'DEVOLVER') ? 'warning' : 'error');

        } elseif ($accion === 'aprobar') {
            $nuevo_destino = avanzar_flujo($pdo, $id, $user_id, "V°B° Jefatura completado.");
            $stmtNd = $pdo->prepare("SELECT nombre FROM estados_tramite WHERE codigo = ?");
            $stmtNd->execute([$nuevo_destino]);
            $nombre_dest = $stmtNd->fetchColumn();
            $mensaje = "Solicitud aprobada correctamente. Avanzó a: $nombre_dest.";
            $tipo_mensaje = "success";
        } elseif ($accion === 'devolver') {
            $motivo = trim($_POST['motivo_rechazo']);
            if (empty($motivo)) throw new Exception("Debe ingresar un motivo para la devolución.");
            devolver_flujo($pdo, $id, $user_id, "Devuelto para corrección: " . $motivo);
            $mensaje = "Solicitud devuelta al creador para corrección.";
            $tipo_mensaje = "warning";
        } elseif ($accion === 'rechazar') {
            $motivo = trim($_POST['motivo_rechazo']);
            if (empty($motivo)) throw new Exception("Debe ingresar un motivo para el rechazo.");
            rechazar_flujo($pdo, $id, $user_id, "Rechazado definitivamente: " . $motivo);
            $mensaje = "Solicitud rechazada y cerrada.";
            $tipo_mensaje = "error"; // Visualmente rojo
        }

        $pdo->commit();
        $vista = 'lista';

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// =====================================================================
// CONSULTAS GET (CARGA DE VISTAS)
// =====================================================================
if ($vista === 'revisar' && isset($_GET['id'])) {
    
    $sql = "
        SELECT e.*, u.nombre_completo as solicitante, un.nombre as unidad, cc.nombre as centro_costo, p.nombre as prioridad_nom, p.clase_css, tc.nombre as tipo_compra_nom, tc.codigo as tipo_compra_cod, prov.razon_social as proveedor_nombre, prov.rut as proveedor_rut
        FROM expedientes e 
        JOIN usuarios u ON e.usuario_creador_id = u.id 
        JOIN unidades un ON e.unidad_origen_id = un.id
        JOIN centros_costo cc ON e.centro_costo_id = cc.id 
        JOIN prioridades p ON e.prioridad_id = p.id 
        JOIN tipos_compra tc ON e.tipo_compra_id = tc.id 
        LEFT JOIN proveedores prov ON e.proveedor_adjudicado_id = prov.id
        WHERE e.id = ?
    ";
    if ($rol !== 'ADMIN_MUNICIPAL') { $sql .= " AND e.unidad_origen_id = $unidad_id"; }
    $stmt = $pdo->prepare($sql); 
    $stmt->execute([$_GET['id']]); 
    $exp = $stmt->fetch();
    
    if (!$exp) die("Expediente no encontrado o sin acceso.");
    
    // Ítems con su cuenta presupuestaria
    $stmtItems = $pdo->prepare("
        SELECT ei.*, cm.codigo as cuenta_codigo 
        FROM expedientes_items ei 
        LEFT JOIN presupuestos_asignados pa ON ei.presupuesto_asignado_id = pa.id 
        LEFT JOIN cuentas_maestras cm ON pa.cuenta_maestra_id = cm.id 
        WHERE ei.expediente_id = ?
    "); 
    $stmtItems->execute([$_GET['id']]); 
    $items = $stmtItems->fetchAll();

    // Criterios (Solo si es Licitación)
    $stmtCrit = $pdo->prepare("SELECT * FROM expedientes_criterios WHERE expediente_id = ? ORDER BY numero_criterio ASC");
    $stmtCrit->execute([$_GET['id']]);
    $criterios = $stmtCrit->fetchAll(PDO::FETCH_ASSOC);
    
    $stmtDocs = $pdo->prepare("SELECT * FROM expedientes_documentos WHERE expediente_id = ? ORDER BY fecha_subida DESC"); 
    $stmtDocs->execute([$_GET['id']]); 
    $docs = $stmtDocs->fetchAll();

} else {
    // Bandeja de Entrada Normal
    $sql = "
        SELECT e.*, u.nombre_completo as solicitante, p.nombre as prioridad_nom, p.clase_css, tc.nombre as tipo_compra_nom
        FROM expedientes e 
        JOIN usuarios u ON e.usuario_creador_id = u.id 
        JOIN prioridades p ON e.prioridad_id = p.id 
        JOIN tipos_compra tc ON e.tipo_compra_id = tc.id
        WHERE e.estado_actual = 'EN_REVISION_JEFATURA'
    ";
    if ($rol !== 'ADMIN_MUNICIPAL') { $sql .= " AND e.unidad_origen_id = $unidad_id"; }
    $sql .= " ORDER BY p.id DESC, e.created_at ASC";
    
    $pendientes = $pdo->query($sql)->fetchAll();
}

function money($v) { return '$ ' . number_format($v, 0, ',', '.'); }
?>