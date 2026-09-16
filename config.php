<?php
// config.php
// Configuración general y conexión a la Base de Datos

// 1. Configuración de Entorno
date_default_timezone_set('America/Santiago');
setlocale(LC_TIME, 'es_CL.UTF-8', 'es_ES.UTF-8');

// Iniciar sesión de manera segura si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1.1 Protección CSRF - Inicialización de Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 1.2 Validación de Token CSRF en peticiones POST
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die("Error de Seguridad (CSRF): La solicitud no pudo ser validada. Por favor, recargue la página e intente nuevamente.");
    }
}

// 1.3 Carga opcional de archivo .env si existe en la raíz
if (file_exists(__DIR__ . '/.env')) {
    $env_lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($env_k, $env_v) = explode('=', $line, 2);
            $env_k = trim($env_k);
            $env_v = trim($env_v, " \t\n\r\0\x0B\"'");
            if (!empty($env_k)) {
                putenv("$env_k=$env_v");
                $_ENV[$env_k] = $env_v;
                $_SERVER[$env_k] = $env_v;
            }
        }
    }
}

// Función helper para obtener variable de entorno limpia de espacios o comillas
function obtener_env($clave, $defecto = '') {
    $val = getenv($clave);
    if ($val === false || $val === null || $val === '') {
        $val = $_ENV[$clave] ?? ($_SERVER[$clave] ?? $defecto);
    }
    return is_string($val) ? trim($val, " \t\n\r\0\x0B\"'") : $val;
}

// 2. Credenciales de Base de Datos (Variables de Entorno Dockploy con Fallback Local)
define('DB_HOST', obtener_env('DB_HOST', 'localhost'));
define('DB_NAME', obtener_env('DB_NAME', 'db_municipal_opi'));
define('DB_USER', obtener_env('DB_USER', 'root'));
define('DB_PASS', obtener_env('DB_PASS', ''));
define('DB_CHARSET', obtener_env('DB_CHARSET', 'utf8mb4'));

// 3. Conexión PDO (Segura)
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => true,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (\PDOException $e) {
    // En producción, no mostrar el error real al usuario, solo loguearlo
    error_log($e->getMessage());
    die("Error crítico de conexión: No se pudo contactar con el servidor de base de datos.");
}

// 3.1 Cargar configuraciones globales desde la base de datos
try {
    $stmtCfg = $pdo->query("SELECT clave, valor FROM configuraciones_sistema");
    $config_sistema = [];
    while ($row = $stmtCfg->fetch(PDO::FETCH_ASSOC)) {
        $config_sistema[$row['clave']] = $row['valor'];
    }
} catch (Exception $e) {
    $config_sistema = [
        'modo_mantenimiento' => '0',
        'valor_utm' => '66000',
        'limite_peso_adjunto_mb' => '10'
    ];
}

define('VALOR_UTM', floatval($config_sistema['valor_utm'] ?? 66000));
define('LIMITE_ADJUNTO_MB', intval($config_sistema['limite_peso_adjunto_mb'] ?? 10));

if (php_sapi_name() !== 'cli' && ($config_sistema['modo_mantenimiento'] ?? '0') === '1') {
    $self = basename($_SERVER['PHP_SELF']);
    if ($self !== 'login.php' && $self !== 'mantenimiento.php' && $self !== 'logout.php') {
        $rol_verif = $_SESSION['user_rol'] ?? '';
        if ($rol_verif !== 'SYSADMIN') {
            header("Location: mantenimiento.php");
            exit;
        }
    }
}

// 4. Constantes del Sistema (Estados del Flujo)
define('ESTADO_BORRADOR', 'BORRADOR');
define('ESTADO_REV_JEFE', 'EN_REVISION_JEFATURA');
define('ESTADO_VAL_PRESUPUESTO', 'EN_VALIDACION_PRESUPUESTARIA');
define('ESTADO_AUT_COTIZACION', 'EN_AUTORIZACION_COTIZACION');
define('ESTADO_GESTION_COMPRA', 'EN_GESTION_ADQUISICIONES');
define('ESTADO_SELECCION', 'EN_EVALUACION_OFERTAS');
define('ESTADO_FIRMA_JEFATURA', 'EN_FIRMA_JEFATURA');
define('ESTADO_VAL_PRESUPUESTO_FINAL', 'EN_VALIDACION_PRESUPUESTARIA_FINAL');
define('ESTADO_CDP_FINANZAS_FINAL', 'ESPERANDO_CDP_FINANZAS_FINAL');
define('ESTADO_APROB_ADM', 'EN_APROBACION_ADMINISTRADOR');
define('ESTADO_OPI_EMITIDA', 'EN_EMISION_OC');
define('ESTADO_FINALIZADO', 'FINALIZADO');
define('ESTADO_CORRECCION', 'EN_CORRECCION');
define('ESTADO_ANULADO', 'ANULADO');
define('ESTADO_RECHAZADO', 'RECHAZADO');

