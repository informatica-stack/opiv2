<?php
// nav.php - Barra de Navegación Superior y Menú Desplegable Clara (V6.2)
$pagina_actual = basename($_SERVER['PHP_SELF']);
$rol = $_SESSION['user_rol'] ?? '';
$es_jefe = $_SESSION['es_jefe'] ?? 0;
$user_name = $_SESSION['user_name'] ?? 'Usuario';
?>
<!-- Optimización de Carga de Fuentes -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<!-- Sistema de Diseño Global -->
<link rel="stylesheet" href="css/style.css">

<!-- Barra Superior Clara -->
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm py-2">
    <div class="container-fluid px-4">
        
        <!-- Logo y Marca -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
            <img src="logo.png" alt="Logo" class="h-8 w-auto object-contain bg-white rounded p-0.5 border" style="height: 32px;">
            <div class="lh-1 d-none d-sm-block text-start">
                <span class="fs-6 fw-bold text-dark d-block">Sistema de Órdenes de Pedido Interno</span>
                <span class="text-muted d-block text-uppercase tracking-wider" style="font-size: 8px; font-weight: 800;">Municipalidad</span>
            </div>
        </a>

        <!-- Menú Desplegable de Navegación en el Header -->
        <div class="d-flex align-items-center gap-2 ms-auto">
            
            <div class="dropdown me-2">
                <button class="btn btn-outline-secondary btn-sm fw-bold dropdown-toggle d-flex align-items-center gap-1.5 px-3 py-2 rounded-3 text-dark border-secondary-subtle" type="button" id="dropdownNavMenu" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-list fs-6"></i>
                    <span class="d-none d-md-inline">Menú de Navegación</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-light mt-2 p-2" aria-labelledby="dropdownNavMenu" style="min-width: 250px;">
                    
                    <li><span class="dropdown-header text-uppercase text-secondary fw-bold" style="font-size: 9px; letter-spacing: 0.5px;">Panel Principal</span></li>
                    <li><a class="dropdown-item rounded-3 py-2 small d-flex align-items-center gap-2 <?= ($pagina_actual === 'mis_solicitudes.php') ? 'active bg-primary text-white' : 'text-dark' ?>" href="mis_solicitudes.php"><i class="bi bi-journal-text"></i> Mis Solicitudes</a></li>
                    <li><a class="dropdown-item rounded-3 py-2 small d-flex align-items-center gap-2 <?= ($pagina_actual === 'nueva_solicitud.php') ? 'active bg-primary text-white' : 'text-dark' ?>" href="nueva_solicitud.php"><i class="bi bi-plus-circle"></i> Nueva Solicitud</a></li>
                    <li><a class="dropdown-item rounded-3 py-2 small d-flex align-items-center gap-2 <?= ($pagina_actual === 'subrogancia.php') ? 'active bg-primary text-white' : 'text-dark' ?>" href="subrogancia.php"><i class="bi bi-person-gear"></i> Configurar Suplente</a></li>
                    
                    <?php if($es_jefe == 1 || $rol === 'JEFE_UNIDAD' || $rol === 'ADMIN_MUNICIPAL' || $rol === 'SYSADMIN'): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><span class="dropdown-header text-uppercase text-secondary fw-bold" style="font-size: 9px; letter-spacing: 0.5px;">Visaciones</span></li>
                        <li><a class="dropdown-item rounded-3 py-2 small d-flex align-items-center gap-2 <?= ($pagina_actual === 'jefatura.php') ? 'active bg-primary text-white' : 'text-dark' ?>" href="jefatura.php"><i class="bi bi-shield-check"></i> V°B° Jefatura</a></li>
                        <?php if($rol === 'ADMIN_MUNICIPAL' || $rol === 'SYSADMIN'): ?>
                            <li><a class="dropdown-item rounded-3 py-2 small d-flex align-items-center gap-2 <?= ($pagina_actual === 'administrador.php') ? 'active bg-primary text-white' : 'text-dark' ?>" href="administrador.php"><i class="bi bi-pencil-square"></i> Firma de OPI</a></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if($rol === 'PRESUPUESTO' || $rol === 'FINANZAS' || $rol === 'SYSADMIN'): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><span class="dropdown-header text-uppercase text-secondary fw-bold" style="font-size: 9px; letter-spacing: 0.5px;">Presupuesto y Finanzas</span></li>
                        <?php if($rol === 'PRESUPUESTO' || $rol === 'SYSADMIN'): ?>
                            <li><a class="dropdown-item rounded-3 py-2 small d-flex align-items-center gap-2 <?= ($pagina_actual === 'control_presupuestario.php') ? 'active bg-primary text-white' : 'text-dark' ?>" href="control_presupuestario.php"><i class="bi bi-calculator"></i> VB Presupuestario</a></li>
                            <li><a class="dropdown-item rounded-3 py-2 small d-flex align-items-center gap-2 <?= ($pagina_actual === 'centros_de_costo.php') ? 'active bg-primary text-white' : 'text-dark' ?>" href="centros_de_costo.php"><i class="bi bi-wallet2"></i> Centros de Costo</a></li>
                            <li><a class="dropdown-item rounded-3 py-2 small d-flex align-items-center gap-2 <?= ($pagina_actual === 'mantenedor_cuentas.php') ? 'active bg-primary text-white' : 'text-dark' ?>" href="mantenedor_cuentas.php"><i class="bi bi-list-columns-reverse"></i> Cuentas Presupuestarias</a></li>
                        <?php endif; ?>
                        <?php if($rol === 'FINANZAS' || $rol === 'SYSADMIN'): ?>
                            <li><a class="dropdown-item rounded-3 py-2 small d-flex align-items-center gap-2 <?= ($pagina_actual === 'finanzas.php') ? 'active bg-primary text-white' : 'text-dark' ?>" href="finanzas.php"><i class="bi bi-file-earmark-check"></i> Firma de CDP</a></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if($rol === 'ADQUISICIONES' || $rol === 'SYSADMIN'): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><span class="dropdown-header text-uppercase text-secondary fw-bold" style="font-size: 9px; letter-spacing: 0.5px;">Adquisiciones</span></li>
                        <li><a class="dropdown-item rounded-3 py-2 small d-flex align-items-center gap-2 <?= ($pagina_actual === 'adquisiciones.php') ? 'active bg-primary text-white' : 'text-dark' ?>" href="adquisiciones.php"><i class="bi bi-cart3"></i> Bandeja Adquisiciones</a></li>
                    <?php endif; ?>

                    <?php if($rol === 'SYSADMIN' || $rol === 'ADMIN_MUNICIPAL'): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><span class="dropdown-header text-uppercase text-secondary fw-bold" style="font-size: 9px; letter-spacing: 0.5px;">Administración Global</span></li>
                        <li><a class="dropdown-item rounded-3 py-2 small d-flex align-items-center gap-2 <?= ($pagina_actual === 'usuarios.php') ? 'active bg-primary text-white' : 'text-dark' ?>" href="usuarios.php"><i class="bi bi-people"></i> Usuarios</a></li>
                        <li><a class="dropdown-item rounded-3 py-2 small d-flex align-items-center gap-2 <?= ($pagina_actual === 'unidades.php') ? 'active bg-primary text-white' : 'text-dark' ?>" href="unidades.php"><i class="bi bi-diagram-3"></i> Unidades</a></li>
                        <li><a class="dropdown-item rounded-3 py-2 small d-flex align-items-center gap-2 <?= ($pagina_actual === 'firmantes.php') ? 'active bg-primary text-white' : 'text-dark' ?>" href="firmantes.php"><i class="bi bi-vector-pen"></i> Firmantes Suplentes</a></li>
                        <li><a class="dropdown-item rounded-3 py-2 small d-flex align-items-center gap-2 text-danger fw-bold <?= ($pagina_actual === 'limpiar_datos_pruebas.php') ? 'active bg-danger text-white' : '' ?>" href="limpiar_datos_pruebas.php"><i class="bi bi-trash3-fill"></i> Limpieza de Pruebas</a></li>
                    <?php endif; ?>

                    <?php if($rol === 'SYSADMIN' || $rol === 'ADMIN_MUNICIPAL' || $rol === 'PRESUPUESTO'): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><span class="dropdown-header text-uppercase text-secondary fw-bold" style="font-size: 9px; letter-spacing: 0.5px;">Configuración</span></li>
                        <li><a class="dropdown-item rounded-3 py-2 small d-flex align-items-center gap-2 <?= ($pagina_actual === 'mantenedor_flujos.php') ? 'active bg-primary text-white' : 'text-dark' ?>" href="mantenedor_flujos.php"><i class="bi bi-gear-wide-connected"></i> Diseñador de Flujos</a></li>

                    <?php endif; ?>
                    
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item rounded-3 py-2 small text-danger fw-bold d-flex align-items-center gap-2" href="logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
                </ul>
            </div>

            <!-- Información y Dropdown de Usuario -->
            <div class="d-flex align-items-center gap-2">
                <div class="d-none d-md-flex flex-column align-items-end lh-1">
                    <span class="text-sm fw-bold text-dark"><?= htmlspecialchars($user_name) ?></span>
                    <?php if ($_SESSION['es_subrogante'] ?? false): ?>
                        <span class="text-warning text-uppercase mt-1 fw-bold" style="font-size: 8px;">
                            <i class="bi bi-person-exclamation me-0.5"></i> Suplente de: <?= htmlspecialchars($_SESSION['subrogado_nombre']) ?>
                        </span>
                    <?php else: ?>
                        <span class="text-muted text-uppercase mt-1" style="font-size: 8px; font-weight: 800;"><?= htmlspecialchars($rol === 'SYSADMIN' ? 'SYSADMIN' : $rol) ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="dropdown">
                    <button class="btn p-0 border-0 d-flex align-items-center justify-content-center text-white rounded-circle shadow-sm" type="button" id="dropdownUserAvatar" data-bs-toggle="dropdown" aria-expanded="false" style="width: 38px; height: 38px; font-weight: 800; font-size: 13px; background: linear-gradient(135deg, #0d6efd, #0dcaf0);">
                        <?= strtoupper(substr($user_name, 0, 2)) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-light mt-2 p-2" aria-labelledby="dropdownUserAvatar">
                        <li class="px-3 py-2 border-bottom mb-2">
                            <div class="fw-bold text-dark text-xs"><?= htmlspecialchars($user_name) ?></div>
                            <?php if ($_SESSION['es_subrogante'] ?? false): ?>
                                <span class="badge bg-warning text-dark border px-2 py-0.5 rounded-pill mt-1" style="font-size: 8px; font-weight: 800;">
                                    Suplente de: <?= htmlspecialchars($_SESSION['subrogado_nombre']) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary border px-2 py-0.5 rounded-pill mt-1" style="font-size: 8px;">
                                    <?= htmlspecialchars($rol) ?>
                                </span>
                            <?php endif; ?>
                        </li>
                        <li><a class="dropdown-item rounded-3 py-2 small text-danger fw-bold d-flex align-items-center gap-2" href="logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>

        </div>

    </div>
</nav>

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