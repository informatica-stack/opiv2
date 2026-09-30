<?php
// firmador_institucional_controller.php - Controlador Backend Efímero para Firma al Paso
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/firmagob_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// 1. Control de Autenticación y Autorización por Roles
$rol_usuario = $_SESSION['user_rol'] ?? '';
$es_jefe = intval($_SESSION['es_jefe'] ?? 0);
$roles_permitidos = ['JEFE_UNIDAD', 'ADMIN_MUNICIPAL', 'SYSADMIN', 'FINANZAS', 'PRESUPUESTO'];

$es_autorizado = in_array($rol_usuario, $roles_permitidos) || ($es_jefe === 1);

if (!isset($_SESSION['user_id']) || !$es_autorizado) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error'   => 'Acceso denegado. Este módulo es exclusivo para Jefaturas, Administrador Municipal, Finanzas, Presupuesto y Sysadmin.'
    ]);
    exit;
}

$action = $_REQUEST['action'] ?? '';

// Función auxiliar para formatear tamaño de bytes a texto legible
function firmador_formato_tamano($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' B';
}

// -------------------------------------------------------------------------
// ACCIÓN 1: SUBIR DOCUMENTO PDF TEMPORAL (EN MEMORIA / TEMP VOLÁTIL)
// -------------------------------------------------------------------------
if ($action === 'subir_documento') {
    try {
        if (!isset($_FILES['archivo_pdf']) || $_FILES['archivo_pdf']['error'] !== UPLOAD_ERR_OK) {
            $err_code = $_FILES['archivo_pdf']['error'] ?? 'DESCONOCIDO';
            throw new Exception("Error al cargar el archivo PDF (Código: $err_code).");
        }

        $file = $_FILES['archivo_pdf'];
        $filesize = $file['size'];
        $original_name = basename($file['name']);

        // Validación de tamaño máximo (5 MB por límite de FirmaGob)
        if ($filesize > 5 * 1024 * 1024) {
            throw new Exception("El archivo excede el tamaño máximo permitido por FirmaGob (5 MB). Su archivo pesa: " . firmador_formato_tamano($filesize));
        }

        // Validación de extensión y contenido binario
        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            throw new Exception("Solo se admiten documentos en formato PDF (.pdf).");
        }

        $pdf_content = file_get_contents($file['tmp_name']);
        if (substr($pdf_content, 0, 4) !== '%PDF') {
            throw new Exception("El archivo no es un documento PDF válido o está corrupto.");
        }

        $checksum_sha256 = hash('sha256', $pdf_content);

        // Carpeta temporal dedicada con permisos restringidos
        $temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'firmador_inst_tmp';
        if (!is_dir($temp_dir)) {
            @mkdir($temp_dir, 0700, true);
        }

        $token_doc = bin2hex(random_bytes(16));
        $temp_filepath = $temp_dir . DIRECTORY_SEPARATOR . 'doc_' . $token_doc . '.pdf';

        if (!move_uploaded_file($file['tmp_name'], $temp_filepath)) {
            // Fallback directo a file_put_contents si move_uploaded_file fallara
            if (file_put_contents($temp_filepath, $pdf_content) === false) {
                throw new Exception("No se pudo almacenar temporalmente el documento en el servidor.");
            }
        }

        // Registrar en sesión para control de pertenencia
        if (!isset($_SESSION['firmador_institucional_temp'])) {
            $_SESSION['firmador_institucional_temp'] = [];
        }

        $_SESSION['firmador_institucional_temp'][$token_doc] = [
            'filepath'       => $temp_filepath,
            'original_name'  => $original_name,
            'filesize'       => $filesize,
            'sha256'         => $checksum_sha256,
            'uploaded_at'    => time()
        ];

        echo json_encode([
            'success'        => true,
            'token_doc'      => $token_doc,
            'nombre'         => $original_name,
            'tamano_bytes'   => $filesize,
            'tamano_formato' => firmador_formato_tamano($filesize),
            'checksum'       => $checksum_sha256
        ]);
        exit;

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error'   => $e->getMessage()
        ]);
        exit;
    }
}

