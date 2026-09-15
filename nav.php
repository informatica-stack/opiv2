<?php
// nav.php - Barra de Navegación SaaS Moderna, Modular y Adaptable (V7.0)
$pagina_actual = basename($_SERVER['PHP_SELF']);
$rol = $_SESSION['user_rol'] ?? '';
$es_jefe = $_SESSION['es_jefe'] ?? 0;
$user_name = $_SESSION['user_name'] ?? 'Usuario';

// Determinar secciones activas para resaltar los dropdowns superiores
$es_modulo_visaciones = in_array($pagina_actual, ['jefatura.php', 'administrador.php']);
$es_modulo_presupuesto = in_array($pagina_actual, ['dashboard.php', 'control_presupuestario.php', 'cuentas_centros_costo.php', 'centros_de_costo.php', 'mantenedor_cuentas.php', 'finanzas.php']);
$es_modulo_adquisiciones = in_array($pagina_actual, ['adquisiciones.php']);
$es_modulo_admin = in_array($pagina_actual, ['usuarios.php', 'unidades.php', 'firmantes.php', 'configuracion_sistema.php', 'mantenedor_flujos.php', 'limpiar_datos_pruebas.php']);
?>
<!-- Barra de Navegación Superior Fija SaaS -->
<nav class="navbar navbar-expand-xl navbar-light bg-white border-bottom shadow-xs sticky-top py-2 px-3 px-xl-4" style="z-index: 1030;">
    <div class="container-fluid px-0">
        
        <!-- 1. Marca y Logotipo -->
        <a class="navbar-brand d-flex align-items-center gap-2.5 me-xl-4" href="index.php">
            <img src="logo.png" alt="Logo" class="h-8 w-auto object-contain bg-white rounded p-0.5 border" style="height: 34px;">
            <div class="lh-1 d-none d-sm-block text-start">
                <span class="fs-6 fw-bold text-dark d-block tracking-tight" style="font-size: 14.5px !important; letter-spacing: -0.2px;">Sistema OPI</span>
                <span class="text-muted d-block text-uppercase" style="font-size: 8px; font-weight: 800; letter-spacing: 0.6px;">MUNICIPALIDAD DE LEBU</span>
            </div>
        </a>

        <!-- 2. Navegación Principal Horizontal (Desktop >= 1200px) -->
        <div class="d-none d-xl-flex align-items-center gap-1 me-auto">
            
            <!-- Mis Solicitudes -->
            <a class="nav-link-saas <?= ($pagina_actual === 'mis_solicitudes.php') ? 'active' : '' ?>" href="mis_solicitudes.php">
                <i class="bi bi-journal-text me-1.5"></i> Mis Solicitudes
            </a>

            <!-- Nueva Solicitud -->
            <a class="nav-link-saas highlight <?= ($pagina_actual === 'nueva_solicitud.php') ? 'active' : '' ?>" href="nueva_solicitud.php">
                <i class="bi bi-plus-circle me-1.5"></i> Nueva Solicitud
            </a>

            <!-- Dropdown: Visaciones & Firmas -->
            <?php if($es_jefe == 1 || $rol === 'JEFE_UNIDAD' || $rol === 'ADMIN_MUNICIPAL' || $rol === 'SYSADMIN'): ?>
                <div class="dropdown">
                    <button class="nav-link-saas dropdown-toggle <?= $es_modulo_visaciones ? 'active' : '' ?>" type="button" id="dropVisaciones" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-shield-check me-1.5"></i> Visaciones
                    </button>
                    <ul class="dropdown-menu shadow-lg border-light mt-1.5 p-2 rounded-3" aria-labelledby="dropVisaciones" style="min-width: 220px;">
                        <li><span class="dropdown-header text-uppercase text-secondary fw-bold" style="font-size: 9px;">Flujo de Firmas</span></li>
                        <li>
                            <a class="dropdown-item rounded-2 py-2 small d-flex align-items-center gap-2 <?= ($pagina_actual === 'jefatura.php') ? 'active bg-primary text-white' : 'text-dark' ?>" href="jefatura.php">
                                <i class="bi bi-person-check fs-6"></i> V°B° Jefatura (1/3)
                            </a>
                        </li>
                        <?php if($rol === 'ADMIN_MUNICIPAL' || $rol === 'SYSADMIN'): ?>
                            <li>
                                <a class="dropdown-item rounded-2 py-2 small d-flex align-items-center gap-2 <?= ($pagina_actual === 'administrador.php') ? 'active bg-primary text-white' : 'text-dark' ?>" href="administrador.php">
                                    <i class="bi bi-pencil-square fs-6"></i> Firma OPI Administrador (3/3)
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Dropdown: Presupuesto y Finanzas -->
            <?php if($rol === 'PRESUPUESTO' || $rol === 'FINANZAS' || $rol === 'ADMIN_MUNICIPAL' || $rol === 'SYSADMIN'): ?>
                <div class="dropdown">
                    <button class="nav-link-saas dropdown-toggle <?= $es_modulo_presupuesto ? 'active' : '' ?>" type="button" id="dropPresupuesto" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-calculator me-1.5"></i> Presupuesto & Finanzas
                    </button>
                    <ul class="dropdown-menu shadow-lg border-light mt-1.5 p-2 rounded-3" aria-labelledby="dropPresupuesto" style="min-width: 250px;">
                        <li><span class="dropdown-header text-uppercase text-secondary fw-bold" style="font-size: 9px;">Gestión Financiera</span></li>
                        <?php if($rol === 'PRESUPUESTO' || $rol === 'ADMIN_MUNICIPAL' || $rol === 'SYSADMIN'): ?>
                            <li>
                                <a class="dropdown-item rounded-2 py-2 small d-flex align-items-center gap-2 <?= ($pagina_actual === 'dashboard.php') ? 'active bg-primary text-white' : 'text-dark' ?>" href="dashboard.php">
                                    <i class="bi bi-speedometer2 fs-6"></i> Dashboard OPIs
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item rounded-2 py-2 small d-flex align-items-center gap-2 <?= ($pagina_actual === 'control_presupuestario.php') ? 'active bg-primary text-white' : 'text-dark' ?>" href="control_presupuestario.php">
                                    <i class="bi bi-calculator fs-6"></i> VB Presupuestario (2/3)
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item rounded-2 py-2 small d-flex align-items-center gap-2 <?= ($pagina_actual === 'cuentas_centros_costo.php' || $pagina_actual === 'centros_de_costo.php' || $pagina_actual === 'mantenedor_cuentas.php') ? 'active bg-primary text-white' : 'text-dark' ?>" href="cuentas_centros_costo.php">
                                    <i class="bi bi-wallet2 fs-6"></i> Cuentas y Centros de Costo
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if($rol === 'FINANZAS' || $rol === 'SYSADMIN'): ?>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item rounded-2 py-2 small d-flex align-items-center gap-2 <?= ($pagina_actual === 'finanzas.php') ? 'active bg-primary text-white' : 'text-dark' ?>" href="finanzas.php">
                                    <i class="bi bi-file-earmark-check fs-6"></i> Firma de CDP Oficial (DAF)
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Adquisiciones -->
            <?php if($rol === 'ADQUISICIONES' || $rol === 'ADMIN_MUNICIPAL' || $rol === 'SYSADMIN'): ?>
                <a class="nav-link-saas <?= ($pagina_actual === 'adquisiciones.php') ? 'active' : '' ?>" href="adquisiciones.php">
                    <i class="bi bi-cart3 me-1.5"></i> Adquisiciones
                </a>
            <?php endif; ?>

            <!-- Dropdown: Administración Global -->
            <?php if($rol === 'SYSADMIN' || $rol === 'ADMIN_MUNICIPAL'): ?>
                <div class="dropdown">
                    <button class="nav-link-saas dropdown-toggle <?= $es_modulo_admin ? 'active' : '' ?>" type="button" id="dropAdmin" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-sliders me-1.5"></i> Administración
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-light mt-1.5 p-2 rounded-3" aria-labelledby="dropAdmin" style="min-width: 250px; max-height: 80vh; overflow-y: auto;">
                        <li><span class="dropdown-header text-uppercase text-secondary fw-bold" style="font-size: 9px;">Configuración Institucional</span></li>
                        <li>
                            <a class="dropdown-item rounded-2 py-2 small d-flex align-items-center gap-2 <?= ($pagina_actual === 'usuarios.php') ? 'active bg-primary text-white' : 'text-dark' ?>" href="usuarios.php">
                                <i class="bi bi-people fs-6"></i> Gestión de Usuarios
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item rounded-2 py-2 small d-flex align-items-center gap-2 <?= ($pagina_actual === 'unidades.php') ? 'active bg-primary text-white' : 'text-dark' ?>" href="unidades.php">
                                <i class="bi bi-diagram-3 fs-6"></i> Direcciones y Unidades
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item rounded-2 py-2 small d-flex align-items-center gap-2 <?= ($pagina_actual === 'firmantes.php') ? 'active bg-primary text-white' : 'text-dark' ?>" href="firmantes.php">
                                <i class="bi bi-vector-pen fs-6"></i> Firmantes Suplentes
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item rounded-2 py-2 small d-flex align-items-center gap-2 <?= ($pagina_actual === 'configuracion_sistema.php') ? 'active bg-primary text-white' : 'text-dark' ?>" href="configuracion_sistema.php">
                                <i class="bi bi-sliders fs-6"></i> Parámetros del Sistema
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item rounded-2 py-2 small d-flex align-items-center gap-2 <?= ($pagina_actual === 'mantenedor_flujos.php') ? 'active bg-primary text-white' : 'text-dark' ?>" href="mantenedor_flujos.php">
                                <i class="bi bi-gear-wide-connected fs-6"></i> Diseñador de Flujos
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <a class="dropdown-item rounded-2 py-2 small text-danger fw-semibold d-flex align-items-center gap-2 <?= ($pagina_actual === 'limpiar_datos_pruebas.php') ? 'active bg-danger text-white' : '' ?>" href="limpiar_datos_pruebas.php">
                                <i class="bi bi-trash3-fill fs-6"></i> Limpieza de Pruebas
                            </a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>

        </div>

        <!-- 3. Acciones a la Derecha (Perfil de Usuario y Botón Móvil) -->
        <div class="d-flex align-items-center gap-2 ms-auto ms-xl-0">
            
            <!-- Botón de Menú Móvil / Offcanvas (< 1200px) -->
            <button class="btn btn-outline-secondary btn-sm d-xl-none d-flex align-items-center gap-1.5 px-3 py-2 rounded-3 text-dark border-secondary-subtle" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNav" aria-controls="offcanvasNav">
                <i class="bi bi-list fs-5"></i>
                <span class="small fw-bold">Menú</span>
            </button>

            <!-- Dropdown Perfil de Usuario -->
            <div class="dropdown">
                <button class="btn p-0 border-0 d-flex align-items-center gap-2 text-start" type="button" id="dropdownUserAvatar" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="d-none d-md-flex flex-column align-items-end lh-1">
                        <span class="text-sm fw-bold text-dark" style="font-size: 13px;"><?= htmlspecialchars($user_name) ?></span>
                        <?php if ($_SESSION['es_subrogante'] ?? false): ?>
                            <span class="text-warning text-uppercase mt-1 fw-bold" style="font-size: 8px;">
                                <i class="bi bi-person-exclamation me-0.5"></i> Suplente de: <?= htmlspecialchars($_SESSION['subrogado_nombre']) ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted text-uppercase mt-1" style="font-size: 8px; font-weight: 800; letter-spacing: 0.3px;"><?= htmlspecialchars($rol === 'SYSADMIN' ? 'SYSADMIN' : $rol) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-center text-white rounded-circle shadow-sm" style="width: 36px; height: 36px; font-weight: 800; font-size: 12.5px; background: linear-gradient(135deg, #2563eb, #06b6d4);">
                        <?= strtoupper(substr($user_name, 0, 2)) ?>
                    </div>
                </button>

                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-light mt-2 p-2 rounded-3" aria-labelledby="dropdownUserAvatar" style="min-width: 240px;">
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
<!-- OFFCANVAS LATERAL SCROLLABLE (PARA MÓVIL Y PANTALLAS PEQUEÑAS) -->
<!-- ============================================================== -->
<div class="offcanvas offcanvas-end border-0 shadow-lg" tabindex="-1" id="offcanvasNav" aria-labelledby="offcanvasNavLabel" style="width: 320px;">
    <div class="offcanvas-header border-bottom py-3 px-4 bg-light">
        <div class="d-flex align-items-center gap-2">
            <img src="logo.png" alt="Logo" class="h-8 w-auto object-contain bg-white rounded p-0.5 border" style="height: 30px;">
            <div>
                <h6 class="offcanvas-title fw-bold text-dark mb-0 fs-6" id="offcanvasNavLabel">Menú del Sistema</h6>
                <span class="text-muted" style="font-size: 9px; font-weight: 700; text-transform: uppercase;">Municipalidad de Lebu</span>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body p-3" style="overflow-y: auto;">
        
        <!-- Panel Principal -->
        <div class="mb-3">
            <div class="text-uppercase text-secondary fw-bold px-2 mb-2" style="font-size: 9.5px; letter-spacing: 0.5px;">Panel Principal</div>
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
                    <i class="bi bi-plus-circle-fill text-success"></i> Nueva Solicitud
                </a>
            </div>
        </div>

        <!-- Visaciones -->
        <?php if($es_jefe == 1 || $rol === 'JEFE_UNIDAD' || $rol === 'ADMIN_MUNICIPAL' || $rol === 'SYSADMIN'): ?>
            <div class="mb-3">
                <div class="text-uppercase text-secondary fw-bold px-2 mb-2" style="font-size: 9.5px; letter-spacing: 0.5px;">Visaciones y Firmas</div>
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

        <!-- Presupuesto y Finanzas -->
        <?php if($rol === 'PRESUPUESTO' || $rol === 'FINANZAS' || $rol === 'ADMIN_MUNICIPAL' || $rol === 'SYSADMIN'): ?>
            <div class="mb-3">
                <div class="text-uppercase text-secondary fw-bold px-2 mb-2" style="font-size: 9.5px; letter-spacing: 0.5px;">Presupuesto & Finanzas</div>
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

        <!-- Adquisiciones -->
        <?php if($rol === 'ADQUISICIONES' || $rol === 'ADMIN_MUNICIPAL' || $rol === 'SYSADMIN'): ?>
            <div class="mb-3">
                <div class="text-uppercase text-secondary fw-bold px-2 mb-2" style="font-size: 9.5px; letter-spacing: 0.5px;">Adquisiciones</div>
                <div class="d-flex flex-column gap-1">
                    <a class="offcanvas-link <?= ($pagina_actual === 'adquisiciones.php') ? 'active' : '' ?>" href="adquisiciones.php">
                        <i class="bi bi-cart3 text-primary"></i> Bandeja Adquisiciones
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Administración Global -->
        <?php if($rol === 'SYSADMIN' || $rol === 'ADMIN_MUNICIPAL'): ?>
            <div class="mb-3">
                <div class="text-uppercase text-secondary fw-bold px-2 mb-2" style="font-size: 9.5px; letter-spacing: 0.5px;">Administración Global</div>
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

        <!-- Mi Cuenta y Ajustes -->
        <div class="pt-2 border-top">
            <div class="text-uppercase text-secondary fw-bold px-2 mb-2" style="font-size: 9.5px; letter-spacing: 0.5px;">Mi Cuenta</div>
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
