<?php
// login.php - Acceso Principal con Redirección Inteligente
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/claveunica_helper.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Si ClaveÚnica redirige directamente a login.php con 'code' y 'state', procesar mediante el callback
if (isset($_GET['code']) && isset($_GET['state'])) {
    require_once __DIR__ . '/claveunica_callback.php';
    exit;
}

// Si ya está logueado, redirigir según su rol
if (isset($_SESSION['user_id'])) {
    redirectBasedOnRole($_SESSION['user_rol']);
}

$error = '';
if (!empty($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

// --- PROCESO DE LOGIN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        try {
            // 1. Buscar Usuario
            $stmt = $pdo->prepare("
                SELECT u.id, u.nombre_completo, u.password_hash, u.unidad_id, u.es_jefe_unidad, u.activo, u.email_verificado, u.estado_aprobacion, r.nombre as rol_nombre 
                FROM usuarios u
                JOIN roles r ON u.rol_id = r.id
                WHERE u.email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // 2. Verificar Contraseña y Estado de Cuenta
            if ($user && password_verify($password, $user['password_hash'])) {
                
                if ($user['email_verificado'] == 0 || $user['estado_aprobacion'] === 'PENDIENTE_VERIFICACION') {
                    $error = 'Su cuenta está pendiente de verificación por correo electrónico. Por favor revise su casilla de correo.';
                } elseif ($user['estado_aprobacion'] === 'PENDIENTE_APROBACION') {
                    $error = 'Su cuenta ha sido verifiada y está pendiente de aprobación por el Administrador del Sistema (SYSADMIN).';
                } elseif ($user['estado_aprobacion'] === 'RECHAZADO') {
                    $error = 'Su solicitud de registro en el sistema fue rechazada por la administración.';
                } elseif ($user['activo'] == 0) {
                    $error = 'Su cuenta de usuario se encuentra desactivada.';
                } else {
                    // Seguridad: Regenerar ID de sesión
                    session_regenerate_id(true);

                    // 3. Guardar Sesión Base
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['nombre_completo'];
                    $_SESSION['user_rol'] = $user['rol_nombre'];
                    $_SESSION['user_unidad'] = $user['unidad_id'];

                    // 4. Lógica de Subrogancia (Heredar Jefatura, Rol y Unidad del Titular - Excluyendo SYSADMIN)
                    $es_jefe = $user['es_jefe_unidad'];
                    $soy_subrogante = false;
                    $subrogado_id = null;
                    $subrogado_nombre = null;
                    $hoy = date('Y-m-d');
                    
                    // Buscar si estoy subrogando a alguien HOY (salvo que sea SYSADMIN)
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
                            
                            // Sobrescribir Rol y Unidad para el flujo y accesos
                            $_SESSION['user_rol'] = $sub['titular_rol'];
                            $_SESSION['user_unidad'] = $sub['titular_unidad'];
                        }
                    }

                    $_SESSION['es_jefe'] = $es_jefe;
                    $_SESSION['es_subrogante'] = $soy_subrogante;
                    $_SESSION['subrogado_id'] = $subrogado_id;
                    $_SESSION['subrogado_nombre'] = $subrogado_nombre;

                    // 5. Redirigir
                    redirectBasedOnRole($_SESSION['user_rol']);
                }

            } else {
                $error = 'Credenciales incorrectas o cuenta no registrada.';
            }
        } catch (PDOException $e) {
            error_log("Login Error: " . $e->getMessage());
            $error = 'Error de conexión con la base de datos.';
        }
    } else {
        $error = 'Por favor complete todos los campos.';
    }
}

// Función Helper de Redirección
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
            // Si es usuario normal pero está subrogando a un jefe
            if(isset($_SESSION['es_jefe']) && $_SESSION['es_jefe'] == 1) {
                header('Location: jefatura.php');
            } else {
                header('Location: mis_solicitudes.php');
            }
            break;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php 
    $titulo_pagina = "Acceso al Sistema";
    include __DIR__ . '/head.php'; 
    ?>
