<?php
// claveunica_helper.php - Helper para la Integración con ClaveÚnica (OpenID Connect / OAuth 2.0)
// Cumple con las especificaciones de la Secretaría de Gobierno Digital (Guía Técnica v5.5)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Obtiene la configuración de ClaveÚnica desde Variables de Entorno de Dockploy (o fallbacks local/servidor).
 * 
 * @return array
 */
function obtener_configuracion_claveunica() {
    $client_id = getenv('CLAVEUNICA_CLIENT_ID') ?: ($_ENV['CLAVEUNICA_CLIENT_ID'] ?? ($_SERVER['CLAVEUNICA_CLIENT_ID'] ?? ''));
    $client_secret = getenv('CLAVEUNICA_CLIENT_SECRET') ?: ($_ENV['CLAVEUNICA_CLIENT_SECRET'] ?? ($_SERVER['CLAVEUNICA_CLIENT_SECRET'] ?? ''));
    
    // Auto-detectar protocolo y host para construir Redirect URI por defecto si no está definida en env
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'testopi.munilebu.gob.cl';
    $default_redirect = $protocol . '://' . $host . '/login.php';
    $default_logout_redirect = $protocol . '://' . $host . '/logout.php';

    $redirect_uri = getenv('CLAVEUNICA_REDIRECT_URI') ?: ($_ENV['CLAVEUNICA_REDIRECT_URI'] ?? ($_SERVER['CLAVEUNICA_REDIRECT_URI'] ?? $default_redirect));
    $logout_redirect_uri = getenv('CLAVEUNICA_LOGOUT_REDIRECT') ?: ($_ENV['CLAVEUNICA_LOGOUT_REDIRECT'] ?? ($_SERVER['CLAVEUNICA_LOGOUT_REDIRECT'] ?? $default_logout_redirect));

    return [
        'client_id' => trim($client_id),
        'client_secret' => trim($client_secret),
        'redirect_uri' => trim($redirect_uri),
        'logout_redirect_uri' => trim($logout_redirect_uri),
        'authorize_url' => 'https://accounts.claveunica.gob.cl/openid/authorize/',
        'token_url' => 'https://accounts.claveunica.gob.cl/openid/token/',
        'userinfo_url' => 'https://accounts.claveunica.gob.cl/openid/userinfo/',
        'logout_url' => 'https://accounts.claveunica.gob.cl/api/v1/accounts/app/logout'
    ];
}

/**
 * Genera el token 'state' dinámico anti-CSRF y retorna la URL completa de autorización de ClaveÚnica.
 * 
 * @return string
 */
function obtener_url_claveunica() {
    $config = obtener_configuracion_claveunica();
    
    // Generar state criptográficamente seguro (40 caracteres hexadecimales)
    $state = bin2hex(random_bytes(20));
    $_SESSION['claveunica_state'] = $state;

    $params = [
        'client_id'     => $config['client_id'],
        'response_type' => 'code',
        'scope'         => 'openid run name',
        'redirect_uri'  => $config['redirect_uri'],
        'state'         => $state,
    ];

    return $config['authorize_url'] . '?' . http_build_query($params);
}

/**
 * Intercambia el código de autorización obtenido en el callback por un Token de Acceso (Access Token).
 * 
 * @param string $code Código de autorización recibido de ClaveÚnica.
 * @param string $state Token de estado recibido en el callback.
 * @return string Retorna el access_token obtenido.
 * @throws Exception Si la validación falla o ClaveÚnica retorna un error.
 */
function intercambiar_code_por_token($code, $state) {
    if (empty($_SESSION['claveunica_state']) || !hash_equals($_SESSION['claveunica_state'], $state)) {
        throw new Exception("Error de seguridad (CSRF): El token de estado 'state' no coincide con la sesión actual.");
    }

    // Limpiar state usado por seguridad
    unset($_SESSION['claveunica_state']);

    $config = obtener_configuracion_claveunica();

    $post_fields = http_build_query([
        'client_id'     => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'redirect_uri'  => $config['redirect_uri'],
        'grant_type'    => 'authorization_code',
        'code'          => $code,
        'state'         => $state,
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config['token_url']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded; charset=UTF-8'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curl_error) {
        throw new Exception("Error de conexión al solicitar token de ClaveÚnica: " . $curl_error);
    }

    $data = json_decode($response, true);

    if ($http_code !== 200 || empty($data['access_token'])) {
        $msg_error = $data['error_description'] ?? ($data['error'] ?? 'Error desconocido al validar código con ClaveÚnica.');
        throw new Exception("ClaveÚnica rechazó la autenticación (HTTP $http_code): " . $msg_error);
    }

    return $data['access_token'];
}

/**
 * Obtiene los datos del ciudadano (RUN y Nombre completo) a partir del Access Token.
 * 
 * @param string $access_token
 * @return array Arreglo asociativo con los datos del usuario ('rut', 'nombres', 'apellidos').
 * @throws Exception Si la llamada a UserInfo falla.
 */
function obtener_info_usuario_claveunica($access_token) {
    $config = obtener_configuracion_claveunica();

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config['userinfo_url']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curl_error) {
        throw new Exception("Error de conexión al obtener datos del ciudadano en ClaveÚnica: " . $curl_error);
    }

    $data = json_decode($response, true);

    if ($http_code !== 200 || empty($data['RolUnico'])) {
        throw new Exception("No se pudo recuperar la información del ciudadano desde ClaveÚnica (HTTP $http_code).");
    }

    // Extraer RUN (RolÚnico) y nombres según la especificación técnica de ClaveÚnica
    $numero_run = $data['RolUnico']['numero'] ?? '';
    $dv_run = $data['RolUnico']['DV'] ?? '';
    $rut_raw = $numero_run . '-' . $dv_run;

    $nombres = is_array($data['name']['nombres'] ?? null) ? implode(' ', $data['name']['nombres']) : ($data['name']['nombres'] ?? '');
    $apellidos = is_array($data['name']['apellidos'] ?? null) ? implode(' ', $data['name']['apellidos']) : ($data['name']['apellidos'] ?? '');

    return [
        'rut_raw'   => $rut_raw,
        'nombres'   => trim($nombres),
        'apellidos' => trim($apellidos),
        'nombre_completo' => trim($nombres . ' ' . $apellidos)
    ];
}
