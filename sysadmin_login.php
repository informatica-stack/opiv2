<?php
// sysadmin_login.php - Acceso Técnico Discreto y de Emergencia para Superadministrador (SYSADMIN)
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si ya está logueado como SYSADMIN, redirigir a mis_solicitudes.php
if (isset($_SESSION['user_id']) && ($_SESSION['user_rol'] ?? '') === 'SYSADMIN') {
    header("Location: mis_solicitudes.php");
    exit;
}

$error = '';
$max_intentos = 5;
$tiempo_bloqueo = 900; // 15 minutos en segundos

// 1. Control Anti Fuerza Bruta por Sesión
$intentos = $_SESSION['sysadmin_login_attempts'] ?? 0;
$bloqueado_hasta = $_SESSION['sysadmin_lockout_time'] ?? 0;
$tiempo_restante = $bloqueado_hasta - time();

if ($tiempo_restante > 0) {
    $minutos = ceil($tiempo_restante / 60);
    $error = "Acceso bloqueado por seguridad debido a múltiples intentos fallidos. Intente nuevamente en $minutos minuto(s).";
}

// 2. Procesar Login Administrativo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tiempo_restante <= 0) {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        try {
            // Buscar únicamente usuarios activos con rol SYSADMIN
            $stmt = $pdo->prepare("
                SELECT u.id, u.nombre_completo, u.password_hash, u.unidad_id, u.activo, u.email_verificado, u.estado_aprobacion, r.nombre as rol_nombre 
                FROM usuarios u
                JOIN roles r ON u.rol_id = r.id
                WHERE u.email = ?
                LIMIT 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && !empty($user['password_hash']) && password_verify($password, $user['password_hash'])) {
                
                // Verificar que sea estrictamente SYSADMIN
                if ($user['rol_nombre'] !== 'SYSADMIN') {
                    throw new Exception("Acceso denegado: Esta consola es de uso exclusivo para la administración de sistemas (SYSADMIN).");
                }

                if ($user['activo'] == 0 || $user['estado_aprobacion'] !== 'APROBADO') {
                    throw new Exception("Su cuenta administrativa se encuentra inactiva o deshabilitada.");
                }

                // Login exitoso: Resetear contador de intentos
                unset($_SESSION['sysadmin_login_attempts']);
                unset($_SESSION['sysadmin_lockout_time']);

                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nombre_completo'];
                $_SESSION['user_rol'] = 'SYSADMIN';
                $_SESSION['user_unidad'] = $user['unidad_id'];
                $_SESSION['es_jefe'] = 0;
                $_SESSION['es_subrogante'] = false;
                $_SESSION['login_via'] = 'local_sysadmin';

                header("Location: mis_solicitudes.php");
                exit;

            } else {
                // Registrar intento fallido
                $intentos++;
                $_SESSION['sysadmin_login_attempts'] = $intentos;

                if ($intentos >= $max_intentos) {
                    $_SESSION['sysadmin_lockout_time'] = time() + $tiempo_bloqueo;
                    $error = "Acceso bloqueado por 15 minutos debido a $max_intentos intentos fallidos seguidos.";
                } else {
                    $restantes = $max_intentos - $intentos;
                    $error = "Credenciales administrativas incorrectas. Intentos restantes: $restantes.";
                }
            }
        } catch (Exception $e) {
            error_log("SYSADMIN Login Error: " . $e->getMessage());
            $error = $e->getMessage();
        }
    } else {
        $error = "Por favor complete todos los campos obligatorios.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php 
    $titulo_pagina = "Consola de Administración - SYSADMIN";
    include __DIR__ . '/head.php'; 
    ?>
</head>
<body class="bg-dark min-vh-100 d-flex flex-column align-items-center justify-content-center p-3 text-white">

    <div class="card bg-secondary text-white shadow-lg border-0 rounded-4 overflow-hidden" style="max-width: 400px; width: 100%; --bs-bg-opacity: .15; backdrop-filter: blur(10px);">
        
        <div class="card-header bg-black bg-opacity-50 p-4 text-center border-bottom border-secondary">
            <div class="d-flex justify-content-center mb-2">
                <i class="bi bi-shield-lock-fill text-warning display-4"></i>
            </div>
            <h5 class="fw-bold mb-0 tracking-wide text-uppercase">Consola SYSADMIN</h5>
            <small class="text-white-50">Acceso Técnico de Emergencia</small>
        </div>

        <div class="card-body p-4 p-sm-4">
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger border-0 d-flex align-items-center gap-2 mb-4" role="alert">
                    <i class="bi bi-exclamation-octagon-fill shrink-0"></i>
                    <div class="small fw-semibold"><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <form action="" method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="mb-3">
                    <label for="email" class="form-label fw-bold text-white-50 small text-uppercase" style="font-size: 10px;">Correo Administrador</label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-secondary">
                            <i class="bi bi-person-fill-gear"></i>
                        </span>
                        <input type="email" name="email" id="email" required 
                            class="form-control bg-dark text-white border-secondary" placeholder="sysadmin@dominio.cl" <?= $tiempo_restante > 0 ? 'disabled' : '' ?>>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label fw-bold text-white-50 small text-uppercase" style="font-size: 10px;">Contraseña Maestro</label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-secondary">
                            <i class="bi bi-key-fill"></i>
                        </span>
                        <input type="password" name="password" id="password" required 
                            class="form-control bg-dark text-white border-secondary" placeholder="••••••••••••" <?= $tiempo_restante > 0 ? 'disabled' : '' ?>>
                    </div>
                </div>

                <button type="submit" class="btn btn-warning w-100 py-2.5 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" <?= $tiempo_restante > 0 ? 'disabled' : '' ?>>
                    Autenticar SYSADMIN
                    <i class="bi bi-arrow-right-circle-fill"></i>
                </button>
            </form>
        </div>

        <div class="card-footer bg-black bg-opacity-30 p-3 text-center border-top border-secondary">
            <p class="text-white-50 small mb-0" style="font-size: 11px;">
                Conexión segura cifrada con TLS &bull; OPIv2 Internal System
            </p>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
