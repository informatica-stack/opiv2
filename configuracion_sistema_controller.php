<?php
// configuracion_sistema_controller.php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['user_rol'] ?? '';
if ($rol !== 'SYSADMIN' && $rol !== 'ADMIN_MUNICIPAL') {
    die("Acceso Denegado. Módulo exclusivo para Administración del Sistema.");
}

// Asegurar existencia de la tabla configuraciones_sistema
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `configuraciones_sistema` (
        `clave` varchar(50) NOT NULL,
        `valor` text DEFAULT NULL,
        PRIMARY KEY (`clave`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    error_log("Error al verificar/crear tabla configuraciones_sistema: " . $e->getMessage());
}

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $limite_peso = max(1, min(200, intval($_POST['limite_peso_adjunto_mb'] ?? 10)));
        $valor_utm = max(1, floatval($_POST['valor_utm'] ?? 66000));
        $modo_mant = ($_POST['modo_mantenimiento'] ?? '0') === '1' ? '1' : '0';
        $ext_input = trim($_POST['extensiones_permitidas'] ?? 'pdf,zip,rar,doc,docx,xls,xlsx,jpg,jpeg,png');

        // Limpiar lista de extensiones (quitar puntos, espacios y convertir a minúsculas)
        $ext_array = array_filter(array_map(function($item) {
            return strtolower(ltrim(trim($item), '.'));
        }, explode(',', $ext_input)));
        $ext_clean = implode(',', array_unique($ext_array));

        $modo_firmagob = (strtoupper(trim($_POST['firmagob_modo'] ?? 'ATENDIDA')) === 'DESATENDIDA') ? 'DESATENDIDA' : 'ATENDIDA';
        $entity_firmagob = trim($_POST['firmagob_entity'] ?? 'Ilustre Municipalidad de Lebu');
        $purpose_firmagob = trim($_POST['firmagob_purpose'] ?? 'Propósito General');
        $ambiente_firmagob = (strtoupper(trim($_POST['firmagob_ambiente'] ?? 'PRODUCCION')) === 'CERTIFICACION') ? 'CERTIFICACION' : 'PRODUCCION';
        $api_url_firmagob = ($ambiente_firmagob === 'CERTIFICACION') 
            ? 'https://api.firma.cert.digital.gob.cl/firma/v2/files/tickets' 
            : 'https://api.firma.digital.gob.cl/firma/v2/files/tickets';

        $params = [
            'limite_peso_adjunto_mb' => (string)$limite_peso,
            'valor_utm'              => (string)$valor_utm,
            'modo_mantenimiento'     => $modo_mant,
            'extensiones_permitidas' => $ext_clean,
            'firmagob_modo'          => $modo_firmagob,
            'firmagob_entity'        => $entity_firmagob,
            'firmagob_purpose'       => $purpose_firmagob,
            'firmagob_ambiente'      => $ambiente_firmagob,
            'firmagob_api_url'       => $api_url_firmagob
        ];

        $stmtSave = $pdo->prepare("INSERT INTO configuraciones_sistema (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
        foreach ($params as $k => $v) {
            $stmtSave->execute([$k, $v]);
        }

        $mensaje = "Configuraciones del sistema y parámetros de FirmaGob actualizados exitosamente.";
        $tipo_mensaje = "success";

    } catch (Exception $e) {
        $mensaje = "Error al guardar las configuraciones: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// Cargar configuraciones actuales
$configs = [
    'limite_peso_adjunto_mb' => '10',
    'valor_utm'              => '66000',
    'modo_mantenimiento'     => '0',
    'extensiones_permitidas' => 'pdf,zip,rar,doc,docx,xls,xlsx,jpg,jpeg,png',
    'firmagob_modo'          => FIRMAGOB_MODO,
    'firmagob_entity'        => FIRMAGOB_ENTITY,
    'firmagob_purpose'       => FIRMAGOB_PURPOSE,
    'firmagob_ambiente'      => FIRMAGOB_AMBIENTE,
    'firmagob_api_url'       => FIRMAGOB_API_URL
];

try {
    $stmt = $pdo->query("SELECT clave, valor FROM configuraciones_sistema");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $configs[$row['clave']] = $row['valor'];
    }
} catch (Exception $e) {
    // Usar valores predeterminados en caso de error
}

// Sincronizar las variables de FirmaGob con las constantes activas (que priorizan Dokploy / .env)
$configs['firmagob_modo']     = FIRMAGOB_MODO;
$configs['firmagob_entity']   = FIRMAGOB_ENTITY;
$configs['firmagob_purpose']  = FIRMAGOB_PURPOSE;
$configs['firmagob_ambiente'] = FIRMAGOB_AMBIENTE;
$configs['firmagob_api_url']  = FIRMAGOB_API_URL;
?>
