<?php
// imprimir_opi.php - Despachador Oficial del Documento OPI (Modelo Lebu N° 758 en PDF)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/pdf_helper.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) die("Acceso denegado.");

$id = $_GET['id'] ?? null;
if (!$id) die("ID no especificado.");

$auto_download = isset($_GET['auto_download']) && $_GET['auto_download'] == '1';

// 1. Buscar si ya existe una versión firmada en expedientes_documentos
$stmtDoc = $pdo->prepare("SELECT ruta_archivo FROM expedientes_documentos WHERE expediente_id = ? AND tipo_doc = 'OPI_FIRMADA_PDF' ORDER BY id DESC LIMIT 1");
$stmtDoc->execute([$id]);
$doc_db = $stmtDoc->fetchColumn();

$ruta_pdf_rel = null;
if ($doc_db && file_exists(__DIR__ . '/' . $doc_db)) {
    $ruta_pdf_rel = $doc_db;
} else {
    // Generar el PDF oficial base con todos los metadatos institucionales
    $ruta_pdf_rel = generar_pdf_base_opi($pdo, (int)$id);
}

$ruta_pdf_abs = __DIR__ . '/' . $ruta_pdf_rel;
if (!file_exists($ruta_pdf_abs)) {
    die("Error al generar el documento OPI.");
}

// 2. Despachar el archivo PDF con cabeceras nativas
$nombre_descarga = basename($ruta_pdf_abs);
$disposition = $auto_download ? 'attachment' : 'inline';

if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/pdf');
header('Content-Disposition: ' . $disposition . '; filename="' . $nombre_descarga . '"');
header('Content-Length: ' . filesize($ruta_pdf_abs));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($ruta_pdf_abs);
exit;