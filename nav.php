<?php
// nav.php - Barra de Navegación SaaS Unificada con Menú Desplegable Lateral Exclusivo (V8.0)
$pagina_actual = basename($_SERVER['PHP_SELF']);
$rol = $_SESSION['user_rol'] ?? '';
$es_jefe = $_SESSION['es_jefe'] ?? 0;
$user_name = $_SESSION['user_name'] ?? 'Usuario';
?>
<!-- Barra de Navegación Superior Fija SaaS (Limpia, Amplia y Minimalista) -->
<nav class="navbar navbar-expand navbar-light bg-white border-bottom shadow-xs sticky-top py-2 px-3 px-md-4 px-lg-5" style="z-index: 1030; min-height: 68px;">
    <div class="container-fluid px-0 d-flex align-items-center justify-content-between">
        
        <!-- 1. Marca y Logotipo -->
        <a class="brand-wrapper" href="index.php">
            <img src="logo.png" alt="Logo Municipalidad" class="brand-logo-img">
            <div class="lh-1 d-none d-sm-block text-start">
                <span class="brand-title">Sistema OPI</span>
                <span class="brand-subtitle">Municipalidad de Lebu</span>
            </div>
        </a>

        <!-- 2. Acciones del Header: Botón Único de Menú + Divisor + Perfil de Usuario -->
        <div class="d-flex align-items-center gap-2 gap-sm-3 gap-md-3.5">
            
            <!-- Botón Desplegable del Menú del Sistema -->
            <button class="btn-nav-menu" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNav" aria-controls="offcanvasNav" title="Abrir Menú de Módulos">
                <i class="bi bi-grid-3x3-gap-fill"></i>
                <span class="d-none d-xs-inline">Menú del Sistema</span>
            </button>

            <!-- Separador Vertical Visual -->
            <div class="nav-vertical-divider d-none d-sm-block"></div>

            <!-- Dropdown Rápido de Perfil de Usuario -->
            <div class="dropdown">
                <button class="user-profile-trigger border-0" type="button" id="dropdownUserAvatar" data-bs-toggle="dropdown" aria-expanded="false" title="Cuenta de Usuario">
                    <div class="user-info-col d-none d-md-flex">
                        <span class="user-display-name"><?= htmlspecialchars($user_name) ?></span>
                        <?php if ($_SESSION['es_subrogante'] ?? false): ?>
                            <span class="badge bg-warning text-dark border px-1.5 py-0.5 mt-0.5" style="font-size: 8px; font-weight: 800;">
                                <i class="bi bi-person-exclamation me-0.5"></i> Suplente de: <?= htmlspecialchars($_SESSION['subrogado_nombre']) ?>
                            </span>
                        <?php else: ?>
                            <span class="user-display-role"><?= htmlspecialchars($rol === 'SYSADMIN' ? 'SYSADMIN' : $rol) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="user-avatar-badge">
                        <?= strtoupper(substr($user_name, 0, 2)) ?>
                    </div>
                    <i class="bi bi-chevron-down user-chevron-icon d-none d-sm-inline-block"></i>
                </button>

                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-light mt-2 p-2 rounded-3" aria-labelledby="dropdownUserAvatar" style="min-width: 250px;">
                    <li class="px-3 py-2.5 border-bottom mb-1 bg-light rounded-2">
                        <div class="fw-bold text-dark text-xs mb-0.5"><?= htmlspecialchars($user_name) ?></div>
                        <?php if ($_SESSION['es_subrogante'] ?? false): ?>
                            <span class="badge bg-warning text-dark border px-2 py-0.5 rounded-pill" style="font-size: 8px; font-weight: 800;">
                                Suplente de: <?= htmlspecialchars($_SESSION['subrogado_nombre']) ?>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-0.5 rounded-pill" style="font-size: 8px;">
                                <?= htmlspecialchars($rol) ?>
                            </span>
                        <?php endif; ?>
                    </li>
                    <li>
                        <a class="dropdown-item rounded-2 py-2 small d-flex align-items-center gap-2 <?= ($pagina_actual === 'subrogancia.php') ? 'active bg-primary text-white' : 'text-dark' ?>" href="subrogancia.php">
                            <i class="bi bi-person-gear fs-6"></i> Configurar Suplente
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item rounded-2 py-2 small text-danger fw-bold d-flex align-items-center gap-2" href="logout.php">
                            <i class="bi bi-box-arrow-right fs-6"></i> Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </div>

        </div>

    </div>
