<?php
// subir_adjunto_ajax.php - Endpoint AJAX para pre-subida de adjuntos con progreso
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Validar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Sesión no válida o expirada.']);
    exit;
}

// 2. Validar token CSRF (vía POST o Header X-CSRF-TOKEN)
$csrf_received = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf_received)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Error de validación de seguridad CSRF.']);
    exit;
}

// 3. Validar presencia de archivo
if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    $err_code = $_FILES['archivo']['error'] ?? UPLOAD_ERR_NO_FILE;
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => "Error en la transmisión del archivo (Código $err_code)."]);
    exit;
}

$file = $_FILES['archivo'];

try {
    // 4. Validar extensión y tamaño usando la función centralizada de config.php
    $ext = validar_subida_archivo($file);

    // 5. Crear directorio temporal para la sesión actual
    $session_dir_name = md5(session_id() . 'opi_salt');
    $tmp_dir = __DIR__ . '/uploads/tmp/' . $session_dir_name . '/';
    if (!file_exists($tmp_dir)) {
        if (!mkdir($tmp_dir, 0777, true)) {
            throw new Exception("No se pudo crear el directorio de carga temporal.");
        }
    }

    // 6. Generar nombre de archivo temporal único y seguro
    $temp_id = 'tmp_' . uniqid() . '_' . time() . '.' . $ext;
    $dest_path = $tmp_dir . $temp_id;

    if (!move_uploaded_file($file['tmp_name'], $dest_path)) {
        throw new Exception("No se pudo guardar el archivo temporal en el servidor.");
    }

    $rel_path = 'uploads/tmp/' . $session_dir_name . '/' . $temp_id;

    echo json_encode([
        'success' => true,
        'temp_id' => $temp_id,
        'nombre_original' => $file['name'],
        'ruta_temp' => $rel_path,
        'size_bytes' => $file['size'],
        'ext' => $ext
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
