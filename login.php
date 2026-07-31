<?php
// login.php - Acceso Principal con Redirección Inteligente
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Si ya está logueado, redirigir según su rol
if (isset($_SESSION['user_id'])) {
    redirectBasedOnRole($_SESSION['user_rol']);
}

$error = '';

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

                    // 4. Lógica de Subrogancia (Heredar Jefatura, Rol y Unidad del Titular)
                    $es_jefe = $user['es_jefe_unidad'];
                    $soy_subrogante = false;
                    $subrogado_nombre = null;
                    $hoy = date('Y-m-d');
                    
                    // Buscar si estoy subrogando a alguien HOY
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
                        $subrogado_nombre = $sub['titular_nombre'];
                        $es_jefe = $sub['titular_es_jefe'];
                        
                        // Sobrescribir Rol y Unidad para el flujo y accesos
                        $_SESSION['user_rol'] = $sub['titular_rol'];
                        $_SESSION['user_unidad'] = $sub['titular_unidad'];
                    }

                    $_SESSION['es_jefe'] = $es_jefe;
                    $_SESSION['es_subrogante'] = $soy_subrogante;
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
            header('Location: master.php');
            break;
        case 'PRESUPUESTO': 
            header('Location: control_presupuestario.php'); 
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso - Gestión OPI Municipal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light min-vh-100 d-flex flex-column align-items-center justify-content-center p-3">

    <div class="card shadow-lg border-0 rounded-4 overflow-hidden" style="max-width: 420px; width: 100%;">
        
        <!-- ENCABEZADO DE TARJETA -->
        <div class="bg-dark p-4 text-center border-bottom border-secondary text-white">
            <div class="d-flex justify-content-center mb-3">
                <img src="logo.png" alt="Logo Institucional" class="img-fluid rounded bg-white p-2 shadow-sm" style="max-height: 96px; width: auto;">
            </div>
            <h4 class="fw-bold mb-1">Gestión de Compras</h4>
            <p class="text-info small mb-0">Plataforma de Orden de Pedido Interno</p>
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
                    <label for="email" class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Correo Institucional</label>
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
                <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2">
                    Iniciar Sesión
                    <i class="bi bi-box-arrow-in-right"></i>
                </button>

            </form>
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