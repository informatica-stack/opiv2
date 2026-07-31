<?php
// trazabilidad_ajax.php - Controlador AJAX de Trazabilidad
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

// 1. SEGURIDAD DE SESIÓN
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado. Inicie sesión nuevamente.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_rol = $_SESSION['user_rol'] ?? '';
$user_unidad = $_SESSION['user_unidad'] ?? null;

// 2. PARÁMETROS DE ENTRADA
$expediente_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$expediente_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de expediente inválido o no provisto.']);
    exit;
}

try {
    // 3. CARGAR EXPEDIENTE Y COMPROBAR EXISTENCIA
    $stmtExp = $pdo->prepare("SELECT id, codigo_interno, usuario_creador_id, unidad_origen_id, estado_actual, titulo_compra FROM expedientes WHERE id = ?");
    $stmtExp->execute([$expediente_id]);
    $exp = $stmtExp->fetch();

    if (!$exp) {
        http_response_code(404);
        echo json_encode(['error' => 'El requerimiento especificado no existe.']);
        exit;
    }

    // 4. CONTROL DE ACCESOS POR ROL
    $tiene_acceso = false;

    // Roles administrativos / visadores centrales tienen acceso transversal
    if (in_array($user_rol, ['SYSADMIN', 'ADMIN_MUNICIPAL', 'PRESUPUESTO', 'FINANZAS', 'ADQUISICIONES'])) {
        $tiene_acceso = true;
    } 
    // Jefes de unidad ven solicitudes de su misma unidad
    elseif ($user_rol === 'JEFE_UNIDAD') {
        if ($exp['unidad_origen_id'] == $user_unidad) {
            $tiene_acceso = true;
        }
    } 
    // Usuarios corrientes solo ven sus propias solicitudes
    else {
        if ($exp['usuario_creador_id'] == $user_id) {
            $tiene_acceso = true;
        }
    }

    if (!$tiene_acceso) {
        http_response_code(403);
        echo json_encode(['error' => 'No tiene permisos para ver la trazabilidad de este expediente.']);
        exit;
    }

    // 5. OBTENER EL HISTORIAL ORDENADO CRONOLÓGICAMENTE
    $sqlHist = "
        SELECT 
            eh.id,
            eh.accion,
            eh.estado_anterior,
            eh.estado_nuevo,
            eh.comentario,
            eh.fecha_accion,
            u.nombre_completo as usuario_nombre,
            r.descripcion as rol_nombre
        FROM expedientes_historial eh
        JOIN usuarios u ON eh.usuario_id = u.id
        JOIN roles r ON u.rol_id = r.id
        WHERE eh.expediente_id = ?
        ORDER BY eh.fecha_accion ASC
    ";
    
    $stmtHist = $pdo->prepare($sqlHist);
    $stmtHist->execute([$expediente_id]);
    $historial = $stmtHist->fetchAll();

    // 6. ENVIAR RESPUESTA EXITOSA
    echo json_encode([
        'codigo_interno' => $exp['codigo_interno'],
        'titulo_compra' => $exp['titulo_compra'],
        'estado_actual' => $exp['estado_actual'],
        'historial' => $historial
    ]);

} catch (Exception $e) {
    error_log("Error en trazabilidad_ajax.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error de servidor al cargar la trazabilidad.']);
}
?>
