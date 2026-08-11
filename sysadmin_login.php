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
    $titulo_pagina = "Acceso Administrador - SYSADMIN";
    include __DIR__ . '/head.php'; 
    ?>
</head>
<body class="bg-light min-vh-100 d-flex flex-column align-items-center justify-content-center p-3">

    <div class="card shadow-lg border-0 rounded-4 overflow-hidden" style="max-width: 420px; width: 100%;">
        
        <!-- ENCABEZADO DE TARJETA (Estilo Claro Institucional) -->
        <div class="bg-white p-4 text-center border-bottom border-light-subtle">
            <div class="d-flex justify-content-center mb-3">
                <img src="logo.png" alt="Logo Institucional" class="img-fluid rounded bg-white p-2 border border-light-subtle shadow-sm" style="max-height: 96px; width: auto;">
            </div>
            <h4 class="fw-bold text-dark mb-1">Sistema de órdenes de pedido interno</h4>
            <p class="text-muted small mb-0">Acceso Administrador del Sistema (SYSADMIN)</p>
        </div>

        <div class="card-body p-4 p-sm-5">
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-4 text-start" role="alert">
                    <i class="bi bi-exclamation-triangle-fill shrink-0"></i>
                    <div class="small fw-semibold"><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <form action="" method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <!-- CORREO INSTITUCIONAL -->
                <div class="mb-3">
                    <label for="email" class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Correo Administrador</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-secondary border-end-0">
                            <i class="bi bi-envelope-fill"></i>
                        </span>
                        <input type="email" name="email" id="email" required 
                            class="form-control bg-light border-start-0 py-2.5" placeholder="informatica@lebu.cl" <?= $tiempo_restante > 0 ? 'disabled' : '' ?>>
                    </div>
                </div>

                <!-- CONTRASEÑA -->
                <div class="mb-4">
                    <label for="password" class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-secondary border-end-0">
                            <i class="bi bi-lock-fill"></i>
                        </span>
                        <input type="password" name="password" id="password" required 
                            class="form-control bg-light border-start-0 py-2.5" placeholder="••••••••••••" <?= $tiempo_restante > 0 ? 'disabled' : '' ?>>
                    </div>
                </div>

                <!-- BOTÓN DE INGRESO -->
                <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" <?= $tiempo_restante > 0 ? 'disabled' : '' ?>>
                    Iniciar Sesión
                    <i class="bi bi-box-arrow-in-right"></i>
                </button>
            </form>
        </div>

        <!-- FOOTER DE TARJETA -->
        <div class="bg-light p-3 text-center border-top border-light-subtle">
            <p class="text-muted small mb-0" style="font-size: 11px;">
                Departamento de Informática &copy; <?= date('Y') ?> &bull; Municipalidad de Lebu
            </p>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
