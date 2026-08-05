<?php
// logout.php
require_once __DIR__ . '/claveunica_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$es_login_claveunica = ($_SESSION['login_via'] ?? '') === 'claveunica';

// 1. Limpiar todas las variables de sesión
$_SESSION = array();

// 2. Borrar la cookie de sesión del navegador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destruir la sesión en el servidor
session_destroy();

// 4. Redirigir al logout de ClaveÚnica si se autenticó por ese medio, o al login directo
if ($es_login_claveunica) {
    $config = obtener_configuracion_claveunica();
    $logout_cu_url = $config['logout_url'] . '?redirect=' . urlencode($config['logout_redirect_uri']);
    header("Location: " . $logout_cu_url);
    exit;
}

header("Location: login.php");
exit;
?>