// -------------------------------------------------------------------------
// ACCIÓN 2: OBTENER PDF TEMPORAL PARA VISOR PDF.JS
// -------------------------------------------------------------------------
if ($action === 'obtener_preview_pdf') {
    $token_doc = $_GET['token_doc'] ?? '';
    $temp_info = $_SESSION['firmador_institucional_temp'][$token_doc] ?? null;

    if (!$temp_info || !file_exists($temp_info['filepath'])) {
        http_response_code(404);
        header('Content-Type: text/plain');
        die("Documento temporal no encontrado o expirado.");
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . addslashes($temp_info['original_name']) . '"');
    header('Content-Length: ' . filesize($temp_info['filepath']));
    header('Cache-Control: private, max-age=0, no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    readfile($temp_info['filepath']);
    exit;
}

// -------------------------------------------------------------------------
// ACCIÓN 3: FIRMAR DOCUMENTO (LIBRE VISIBLE O INVISIBLE)
// -------------------------------------------------------------------------
if ($action === 'firmar_documento') {
    try {
        $token_doc = $_POST['token_doc'] ?? '';
        $temp_info = $_SESSION['firmador_institucional_temp'][$token_doc] ?? null;

        if (!$temp_info || !file_exists($temp_info['filepath'])) {
            throw new Exception("El documento temporal ha expirado o no existe. Por favor vuelva a cargarlo.");
        }

        $filepath = $temp_info['filepath'];
        $original_name = $temp_info['original_name'];
        $filesize = $temp_info['filesize'];
        $checksum_original = $temp_info['sha256'];

        $tipo_firma = strtoupper(trim($_POST['tipo_firma'] ?? 'VISIBLE'));
        if (!in_array($tipo_firma, ['VISIBLE', 'INVISIBLE'])) {
            $tipo_firma = 'VISIBLE';
        }

        $otp = !empty($_POST['otp']) ? trim($_POST['otp']) : null;
        $descripcion = !empty($_POST['descripcion']) ? trim($_POST['descripcion']) : ("Firma Institucional al Paso: " . $original_name);

        // Coordenadas si es visible
        $coordenadas = null;
        $pagina_firmada = null;
        $llx = null;
        $lly = null;
        $urx = null;
        $ury = null;

        if ($tipo_firma === 'VISIBLE') {
            $pagina_firmada = isset($_POST['page']) ? intval($_POST['page']) : 1;
            if ($pagina_firmada < 1) $pagina_firmada = 1;

            $llx = floatval($_POST['llx'] ?? 40);
            $lly = floatval($_POST['lly'] ?? 50);
            $urx = floatval($_POST['urx'] ?? 210);
            $ury = floatval($_POST['ury'] ?? 130);

            $coordenadas = [
                'page' => $pagina_firmada,
                'llx'  => $llx,
                'lly'  => $lly,
                'urx'  => $urx,
                'ury'  => $ury
            ];
        }

        // Resolver firmante y subrogancia
        $firmar_como = $_POST['firmar_como'] ?? 'TITULAR';
        $es_subrogante_sesion = !empty($_SESSION['es_subrogante']);

        $usuario_id = $_SESSION['user_id'];
        $es_subrogante_final = 0;
        $subrogado_nombre_final = null;

        if ($firmar_como === 'SUBROGANTE' && $es_subrogante_sesion) {
            $run_firmante = $_SESSION['user_rut'];
            $nombre_firmante = ($_SESSION['user_name'] ?? 'Funcionario') . ' (S)';
            $cargo_firmante = $_SESSION['subrogado_cargo'] ?? (($_SESSION['user_cargo'] ?? 'Funcionario') . ' (S)');
            $es_subrogante_final = 1;
            $subrogado_nombre_final = $_SESSION['subrogado_nombre'] ?? null;
        } else {
            $run_firmante = $_SESSION['user_rut'] ?? '';
            $nombre_firmante = $_SESSION['user_name'] ?? ($_SESSION['user_nombre'] ?? 'Funcionario Autorizado');
            $cargo_firmante = $_SESSION['user_cargo'] ?? ($_SESSION['user_rol'] ?? 'Funcionario Autorizado');
        }

        // Ejecutar firma con FirmaGob v2 mediante firmagob_helper.php
        $resultado_firma = firmagob_firmar_documento_libre(
            $filepath,
            $run_firmante,
            $descripcion,
            $coordenadas,
            $otp,
            $nombre_firmante,
            $cargo_firmante
        );

        if (!$resultado_firma || empty($resultado_firma['content_binary'])) {
            throw new Exception("FirmaGob no retornó el contenido binario del documento firmado.");
        }

        $signed_binary = $resultado_firma['content_binary'];
        $checksum_signed = $resultado_firma['checksum_signed'] ?? hash('sha256', $signed_binary);
        $id_solicitud = $resultado_firma['id_solicitud'] ?? null;

        // CICLO DE VIDA EFÍMERO: Eliminar de inmediato el archivo temporal subido
        if (file_exists($filepath)) {
            @unlink($filepath);
        }
        unset($_SESSION['firmador_institucional_temp'][$token_doc]);

        // Registrar trazabilidad y auditoría legal en la base de datos
        try {
            $ip_origen = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
            $modo_firma = FIRMAGOB_MODO;

            $stmtAudit = $pdo->prepare("
                INSERT INTO firmas_al_paso_auditoria (
                    usuario_id, run_firmante, nombre_firmante, cargo_firmante,
                    es_subrogante, subrogado_nombre, nombre_archivo_original,
                    tamano_bytes, checksum_original_sha256, checksum_firmado_sha256,
                    firmagob_id_solicitud, tipo_firma, modo_firma, pagina_firmada,
                    coord_llx, coord_lly, coord_urx, coord_ury, ip_origen, user_agent
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmtAudit->execute([
                $usuario_id,
                $run_firmante,
                $nombre_firmante,
                $cargo_firmante,
                $es_subrogante_final,
                $subrogado_nombre_final,
                $original_name,
                $filesize,
                $checksum_original,
                $checksum_signed,
                $id_solicitud,
                $tipo_firma,
                $modo_firma,
                $pagina_firmada,
                $llx,
                $lly,
                $urx,
                $ury,
                $ip_origen,
                $user_agent
            ]);

        } catch (Exception $exAudit) {
            // Error de auditoría no bloquea la entrega del PDF firmado al usuario, pero se registra en logs
            error_log("Error al registrar auditoría de firma al paso: " . $exAudit->getMessage());
        }

        // Preparar token de descarga efímero en sesión
        if (!isset($_SESSION['firmador_descargas'])) {
            $_SESSION['firmador_descargas'] = [];
        }

        // Purgar descargas de más de 30 minutos de antigüedad en la sesión
        $tiempo_limite = time() - 1800;
        foreach ($_SESSION['firmador_descargas'] as $k => $item) {
            if (($item['created_at'] ?? 0) < $tiempo_limite) {
                unset($_SESSION['firmador_descargas'][$k]);
            }
        }

        $token_descarga = bin2hex(random_bytes(16));
        $signed_filename = pathinfo($original_name, PATHINFO_FILENAME) . '_firmado.pdf';

        $_SESSION['firmador_descargas'][$token_descarga] = [
            'binary'     => $signed_binary,
            'filename'   => $signed_filename,
            'created_at' => time()
        ];

        echo json_encode([
            'success'           => true,
            'token_descarga'    => $token_descarga,
            'nombre_archivo'    => $signed_filename,
            'id_solicitud'      => $id_solicitud,
            'checksum_firmado'  => $checksum_signed,
            'checksum_original' => $checksum_original,
            'tamano_firmado'    => firmador_formato_tamano(strlen($signed_binary)),
            'mensaje'           => '¡Documento firmado exitosamente con FirmaGob!'
        ]);
        exit;

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error'   => $e->getMessage()
        ]);
        exit;
    }
}

// -------------------------------------------------------------------------
// ACCIÓN 4: DESCARGAR DOCUMENTO FIRMADO EFÍMERO
// -------------------------------------------------------------------------
if ($action === 'descargar_firmado') {
    $token = $_GET['token'] ?? '';
    $descarga = $_SESSION['firmador_descargas'][$token] ?? null;

    if (!$descarga || empty($descarga['binary'])) {
        http_response_code(404);
        header('Content-Type: text/plain');
        die("El enlace de descarga ha expirado o no es válido.");
    }

    $binary = $descarga['binary'];
    $filename = $descarga['filename'] ?? 'documento_firmado.pdf';

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
    header('Content-Length: ' . strlen($binary));
    header('Cache-Control: private, max-age=0, no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo $binary;
    exit;
}

// -------------------------------------------------------------------------
// ACCIÓN 5: PREVISUALIZAR DOCUMENTO FIRMADO EN PANTALLA
// -------------------------------------------------------------------------
if ($action === 'preview_firmado') {
    $token = $_GET['token'] ?? '';
    $descarga = $_SESSION['firmador_descargas'][$token] ?? null;

    if (!$descarga || empty($descarga['binary'])) {
        http_response_code(404);
        header('Content-Type: text/plain');
        die("La vista previa ha expirado o no es válida.");
    }

    $binary = $descarga['binary'];
    $filename = $descarga['filename'] ?? 'documento_firmado.pdf';

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . addslashes($filename) . '"');
    header('Content-Length: ' . strlen($binary));
    header('Cache-Control: private, max-age=0, no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    echo $binary;
    exit;
}

// -------------------------------------------------------------------------
// ACCIÓN 6: CANCELAR O PURGAR ARCHIVO TEMPORAL
// -------------------------------------------------------------------------
if ($action === 'cancelar_temporal') {
    $token_doc = $_POST['token_doc'] ?? '';
    if (!empty($token_doc) && isset($_SESSION['firmador_institucional_temp'][$token_doc])) {
        $filepath = $_SESSION['firmador_institucional_temp'][$token_doc]['filepath'];
        if (file_exists($filepath)) {
            @unlink($filepath);
        }
        unset($_SESSION['firmador_institucional_temp'][$token_doc]);
    }
    echo json_encode(['success' => true]);
    exit;
}

// Acción por defecto no reconocida
http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Acción no especificada o no válida.']);
exit;
