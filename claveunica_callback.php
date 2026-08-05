<?php
// claveunica_callback.php - Controlador para procesar la respuesta (callback) de ClaveÚnica
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/rut_helper.php';
require_once __DIR__ . '/claveunica_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';
$error_cu = $_GET['error'] ?? '';

if (!empty($error_cu)) {
    $_SESSION['login_error'] = "Autenticación cancelada en ClaveÚnica: " . htmlspecialchars($error_cu);
    header("Location: login.php");
    exit;
}

if (empty($code) || empty($state)) {
    $_SESSION['login_error'] = "No se recibieron los parámetros de respuesta requeridos desde ClaveÚnica.";
    header("Location: login.php");
    exit;
}

try {
    // 1. Intercambiar el 'code' por 'access_token' (valida internamente el token anti-CSRF 'state')
    $access_token = intercambiar_code_por_token($code, $state);

    // 2. Obtener datos del ciudadano autenticado (RUN, Nombres y Apellidos)
    $info_usuario = obtener_info_usuario_claveunica($access_token);

    $rut_cu_raw = $info_usuario['rut_raw'];
    $rut_cu_formateado = formatear_rut_chileno($rut_cu_raw);

    // 3. Buscar usuario en la Base de Datos comparando RUT de manera agnóstica a puntos y guiones
    $rut_limpio_cu = preg_replace('/[^0-9kK]/', '', $rut_cu_raw);

    $stmt = $pdo->prepare("
        SELECT u.id, u.nombre_completo, u.unidad_id, u.es_jefe_unidad, u.activo, u.email_verificado, u.estado_aprobacion, r.nombre as rol_nombre 
        FROM usuarios u
        JOIN roles r ON u.rol_id = r.id
        WHERE REPLACE(REPLACE(u.rut, '.', ''), '-', '') = ?
        LIMIT 1
    ");
    $stmt->execute([strtoupper($rut_limpio_cu)]);
    $user = $stmt->fetch();

    if ($user) {
        // 4. Verificar Estado de la Cuenta
        if ($user['email_verificado'] == 0 || $user['estado_aprobacion'] === 'PENDIENTE_VERIFICACION') {
            $_SESSION['login_error'] = "Su cuenta (RUT: $rut_cu_formateado) está pendiente de verificación por correo electrónico.";
            header("Location: login.php");
            exit;
        } elseif ($user['estado_aprobacion'] === 'PENDIENTE_APROBACION') {
            $_SESSION['login_error'] = "Su cuenta (RUT: $rut_cu_formateado) fue verificada y está pendiente de aprobación por el Administrador (SYSADMIN).";
            header("Location: login.php");
            exit;
        } elseif ($user['estado_aprobacion'] === 'RECHAZADO') {
            $_SESSION['login_error'] = "Su solicitud de usuario en el sistema fue rechazada por la administración.";
            header("Location: login.php");
            exit;
        } elseif ($user['activo'] == 0) {
            $_SESSION['login_error'] = "Su cuenta de usuario se encuentra desactivada.";
            header("Location: login.php");
            exit;
        }

        // 5. Iniciar Sesión Exitosa
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nombre_completo'];
        $_SESSION['user_rol'] = $user['rol_nombre'];
        $_SESSION['user_unidad'] = $user['unidad_id'];
        $_SESSION['login_via'] = 'claveunica';

        // 6. Lógica de Subrogancia (Idéntica a login.php)
        $es_jefe = $user['es_jefe_unidad'];
        $soy_subrogante = false;
        $subrogado_id = null;
        $subrogado_nombre = null;
        $hoy = date('Y-m-d');

        if ($user['rol_nombre'] !== 'SYSADMIN') {
            $stmtSub = $pdo->prepare("
                SELECT s.usuario_titular_id, u.nombre_completo as titular_nombre, u.unidad_id as titular_unidad, u.es_jefe_unidad as titular_es_jefe, r.nombre as titular_rol
                FROM subrogancias s
                JOIN usuarios u ON s.usuario_titular_id = u.id
                JOIN roles r ON u.rol_id = r.id
                WHERE s.usuario_subrogante_id = ? 
                AND s.activo = 1 
                AND ? BETWEEN s.fecha_inicio AND s.fecha_fin
                LIMIT 1
            ");
            $stmtSub->execute([$user['id'], $hoy]);
            $sub = $stmtSub->fetch();

            if ($sub) {
                $soy_subrogante = true;
                $subrogado_id = $sub['usuario_titular_id'];
                $subrogado_nombre = $sub['titular_nombre'];
                $es_jefe = $sub['titular_es_jefe'];

                // Sobrescribir Rol y Unidad para accesos
                $_SESSION['user_rol'] = $sub['titular_rol'];
                $_SESSION['user_unidad'] = $sub['titular_unidad'];
            }
        }

        $_SESSION['es_jefe'] = $es_jefe;
        $_SESSION['es_subrogante'] = $soy_subrogante;
        $_SESSION['subrogado_id'] = $subrogado_id;
        $_SESSION['subrogado_nombre'] = $subrogado_nombre;

        // 7. Redirigir según el Rol del usuario
        redirectBasedOnRole($_SESSION['user_rol']);

    } else {
        // Usuario autenticado en ClaveÚnica pero no registrado en OPIv2
        $_SESSION['login_error'] = "El RUT $rut_cu_formateado (" . htmlspecialchars($info_usuario['nombre_completo']) . ") no se encuentra registrado en el sistema. Solicite la creación de su cuenta al administrador.";
        header("Location: login.php");
        exit;
    }

} catch (Exception $e) {
    error_log("ClaveUnica Callback Error: " . $e->getMessage());
    $_SESSION['login_error'] = "Error durante el proceso de autenticación con ClaveÚnica: " . $e->getMessage();
    header("Location: login.php");
    exit;
}

/**
 * Función Helper de Redirección según Rol
 */
if (!function_exists('redirectBasedOnRole')) {
    function redirectBasedOnRole($role) {
        switch ($role) {
            case 'SYSADMIN':
                header('Location: mis_solicitudes.php');
                break;
            case 'PRESUPUESTO': 
                header('Location: control_presupuestario.php'); 
                break;
            case 'FINANZAS': 
                header('Location: finanzas.php'); 
                break;
            case 'ADQUISICIONES': 
                header('Location: adquisiciones.php'); 
                break;
            case 'ADMIN_MUNICIPAL': 
                header('Location: administrador.php'); 
                break;
            case 'JEFE_UNIDAD': 
                header('Location: jefatura.php'); 
                break;
            default: 
                if(isset($_SESSION['es_jefe']) && $_SESSION['es_jefe'] == 1) {
                    header('Location: jefatura.php');
                } else {
                    header('Location: mis_solicitudes.php');
                }
                break;
        }
        exit;
    }
}
