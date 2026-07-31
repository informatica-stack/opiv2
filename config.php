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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die("Error de Seguridad (CSRF): La solicitud no pudo ser validada. Por favor, recargue la página e intente nuevamente.");
    }
}

// 2. Credenciales de Base de Datos (Variables de Entorno Dockploy con Fallback Local)
define('DB_HOST', getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? ($_SERVER['DB_HOST'] ?? 'localhost')));
define('DB_NAME', getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? ($_SERVER['DB_NAME'] ?? 'db_municipal_opi')));
define('DB_USER', getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? ($_SERVER['DB_USER'] ?? 'root')));
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : ($_ENV['DB_PASS'] ?? ($_SERVER['DB_PASS'] ?? '')));
define('DB_CHARSET', getenv('DB_CHARSET') ?: ($_ENV['DB_CHARSET'] ?? ($_SERVER['DB_CHARSET'] ?? 'utf8mb4')));

// 3. Conexión PDO (Segura)
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
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
define('ESTADO_GESTION_COMPRA', 'EN_GESTION_COMPRA');
define('ESTADO_SELECCION', 'EN_SELECCION_PROVEEDOR');
define('ESTADO_APROB_ADM', 'EN_APROBACION_ADMINISTRADOR');
define('ESTADO_OPI_EMITIDA', 'OPI_EMITIDA');
define('ESTADO_ANULADO', 'ANULADO');

// 5. Función helper para URLs (opcional)
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

require_once __DIR__ . '/flujos_helper.php';
?>