</nav>

<!-- ============================================================== -->
<!-- MENÚ DESPLEGABLE ÚNICO (DRAWER OFFCANVAS LATERAL SCROLLABLE)   -->
<!-- ============================================================== -->
<div class="offcanvas offcanvas-end border-0 shadow-lg" tabindex="-1" id="offcanvasNav" aria-labelledby="offcanvasNavLabel" style="width: 330px; z-index: 1050;">
    
    <!-- Cabecera del Menú -->
    <div class="offcanvas-header border-bottom py-3 px-4 bg-light">
        <div class="d-flex align-items-center gap-3">
            <img src="logo.png" alt="Logo" class="brand-logo-img" style="height: 36px;">
            <div>
                <h6 class="offcanvas-title fw-bold text-dark mb-0 fs-6" id="offcanvasNavLabel">Menú del Sistema</h6>
                <span class="brand-subtitle">Municipalidad de Lebu</span>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <!-- Cuerpo del Menú con Scroll Fluido -->
    <div class="offcanvas-body p-3" style="overflow-y: auto;">
        
        <!-- 1. Panel Principal -->
        <div class="mb-3.5">
            <div class="text-uppercase text-secondary fw-bold px-2 mb-2" style="font-size: 9.5px; letter-spacing: 0.6px;">Panel Principal</div>
            <div class="d-flex flex-column gap-1">
                <?php if($rol === 'PRESUPUESTO' || $rol === 'ADMIN_MUNICIPAL' || $rol === 'SYSADMIN'): ?>
                    <a class="offcanvas-link <?= ($pagina_actual === 'dashboard.php') ? 'active' : '' ?>" href="dashboard.php">
                        <i class="bi bi-speedometer2 text-primary"></i> Dashboard OPIs
                    </a>
                <?php endif; ?>
                <a class="offcanvas-link <?= ($pagina_actual === 'mis_solicitudes.php') ? 'active' : '' ?>" href="mis_solicitudes.php">
                    <i class="bi bi-journal-text text-primary"></i> Mis Solicitudes
                </a>
                <a class="offcanvas-link highlight <?= ($pagina_actual === 'nueva_solicitud.php') ? 'active' : '' ?>" href="nueva_solicitud.php">
                    <i class="bi bi-plus-circle-fill text-primary"></i> Nueva Solicitud
                </a>
            </div>
        </div>

        <!-- 2. Visaciones y Firmas -->
        <?php if($es_jefe == 1 || $rol === 'JEFE_UNIDAD' || $rol === 'ADMIN_MUNICIPAL' || $rol === 'SYSADMIN'): ?>
            <div class="mb-3.5">
                <div class="text-uppercase text-secondary fw-bold px-2 mb-2" style="font-size: 9.5px; letter-spacing: 0.6px;">Visaciones y Firmas</div>
                <div class="d-flex flex-column gap-1">
                    <a class="offcanvas-link <?= ($pagina_actual === 'jefatura.php') ? 'active' : '' ?>" href="jefatura.php">
                        <i class="bi bi-shield-check text-primary"></i> V°B° Jefatura (1/3)
                    </a>
                    <?php if($rol === 'ADMIN_MUNICIPAL' || $rol === 'SYSADMIN'): ?>
                        <a class="offcanvas-link <?= ($pagina_actual === 'administrador.php') ? 'active' : '' ?>" href="administrador.php">
                            <i class="bi bi-pencil-square text-primary"></i> Firma OPI Administrador (3/3)
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- 3. Presupuesto y Finanzas -->
        <?php if($rol === 'PRESUPUESTO' || $rol === 'FINANZAS' || $rol === 'ADMIN_MUNICIPAL' || $rol === 'SYSADMIN'): ?>
            <div class="mb-3.5">
                <div class="text-uppercase text-secondary fw-bold px-2 mb-2" style="font-size: 9.5px; letter-spacing: 0.6px;">Presupuesto & Finanzas</div>
                <div class="d-flex flex-column gap-1">
                    <?php if($rol === 'PRESUPUESTO' || $rol === 'ADMIN_MUNICIPAL' || $rol === 'SYSADMIN'): ?>
                        <a class="offcanvas-link <?= ($pagina_actual === 'control_presupuestario.php') ? 'active' : '' ?>" href="control_presupuestario.php">
                            <i class="bi bi-calculator text-primary"></i> VB Presupuestario (2/3)
                        </a>
                        <a class="offcanvas-link <?= ($pagina_actual === 'cuentas_centros_costo.php' || $pagina_actual === 'centros_de_costo.php' || $pagina_actual === 'mantenedor_cuentas.php') ? 'active' : '' ?>" href="cuentas_centros_costo.php">
                            <i class="bi bi-wallet2 text-primary"></i> Cuentas y Centros de Costo
                        </a>
                    <?php endif; ?>
                    <?php if($rol === 'FINANZAS' || $rol === 'SYSADMIN'): ?>
                        <a class="offcanvas-link <?= ($pagina_actual === 'finanzas.php') ? 'active' : '' ?>" href="finanzas.php">
                            <i class="bi bi-file-earmark-check text-primary"></i> Firma CDP Oficial (DAF)
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- 4. Adquisiciones -->
        <?php if($rol === 'ADQUISICIONES' || $rol === 'ADMIN_MUNICIPAL' || $rol === 'SYSADMIN'): ?>
            <div class="mb-3.5">
                <div class="text-uppercase text-secondary fw-bold px-2 mb-2" style="font-size: 9.5px; letter-spacing: 0.6px;">Adquisiciones</div>
                <div class="d-flex flex-column gap-1">
                    <a class="offcanvas-link <?= ($pagina_actual === 'adquisiciones.php') ? 'active' : '' ?>" href="adquisiciones.php">
                        <i class="bi bi-cart3 text-primary"></i> Bandeja Adquisiciones
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- 5. Administración Global -->
        <?php if($rol === 'SYSADMIN' || $rol === 'ADMIN_MUNICIPAL'): ?>
            <div class="mb-3.5">
                <div class="text-uppercase text-secondary fw-bold px-2 mb-2" style="font-size: 9.5px; letter-spacing: 0.6px;">Administración Global</div>
                <div class="d-flex flex-column gap-1">
                    <a class="offcanvas-link <?= ($pagina_actual === 'usuarios.php') ? 'active' : '' ?>" href="usuarios.php">
                        <i class="bi bi-people text-primary"></i> Gestión de Usuarios
                    </a>
                    <a class="offcanvas-link <?= ($pagina_actual === 'unidades.php') ? 'active' : '' ?>" href="unidades.php">
                        <i class="bi bi-diagram-3 text-primary"></i> Direcciones y Unidades
                    </a>
                    <a class="offcanvas-link <?= ($pagina_actual === 'firmantes.php') ? 'active' : '' ?>" href="firmantes.php">
                        <i class="bi bi-vector-pen text-primary"></i> Firmantes Suplentes
                    </a>
                    <a class="offcanvas-link <?= ($pagina_actual === 'configuracion_sistema.php') ? 'active' : '' ?>" href="configuracion_sistema.php">
                        <i class="bi bi-sliders text-primary"></i> Parámetros del Sistema
                    </a>
                    <a class="offcanvas-link <?= ($pagina_actual === 'mantenedor_flujos.php') ? 'active' : '' ?>" href="mantenedor_flujos.php">
                        <i class="bi bi-gear-wide-connected text-primary"></i> Diseñador de Flujos
                    </a>
                    <a class="offcanvas-link text-danger fw-semibold <?= ($pagina_actual === 'limpiar_datos_pruebas.php') ? 'active bg-danger text-white' : '' ?>" href="limpiar_datos_pruebas.php">
                        <i class="bi bi-trash3-fill"></i> Limpieza de Pruebas
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- 6. Mi Cuenta y Suplencia -->
        <div class="pt-2.5 border-top">
            <div class="text-uppercase text-secondary fw-bold px-2 mb-2" style="font-size: 9.5px; letter-spacing: 0.6px;">Mi Cuenta</div>
            <div class="d-flex flex-column gap-1">
                <a class="offcanvas-link <?= ($pagina_actual === 'subrogancia.php') ? 'active' : '' ?>" href="subrogancia.php">
                    <i class="bi bi-person-gear text-secondary"></i> Configurar Suplente
                </a>
                <a class="offcanvas-link text-danger fw-bold" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                </a>
            </div>
        </div>

    </div>
</div>

<!-- Inyección Automática de Token CSRF en Formularios POST -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[method="post"], form[method="POST"]').forEach(form => {
        if (!form.querySelector('input[name="csrf_token"]')) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'csrf_token';
            input.value = '<?= $_SESSION['csrf_token'] ?>';
            form.appendChild(input);
        }
    });
});
</script>
<?php include_once __DIR__ . '/modal_trazabilidad.php'; ?>
