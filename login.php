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

// Toda autenticación regular es gestionada mediante ClaveÚnica (OpenID Connect)


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

        <div class="card-body p-4 p-sm-5 text-center">
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-4 text-start" role="alert">
                    <i class="bi bi-exclamation-triangle-fill shrink-0"></i>
                    <div class="small fw-semibold"><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <p class="text-secondary small fw-bold mb-4 uppercase tracking-wide">
                Ingresa con tu ClaveÚnica
            </p>

            <div class="d-flex justify-content-center my-3">
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