// 5. Configuración de Firma Digital FirmaGob (Gobierno Digital)
// Prioridad: Base de Datos (guardada desde Configuración del Sistema) -> Variables de Entorno (Dokploy / .env) -> Valor por Defecto
$modo_cfg = !empty($config_sistema['firmagob_modo']) ? $config_sistema['firmagob_modo'] : obtener_env('FIRMAGOB_MODO', 'ATENDIDA');
define('FIRMAGOB_MODO', strtoupper(trim((string)$modo_cfg)) === 'DESATENDIDA' ? 'DESATENDIDA' : 'ATENDIDA');

$ambiente_cfg = !empty($config_sistema['firmagob_ambiente']) ? $config_sistema['firmagob_ambiente'] : obtener_env('FIRMAGOB_AMBIENTE', 'PRODUCCION');
define('FIRMAGOB_AMBIENTE', $ambiente_cfg);

$api_url_cfg = !empty($config_sistema['firmagob_api_url']) ? $config_sistema['firmagob_api_url'] : obtener_env('FIRMAGOB_API_URL', 'https://api.firma.digital.gob.cl/firma/v2/files/tickets');
define('FIRMAGOB_API_URL', $api_url_cfg);

$entity_cfg = !empty($config_sistema['firmagob_entity']) ? $config_sistema['firmagob_entity'] : obtener_env('FIRMAGOB_ENTITY', 'Ilustre Municipalidad de Lebu');
define('FIRMAGOB_ENTITY', $entity_cfg);

// En modo desatendido el propósito siempre es 'Desatendido'; en atendida es 'Propósito General' o el configurado
if (FIRMAGOB_MODO === 'DESATENDIDA') {
    $purpose_cfg = 'Desatendido';
} else {
    $purpose_cfg = !empty($config_sistema['firmagob_purpose']) ? $config_sistema['firmagob_purpose'] : obtener_env('FIRMAGOB_PURPOSE', 'Propósito General');
}
define('FIRMAGOB_PURPOSE', $purpose_cfg);

// Credenciales secretas leídas desde Dokploy / .env (sin datos sensibles en el código fuente)
define('FIRMAGOB_API_TOKEN_KEY', obtener_env('FIRMAGOB_API_TOKEN_KEY', ''));
define('FIRMAGOB_SECRET', obtener_env('FIRMAGOB_SECRET', ''));

// 6. Función helper para URLs (opcional)
function base_url($path = '') {
    // Ajusta esto a tu carpeta real, ej: /sistema-opi/
    return "/sistema_opi/" . ltrim($path, '/');
}

/**
 * Valida un archivo subido contra tipos permitidos y tamaño límite.
 * Retorna la extensión limpia o lanza una excepción en caso de error.
 */
function validar_subida_archivo($file, $index = null, $allowed_exts = ['pdf', 'zip', 'rar', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png']) {
    $error = ($index !== null) ? $file['error'][$index] : $file['error'];
    if ($error !== UPLOAD_ERR_OK) {
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        throw new Exception("Error al subir el archivo (Código de error: $error).");
    }

    $name = ($index !== null) ? $file['name'][$index] : $file['name'];
    $size = ($index !== null) ? $file['size'][$index] : $file['size'];

    // 1. Validar extensión (whitelist)
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_exts)) {
        throw new Exception("Formato de archivo no permitido. Solo se aceptan: " . implode(', ', $allowed_exts));
    }

    // 2. Validar tamaño límite
    $limite_bytes = LIMITE_ADJUNTO_MB * 1024 * 1024;
    if ($size > $limite_bytes) {
        throw new Exception("El archivo excede el tamaño máximo permitido de " . LIMITE_ADJUNTO_MB . " MB.");
    }

    return $ext;
}

if (!function_exists('color_estado')) {
    function color_estado($estado_codigo) {
        if (in_array($estado_codigo, ['BORRADOR', 'EN_REVISION_JEFATURA'])) return 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle';
        if (in_array($estado_codigo, ['RECHAZADO', 'ANULADO'])) return 'bg-danger-subtle text-danger-emphasis text-decoration-line-through border border-danger-subtle';
        if ($estado_codigo === 'FINALIZADO') return 'bg-success-subtle text-success-emphasis fw-bold border border-success-subtle';
        if (in_array($estado_codigo, ['EN_COTIZACION_ADQ', 'EN_GESTION_ADQUISICIONES'])) return 'bg-info-subtle text-info-emphasis fw-bold border border-info-subtle';
        if ($estado_codigo === 'EN_EVALUACION_OFERTAS') return 'bg-warning-subtle text-warning-emphasis fw-bold border border-warning-subtle';
        if ($estado_codigo === 'EN_CORRECCION') return 'bg-danger-subtle text-danger-emphasis fw-bold border border-danger-subtle'; 
        return 'bg-primary-subtle text-primary-emphasis border border-primary-subtle';
    }
}

if (!function_exists('money')) {
    function money($v) {
        if ($v === null || $v === '') return '$ 0';
        return '$ ' . number_format((float)$v, 0, ',', '.');
    }
}

require_once __DIR__ . '/flujos_helper.php';
require_once __DIR__ . '/firmagob_helper.php';
require_once __DIR__ . '/pdf_helper.php';
?>