</head>
<body class="bg-light min-vh-100 d-flex flex-column align-items-center justify-content-center p-3">

    <div class="card shadow-lg border-0 rounded-4 overflow-hidden" style="max-width: 420px; width: 100%;">
        
        <!-- ENCABEZADO DE TARJETA -->
        <div class="bg-dark p-4 text-center border-bottom border-secondary text-white">
            <div class="d-flex justify-content-center mb-3">
                <img src="logo.png" alt="Logo Institucional" class="img-fluid rounded bg-white p-2 shadow-sm" style="max-height: 96px; width: auto;">
            </div>
            <h4 class="fw-bold mb-1">Sistema de órdenes de pedido interno</h4>
            <!--  <p class="text-info small mb-0">Plataforma de Orden de Pedido Interno</p>-->
        </div>

        <div class="card-body p-4 p-sm-5">
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill shrink-0"></i>
                    <div class="small fw-semibold"><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <!-- CORREO INSTITUCIONAL -->
                <div class="mb-3">
                    <label for="email" class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Correo</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-secondary border-end-0">
                            <i class="bi bi-envelope-fill"></i>
                        </span>
                        <input type="email" name="email" id="email" required 
                            class="form-control bg-light border-start-0 py-2.5">
                    </div>
                </div>

                <!-- CONTRASEÑA -->
                <div class="mb-3">
                    <label for="password" class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-secondary border-end-0">
                            <i class="bi bi-lock-fill"></i>
                        </span>
                        <input type="password" name="password" id="password" required 
                            class="form-control bg-light border-start-0 py-2.5">
                    </div>
                </div>

                <!-- RECORDARME Y SOPORTE -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="recordarme">
                        <label class="form-check-label text-secondary small cursor-pointer" for="recordarme">Recordarme</label>
                    </div>
                    <a href="#" class="text-decoration-none small fw-bold text-primary">¿Ayuda?</a>
                </div>

                <!-- BOTÓN DE INGRESO -->
                <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2 mb-3">
                    Iniciar Sesión
                    <i class="bi bi-box-arrow-in-right"></i>
                </button>
            </form>

            <!-- DIVISOR "O BIEN" -->
            <div class="position-relative text-center my-4">
                <hr class="text-secondary opacity-25 m-0">
                <span class="position-absolute top-50 start-50 translate-middle bg-white px-3 text-muted small fw-semibold text-uppercase" style="font-size: 11px;">o bien</span>
            </div>

            <!-- BOTÓN OFICIAL CLAVEÚNICA (Ejemplo Institucional Lebu) -->
            <a class="btn-cu btn-m btn-color-estandar shadow-sm" href="<?= obtener_url_claveunica() ?>" aria-label="Iniciar sesión con ClaveÚnica">
                <svg class="cl-claveunica-svg" width="24" height="24" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M12.4998 13.8956C12.9835 13.8956 13.3756 14.2878 13.3756 14.7715C13.3756 15.2552 12.9835 15.6473 12.4998 15.6473C12.0161 15.6473 11.6239 15.2552 11.6239 14.7715C11.6239 14.2878 12.0161 13.8956 12.4998 13.8956Z" fill="white"></path>
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M11.631 1.70078C11.631 1.21768 12.0227 0.82605 12.5058 0.82605H15.9585C16.4416 0.82605 16.8333 1.21768 16.8333 1.70078C16.8333 2.18387 16.4416 2.5755 15.9585 2.5755H13.3805V9.35701C15.9909 9.77835 17.9845 12.0421 17.9845 14.7714C17.9845 17.8006 15.5289 20.2562 12.4998 20.2562C9.47065 20.2562 7.01505 17.8006 7.01505 14.7714C7.01505 12.0379 9.01473 9.77145 11.631 9.35509V1.70078ZM8.7645 14.7714C8.7645 12.7085 10.4368 11.0361 12.4998 11.0361C14.5627 11.0361 16.2351 12.7085 16.2351 14.7714C16.2351 16.8344 14.5627 18.5067 12.4998 18.5067C10.4368 18.5067 8.7645 16.8344 8.7645 14.7714Z" fill="white"></path>
                    <path d="M16.7507 5.65748C16.313 5.45302 15.7924 5.64209 15.5879 6.07979C15.3835 6.51748 15.5725 7.03806 16.0102 7.24252C18.8442 8.56635 20.8048 11.4409 20.8048 14.7716C20.8048 19.3583 17.0865 23.0766 12.4998 23.0766C7.91305 23.0766 4.19477 19.3583 4.19477 14.7716C4.19477 11.4517 6.14272 8.58499 8.96185 7.25542C9.39879 7.04935 9.58595 6.52809 9.37988 6.09115C9.17381 5.6542 8.65254 5.46705 8.2156 5.67312C4.80707 7.28066 2.44531 10.7494 2.44531 14.7716C2.44531 20.3245 6.94686 24.826 12.4998 24.826C18.0527 24.826 22.5543 20.3245 22.5543 14.7716C22.5543 10.7363 20.1771 7.25811 16.7507 5.65748Z" fill="white"></path>
                </svg>
                <span class="texto" aria-hidden="true">Iniciar sesión</span>
            </a>
        </div>

        
        <!-- FOOTER DE TARJETA -->
        <div class="bg-light p-3 text-center border-top border-light-subtle">
            <p class="mb-2">
                <a href="registro.php" class="text-decoration-none fw-bold small text-primary d-inline-flex align-items-center gap-1">
                    <i class="bi bi-person-plus-fill"></i> ¿No tienes cuenta? Regístrate como Funcionario
                </a>
            </p>
            <p class="text-muted small mb-0" style="font-size: 11px;">
                Departamento de Informática &copy; <?= date('Y') ?>
            </p>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>