<?php
// logout.php
// Iniciar sesión para poder destruirla
session_start();

// 1. Limpiar todas las variables de sesión
$_SESSION = array();

// 2. Borrar la cookie de sesión del navegador (Seguridad extra)
// Esto invalida el ID de sesión en el cliente
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destruir la sesión en el servidor
session_destroy();

// 4. Redirigir al Login
header("Location: login.php");
exit;
?>