<?php 
// mis_solicitudes.php - Vista UI Renovada SaaS Clean Minimalist
require_once __DIR__ . '/mis_solicitudes_controller.php'; 

// Métricas en tiempo real del usuario actual
$stmtKpiTotal = $pdo->prepare("SELECT COUNT(*) FROM expedientes WHERE usuario_creador_id = ?");
$stmtKpiTotal->execute([$user_id]);
$kpi_total_user = (int)$stmtKpiTotal->fetchColumn();

$stmtKpiPend = $pdo->prepare("SELECT COUNT(*) FROM expedientes WHERE usuario_creador_id = ? AND estado_actual IN ('BORRADOR', 'EN_REVISION_JEFATURA', 'EN_CORRECCION')");
$stmtKpiPend->execute([$user_id]);
$kpi_pend_user = (int)$stmtKpiPend->fetchColumn();

$stmtKpiPres = $pdo->prepare("SELECT COUNT(*), COALESCE(SUM(COALESCE(monto_definitivo, monto_estimado)), 0) FROM expedientes WHERE usuario_creador_id = ? AND estado_actual IN ('EN_REVISION_PRESUPUESTO', 'VB_PRESUPUESTO', 'VB_FINANZAS_CDP')");
$stmtKpiPres->execute([$user_id]);
$kpi_pres_row = $stmtKpiPres->fetch(PDO::FETCH_NUM);
$kpi_pres_count = (int)$kpi_pres_row[0];
$kpi_pres_monto = (float)$kpi_pres_row[1];

$stmtKpiAdq = $pdo->prepare("SELECT COUNT(*) FROM expedientes WHERE usuario_creador_id = ? AND estado_actual IN ('EN_GESTION_ADQUISICIONES', 'EN_COTIZACION_ADQ', 'EN_EVALUACION_OFERTAS', 'FINALIZADO', 'ADJUDICADO')");
$stmtKpiAdq->execute([$user_id]);
$kpi_adq_user = (int)$stmtKpiAdq->fetchColumn();

$user_name = $_SESSION['user_name'] ?? 'Usuario';
$user_rol = $_SESSION['user_rol'] ?? '';
$es_jefe = $_SESSION['es_jefe'] ?? 0;
$user_depto = $_SESSION['user_depto_nombre'] ?? 'Municipalidad';
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Solicitudes - Sistema OPI</title>

    <!-- Tipografía Moderna Limpia con Números Estándar (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Bootstrap 5 CSS para Modales y Utilidades -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        :root {
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --text-light: #94a3b8;
            --border-color: #e2e8f0;
            --border-focus: #3b82f6;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --primary-light: #eff6ff;
            --success: #10b981;
            --success-light: #ecfdf5;
            --warning: #f59e0b;
            --warning-light: #fffbeb;
            --danger: #ef4444;
            --danger-light: #fef2f2;
            --info: #06b6d4;
            --info-light: #ecfeff;
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 16px;
            --shadow-subtle: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03);
            --shadow-card: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.03);
            --shadow-elevated: 0 20px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.03);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            font-variant-numeric: normal;
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            font-size: 13.5px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        /* HEADER & TOPBAR */
        .saas-topbar {
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
            padding: 0 24px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .brand-box {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-main);
        }

        .brand-logo-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 17px;
        }

        .brand-text h1 {
            font-size: 15px;
            font-weight: 700;
            margin: 0;
            color: var(--text-main);
            letter-spacing: -0.3px;
        }
        .brand-text p {
            font-size: 11px;
            color: var(--text-muted);
            margin: 0;
            font-weight: 500;
        }

        .saas-nav-links {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .saas-nav-item {
            padding: 7px 14px;
            border-radius: var(--radius-sm);
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .saas-nav-item:hover {
            background: #f1f5f9;
            color: var(--text-main);
        }
        .saas-nav-item.active {
            background: var(--primary-light);
            color: var(--primary);
        }

        .user-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 4px 10px 4px 4px;
            border-radius: 9999px;
            background: #f8fafc;
            border: 1px solid var(--border-color);
        }
        .user-avatar-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #334155;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
        }

        /* MAIN CONTAINER */
        .saas-container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 28px 24px 60px;
        }

        /* BREADCRUMBS & TITLE */
        .page-header-row {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .breadcrumbs {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 4px;
        }
        .breadcrumbs a {
            color: var(--text-muted);
            text-decoration: none;
        }
        .breadcrumbs a:hover { color: var(--text-main); }
        .breadcrumbs span.current {
            color: var(--text-main);
            font-weight: 600;
        }

        .page-title h2 {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin: 0;
            color: var(--text-main);
        }
        .page-title p {
            color: var(--text-muted);
            font-size: 13px;
            margin: 2px 0 0;
        }

        /* BOTONES SAAS */
        .btn-saas {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            border-radius: var(--radius-sm);
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .btn-saas-primary {
            background-color: var(--primary);
            color: #ffffff;
            box-shadow: 0 1px 2px rgba(37, 99, 235, 0.2);
        }
        .btn-saas-primary:hover {
            background-color: var(--primary-hover);
            color: #ffffff;
        }
        .btn-saas-secondary {
            background-color: #ffffff;
            border-color: var(--border-color);
            color: var(--text-main);
        }
        .btn-saas-secondary:hover {
            background-color: #f8fafc;
            border-color: #cbd5e1;
            color: var(--text-main);
        }
        .btn-saas-danger {
            background-color: #ffffff;
            border-color: #fecaca;
            color: var(--danger);
        }
        .btn-saas-danger:hover {
            background-color: var(--danger-light);
            border-color: #fca5a5;
            color: #b91c1c;
        }
        .btn-saas-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        /* METRIC CARDS GRID */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .metric-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 18px 20px;
            box-shadow: var(--shadow-subtle);
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
        }

        .metric-info h5 {
            font-size: 11.5px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin: 0 0 4px;
        }
        .metric-number {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: var(--text-main);
            margin: 0;
            line-height: 1.2;
            font-variant-numeric: tabular-nums;
        }
        .metric-sub {
            font-size: 11.5px;
            color: var(--text-muted);
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .metric-icon-box {
            width: 38px;
            height: 38px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
        }
        .metric-icon-box.blue { background: var(--primary-light); color: var(--primary); }
        .metric-icon-box.yellow { background: var(--warning-light); color: var(--warning); }
        .metric-icon-box.cyan { background: var(--info-light); color: var(--info); }
        .metric-icon-box.green { background: var(--success-light); color: var(--success); }

        /* PANEL PRINCIPAL DE TABLA */
        .saas-panel {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-subtle);
            overflow: hidden;
        }

        .panel-toolbar {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border-color);
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .filters-left {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            flex: 1;
        }

        .saas-search-box {
            position: relative;
            min-width: 260px;
            max-width: 320px;
            flex: 1;
        }
        .saas-search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 13px;
        }
        .saas-search-input {
            width: 100%;
            padding: 7px 12px 7px 34px;
            font-size: 12.5px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            background: #f8fafc;
            color: var(--text-main);
            outline: none;
            transition: all 0.15s;
        }
        .saas-search-input:focus {
            border-color: var(--border-focus);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .saas-select {
            padding: 7px 10px;
            font-size: 12.5px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            background: #ffffff;
            color: var(--text-main);
            outline: none;
            cursor: pointer;
        }

        /* ADVANCED DATE PANEL */
        .advanced-filters-panel {
            padding: 14px 20px;
            background: #f8fafc;
            border-bottom: 1px solid var(--border-color);
        }

        /* DATA TABLE */
        .table-responsive {
            overflow-x: auto;
        }

        table.saas-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        table.saas-table th {
            background: #f8fafc;
            padding: 12px 18px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
        }

        table.saas-table td {
            padding: 14px 18px;
            border-bottom: 1px solid var(--border-color);
            font-size: 13px;
            vertical-align: middle;
        }

        table.saas-table tr:hover td {
            background-color: #fafcff;
        }

        /* CODE AND BADGES */
        .saas-code-btn {
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            font-size: 13px;
            font-family: inherit;
        }
        .saas-code-btn:hover {
            text-decoration: underline;
        }

        .saas-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 600;
            white-space: nowrap;
            border: 1px solid transparent;
        }
        .saas-badge-neutral { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
        .saas-badge-warning { background: var(--warning-light); color: #b45309; border-color: #fde68a; }
        .saas-badge-info { background: var(--info-light); color: #0e7490; border-color: #a5f3fc; }
        .saas-badge-success { background: var(--success-light); color: #047857; border-color: #a7f3d0; }
        .saas-badge-danger { background: var(--danger-light); color: #b91c1c; border-color: #fecaca; }

        .priority-indicator {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .prio-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }

        .price-text {
            font-weight: 700;
            color: var(--text-main);
            font-size: 13.5px;
            font-variant-numeric: tabular-nums;
        }

        /* FOOTER & PAGINATION */
        .panel-footer {
            padding: 12px 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fafafa;
            font-size: 12px;
            color: var(--text-muted);
            flex-wrap: wrap;
            gap: 12px;
        }

        .saas-pagination {
            display: flex;
            gap: 4px;
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .saas-page-link {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            text-decoration: none;
            color: var(--text-main);
            background: #ffffff;
            font-weight: 600;
            font-size: 12px;
            transition: all 0.15s;
        }
        .saas-page-link:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }
        .saas-page-link.active {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }

        /* SLIDE-OVER DRAWER */
        .saas-drawer-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(2px);
            z-index: 1040;
            display: none;
            transition: opacity 0.2s ease;
        }
        .saas-drawer-backdrop.show { display: block; }

        .saas-drawer {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            width: 520px;
            max-width: 100vw;
            background: #ffffff;
            border-left: 1px solid var(--border-color);
            box-shadow: var(--shadow-elevated);
            z-index: 1050;
            display: flex;
            flex-direction: column;
            transform: translateX(100%);
            transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .saas-drawer.open {
            transform: translateX(0);
        }

        .drawer-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
        }
        .drawer-body {
            padding: 24px;
            flex: 1;
            overflow-y: auto;
        }
        .drawer-footer {
            padding: 14px 24px;
            border-top: 1px solid var(--border-color);
            background: #fafafa;
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>

    <!-- TOPBAR SAAS UNIFICADA -->
    <header class="saas-topbar">
        <a href="index.php" class="brand-box">
            <div class="brand-logo-icon">O</div>
            <div class="brand-text">
                <h1>Sistema OPI</h1>
                <p>Órdenes de Pedido Interno</p>
            </div>
        </a>

        <!-- Accesos directos rápidos -->
        <nav class="saas-nav-links d-none d-lg-flex">
            <a href="mis_solicitudes.php" class="saas-nav-item active"><i class="bi bi-journal-text"></i> Mis Solicitudes</a>
            <a href="nueva_solicitud.php" class="saas-nav-item"><i class="bi bi-plus-circle"></i> Nueva Solicitud</a>
            <?php if($es_jefe == 1 || $user_rol === 'JEFE_UNIDAD' || $user_rol === 'ADMIN_MUNICIPAL' || $user_rol === 'SYSADMIN'): ?>
                <a href="jefatura.php" class="saas-nav-item"><i class="bi bi-shield-check"></i> V°B° Jefatura</a>
            <?php endif; ?>
            <?php if($user_rol === 'PRESUPUESTO' || $user_rol === 'ADMIN_MUNICIPAL' || $user_rol === 'SYSADMIN'): ?>
                <a href="control_presupuestario.php" class="saas-nav-item"><i class="bi bi-calculator"></i> Presupuesto</a>
                <a href="centros_de_costo.php" class="saas-nav-item"><i class="bi bi-wallet2"></i> Centros Costo</a>
            <?php endif; ?>
            <?php if($user_rol === 'ADQUISICIONES' || $user_rol === 'SYSADMIN'): ?>
                <a href="adquisiciones.php" class="saas-nav-item"><i class="bi bi-cart3"></i> Adquisiciones</a>
            <?php endif; ?>
        </nav>

        <!-- Menú desplegable completo y Perfil -->
        <div class="d-flex align-items-center gap-2">
            <!-- Menú global modular -->
            <div class="dropdown">
                <button class="btn-saas btn-saas-secondary btn-saas-sm dropdown-toggle d-flex align-items-center gap-1.5" type="button" id="dropdownGlobalNav" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-grid-fill text-primary"></i>
                    <span class="d-none d-sm-inline">Módulos</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-light mt-2 p-2" aria-labelledby="dropdownGlobalNav" style="min-width: 250px;">
                    <li><span class="dropdown-header text-uppercase text-secondary fw-bold" style="font-size: 9px;">Panel Principal</span></li>
                    <?php if($user_rol === 'PRESUPUESTO' || $user_rol === 'ADMIN_MUNICIPAL' || $user_rol === 'SYSADMIN'): ?>
                        <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard OPIs</a></li>
                    <?php endif; ?>
                    <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2 active bg-primary text-white" href="mis_solicitudes.php"><i class="bi bi-journal-text"></i> Mis Solicitudes</a></li>
                    <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2" href="nueva_solicitud.php"><i class="bi bi-plus-circle"></i> Nueva Solicitud</a></li>
                    <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2" href="subrogancia.php"><i class="bi bi-person-gear"></i> Configurar Suplente</a></li>
                    
                    <?php if($es_jefe == 1 || $user_rol === 'JEFE_UNIDAD' || $user_rol === 'ADMIN_MUNICIPAL' || $user_rol === 'SYSADMIN'): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><span class="dropdown-header text-uppercase text-secondary fw-bold" style="font-size: 9px;">Visaciones</span></li>
                        <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2" href="jefatura.php"><i class="bi bi-shield-check"></i> V°B° Jefatura</a></li>
                        <?php if($user_rol === 'ADMIN_MUNICIPAL' || $user_rol === 'SYSADMIN'): ?>
                            <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2" href="administrador.php"><i class="bi bi-pencil-square"></i> Firma de OPI</a></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if($user_rol === 'PRESUPUESTO' || $user_rol === 'FINANZAS' || $user_rol === 'SYSADMIN'): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><span class="dropdown-header text-uppercase text-secondary fw-bold" style="font-size: 9px;">Presupuesto y Finanzas</span></li>
                        <?php if($user_rol === 'PRESUPUESTO' || $user_rol === 'SYSADMIN'): ?>
                            <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2" href="control_presupuestario.php"><i class="bi bi-calculator"></i> VB Presupuestario</a></li>
                            <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2" href="centros_de_costo.php"><i class="bi bi-wallet2"></i> Centros de Costo</a></li>
                            <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2" href="mantenedor_cuentas.php"><i class="bi bi-list-columns-reverse"></i> Cuentas Presupuestarias</a></li>
                        <?php endif; ?>
                        <?php if($user_rol === 'FINANZAS' || $user_rol === 'SYSADMIN'): ?>
                            <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2" href="finanzas.php"><i class="bi bi-file-earmark-check"></i> Firma de CDP</a></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if($user_rol === 'ADQUISICIONES' || $user_rol === 'SYSADMIN'): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><span class="dropdown-header text-uppercase text-secondary fw-bold" style="font-size: 9px;">Adquisiciones</span></li>
                        <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2" href="adquisiciones.php"><i class="bi bi-cart3"></i> Bandeja Adquisiciones</a></li>
                    <?php endif; ?>

                    <?php if($user_rol === 'SYSADMIN' || $user_rol === 'ADMIN_MUNICIPAL'): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><span class="dropdown-header text-uppercase text-secondary fw-bold" style="font-size: 9px;">Administración</span></li>
                        <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2" href="usuarios.php"><i class="bi bi-people"></i> Usuarios</a></li>
                        <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2" href="unidades.php"><i class="bi bi-diagram-3"></i> Unidades</a></li>
                        <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2" href="configuracion_sistema.php"><i class="bi bi-sliders"></i> Parámetros</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Avatar y Menú Usuario -->
            <div class="dropdown">
                <button class="user-pill border-0 p-1" type="button" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false" style="background: #f1f5f9; cursor: pointer;">
                    <div class="user-avatar-circle"><?= strtoupper(substr($user_name, 0, 2)) ?></div>
                    <div class="d-none d-sm-block text-start pe-2">
                        <div style="font-weight: 700; font-size: 12px; line-height: 1.1;"><?= htmlspecialchars($user_name) ?></div>
                        <div style="font-size: 10.5px; color: var(--text-muted);"><?= htmlspecialchars($user_rol ?: 'Usuario') ?></div>
                    </div>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-light mt-2 p-2" aria-labelledby="dropdownUser">
                    <li class="px-3 py-2 border-bottom mb-2">
                        <div class="fw-bold text-dark text-xs"><?= htmlspecialchars($user_name) ?></div>
                        <?php if ($_SESSION['es_subrogante'] ?? false): ?>
                            <span class="badge bg-warning text-dark border px-2 py-0.5 rounded-pill mt-1" style="font-size: 9px; font-weight: 700;">
                                Suplente de: <?= htmlspecialchars($_SESSION['subrogado_nombre']) ?>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-0.5 rounded-pill mt-1" style="font-size: 9px;">
                                <?= htmlspecialchars($user_rol) ?>
                            </span>
                        <?php endif; ?>
                    </li>
                    <li><a class="dropdown-item rounded-3 py-1.5 small text-danger fw-bold d-flex align-items-center gap-2" href="logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- WRAPPER -->
    <main class="saas-container">
        
        <!-- CABECERA DE PÁGINA -->
        <div class="page-header-row">
            <div class="page-title">
                <div class="breadcrumbs">
                    <a href="index.php">Inicio</a>
                    <i class="bi bi-chevron-right" style="font-size: 9px;"></i>
                    <span class="current">Mis Solicitudes</span>
                </div>
                <h2>Mis Solicitudes OPI</h2>
                <p>Historial completo, estado de visaciones y trazabilidad de requerimientos de compra.</p>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button type="button" onclick="toggleFiltrosAvanzados()" class="btn-saas btn-saas-secondary">
                    <i class="bi bi-funnel"></i>
                    <span>Filtros</span>
                </button>
                <a href="nueva_solicitud.php" class="btn-saas btn-saas-primary">
                    <i class="bi bi-plus-lg"></i>
                    <span>Nueva Solicitud</span>
                </a>
            </div>
        </div>

        <!-- MENSAJE DE ALERTA -->
        <?php if ($mensaje): ?>
            <div class="alert <?= $tipo_mensaje === 'error' ? 'alert-danger' : 'alert-success' ?> d-flex align-items-center gap-2 mb-4 rounded-3 shadow-sm" role="alert">
                <i class="bi <?= $tipo_mensaje === 'error' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill' ?>"></i>
                <div><?= htmlspecialchars($mensaje) ?></div>
            </div>
        <?php endif; ?>

        <!-- TARJETAS DE MÉTRICAS KPI (DINÁMICAS DEL USUARIO) -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-info">
                    <h5>Total Solicitudes</h5>
                    <div class="metric-number"><?= $kpi_total_user ?></div>
                    <div class="metric-sub"><i class="bi bi-folder2-open text-primary"></i> En su historial personal</div>
                </div>
                <div class="metric-icon-box blue"><i class="bi bi-file-earmark-text"></i></div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <h5>Pendiente Jefatura</h5>
                    <div class="metric-number"><?= $kpi_pend_user ?></div>
                    <div class="metric-sub" style="color: <?= $kpi_pend_user > 0 ? 'var(--warning)' : 'var(--text-muted)' ?>;">
                        <i class="bi bi-clock-history"></i> <?= $kpi_pend_user > 0 ? 'En revisión / firma' : 'Al día' ?>
                    </div>
                </div>
                <div class="metric-icon-box yellow"><i class="bi bi-pen"></i></div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <h5>Validación Presupuesto</h5>
                    <div class="metric-number"><?= $kpi_pres_count ?></div>
                    <div class="metric-sub"><i class="bi bi-cash-stack text-info"></i> <?= money($kpi_pres_monto) ?> comprometidos</div>
                </div>
                <div class="metric-icon-box cyan"><i class="bi bi-calculator"></i></div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <h5>En Adquisiciones / Listas</h5>
                    <div class="metric-number"><?= $kpi_adq_user ?></div>
                    <div class="metric-sub" style="color: var(--success);"><i class="bi bi-check2-circle"></i> Trámite avanzado</div>
                </div>
                <div class="metric-icon-box green"><i class="bi bi-bag-check"></i></div>
            </div>
        </div>

        <!-- PANEL DE TABLA Y FILTROS -->
        <div class="saas-panel">
            <!-- TOOLBAR FILTROS RÁPIDOS -->
            <form method="GET" action="mis_solicitudes.php" id="formFiltros">
                <div class="panel-toolbar">
                    <div class="filters-left">
                        <div class="saas-search-box">
                            <i class="bi bi-search"></i>
                            <input type="text" name="f_q" value="<?= htmlspecialchars($f_q) ?>" class="saas-search-input" placeholder="Buscar por ID, título o motivo..." onchange="this.form.submit()">
                        </div>

                        <select name="f_tipo" class="saas-select" onchange="this.form.submit()">
                            <option value="">Todos los Tipos de Compra</option>
                            <?php foreach($tipos_compra_filtro as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= $f_tipo == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <select name="f_estado" class="saas-select" onchange="this.form.submit()">
                            <option value="">Todos los Estados</option>
                            <?php foreach($estados_filtro as $e): ?>
                                <option value="<?= $e['codigo'] ?>" <?= $f_estado == $e['codigo'] ? 'selected' : '' ?>><?= htmlspecialchars($e['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <?php if ($f_q || $f_tipo || $f_estado || $f_desde || $f_hasta): ?>
                            <a href="mis_solicitudes.php" class="btn-saas btn-saas-secondary btn-saas-sm">Limpiar</a>
                        <?php endif; ?>
                        <button type="submit" class="btn-saas btn-saas-primary btn-saas-sm">Aplicar</button>
                    </div>
                </div>

                <!-- PANEL FECHAS DESPLEGABLE -->
                <div id="panelFechas" class="advanced-filters-panel <?= ($f_desde || $f_hasta) ? '' : 'd-none' ?>">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-sm-6 col-md-3">
                            <label class="form-label text-muted fw-bold small text-uppercase" style="font-size: 10px;">Fecha Desde</label>
                            <input type="date" name="f_desde" value="<?= htmlspecialchars($f_desde) ?>" class="form-control form-control-sm">
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <label class="form-label text-muted fw-bold small text-uppercase" style="font-size: 10px;">Fecha Hasta</label>
                            <input type="date" name="f_hasta" value="<?= htmlspecialchars($f_hasta) ?>" class="form-control form-control-sm">
                        </div>
                        <div class="col-12 col-md-3">
                            <button type="submit" class="btn-saas btn-saas-primary btn-saas-sm">Filtrar por Rango</button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- TABLA DE DATOS -->
            <div class="table-responsive">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th style="width: 170px;">Folio / Fecha</th>
                            <th style="width: 110px;">Prioridad</th>
                            <th>Requerimiento / Justificación</th>
                            <th style="width: 140px;">Clasificación</th>
                            <th style="width: 200px;">Estado de Trámite</th>
                            <th style="width: 140px; text-align: right;">Monto Total</th>
                            <th style="width: 140px; text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($mis_solicitudes) > 0): ?>
                            <?php foreach ($mis_solicitudes as $row): 
                                $items_json = htmlspecialchars(json_encode($row['items_detalle']), ENT_QUOTES, 'UTF-8');
                                $row_json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                                
                                // Prioridad styling
                                $prio_name = strtolower($row['prioridad_nombre'] ?? '');
                                $is_alta = strpos($prio_name, 'alta') !== false || strpos($prio_name, 'urgente') !== false;
                            ?>
                            <tr>
                                <!-- FOLIO & FECHA -->
                                <td>
                                    <button type="button" class="saas-code-btn" onclick="abrirDrawerDetalle(<?= $row_json ?>)">
                                        <?= htmlspecialchars($row['codigo_interno']) ?>
                                    </button>
                                    <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">
                                        <i class="bi bi-calendar3 me-1" style="font-size: 10px;"></i>
                                        <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?>
                                    </div>
                                </td>

                                <!-- PRIORIDAD -->
                                <td>
                                    <span class="priority-indicator" style="background: <?= $is_alta ? '#fef2f2' : '#f1f5f9' ?>; color: <?= $is_alta ? '#dc2626' : '#475569' ?>;">
                                        <span class="prio-dot" style="background: <?= $is_alta ? '#ef4444' : '#94a3b8' ?>;"></span>
                                        <?= htmlspecialchars($row['prioridad_nombre']) ?>
                                    </span>
                                </td>

                                <!-- REQUERIMIENTO / GLOSA -->
                                <td>
                                    <div style="font-weight: 600; color: var(--text-main); line-height: 1.35; margin-bottom: 2px;">
                                        <?= htmlspecialchars($row['titulo_compra'] ?? 'Sin Título') ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 6px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        <?= htmlspecialchars($row['motivo_compra']) ?>
                                    </div>
                                    
                                    <div class="d-flex align-items-center gap-2 flex-wrap" style="font-size: 11px;">
                                        <span class="text-muted" style="font-weight: 500;">
                                            <i class="bi bi-tag me-1"></i>CC: <?= htmlspecialchars($row['cc_nombre']) ?>
                                        </span>

                                        <button type="button" onclick="abrirModalVerItems(<?= $items_json ?>, '<?= htmlspecialchars($row['codigo_interno']) ?>')" class="btn-saas btn-saas-secondary btn-saas-sm py-0.5 px-2" style="font-size: 10.5px;">
                                            <i class="bi bi-list-check"></i> Ítems (<?= count($row['items_detalle']) ?>)
                                        </button>

                                        <?php if($row['docs_adjuntos']): 
                                            $docs_array = explode('||', $row['docs_adjuntos']);
                                            $docs_count = count($docs_array);
                                            $docs_json = htmlspecialchars(json_encode($docs_array), ENT_QUOTES, 'UTF-8');
                                        ?>
                                            <button type="button" onclick="abrirModalAdjuntos(<?= $docs_json ?>, '<?= htmlspecialchars($row['codigo_interno']) ?>', <?= $row['id'] ?>)" class="btn-saas btn-saas-secondary btn-saas-sm py-0.5 px-2 text-primary" style="font-size: 10.5px;">
                                                <i class="bi bi-paperclip"></i> Adjuntos (<?= $docs_count ?>)
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- TIPO COMPRA -->
                                <td>
                                    <span class="saas-badge saas-badge-neutral">
                                        <?= htmlspecialchars($row['tipo_nombre']) ?>
                                    </span>
                                </td>

                                <!-- ESTADO & TRAZABILIDAD -->
                                <td>
                                    <?php 
                                        $est_cod = $row['estado_actual'];
                                        $badge_type = 'saas-badge-neutral';
                                        if (in_array($est_cod, ['FINALIZADO', 'ADJUDICADO'])) $badge_type = 'saas-badge-success';
                                        elseif (in_array($est_cod, ['EN_COTIZACION_ADQ', 'EN_GESTION_ADQUISICIONES', 'VB_PRESUPUESTO'])) $badge_type = 'saas-badge-info';
                                        elseif (in_array($est_cod, ['EN_EVALUACION_OFERTAS', 'EN_REVISION_JEFATURA'])) $badge_type = 'saas-badge-warning';
                                        elseif (in_array($est_cod, ['RECHAZADO', 'ANULADO', 'EN_CORRECCION'])) $badge_type = 'saas-badge-danger';
                                    ?>
                                    <span class="saas-badge <?= $badge_type ?>">
                                        <?= htmlspecialchars($row['estado_nombre']) ?>
                                    </span>

                                    <div class="mt-1.5">
                                        <button type="button" onclick="verTrazabilidad(<?= (int)$row['id'] ?>)" class="btn btn-link p-0 text-decoration-none d-flex align-items-center gap-1" style="font-size: 11.5px; color: var(--primary);">
                                            <i class="bi bi-clock-history"></i> Ver Historial
                                        </button>
                                    </div>

                                    <?php if($row['folio_opi']): ?>
                                        <div style="font-size: 11px; color: var(--success); font-weight: 600; margin-top: 2px;">
                                            <i class="bi bi-check-circle-fill"></i> OPI: <?= htmlspecialchars($row['folio_opi']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if($row['orden_compra_numero']): ?>
                                        <div style="font-size: 11px; color: var(--primary); font-weight: 600;">
                                            <i class="bi bi-cart-check"></i> OC: <?= htmlspecialchars($row['orden_compra_numero']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- MONTO -->
                                <td style="text-align: right;">
                                    <div class="price-text">
                                        <?= money($row['monto_definitivo'] ?? $row['monto_estimado']) ?>
                                    </div>
                                    <div style="font-size: 10px; color: var(--text-light); text-transform: uppercase;">CLP Estimado</div>
                                </td>

                                <!-- ACCIONES -->
                                <td style="text-align: right;">
                                    <div class="d-flex align-items-center justify-content-end gap-1.5">
                                        <button type="button" class="btn-saas btn-saas-secondary btn-saas-sm" onclick="abrirDrawerDetalle(<?= $row_json ?>)" title="Ver Detalle Completo">
                                            <i class="bi bi-eye"></i>
                                        </button>

                                        <?php if (in_array($row['estado_actual'], ['BORRADOR', 'EN_REVISION_JEFATURA', 'EN_CORRECCION'])): ?>
                                            <a href="editar_solicitud.php?id=<?= $row['id'] ?>" class="btn-saas btn-saas-secondary btn-saas-sm" title="Editar Solicitud">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="mis_solicitudes.php?anular_id=<?= $row['id'] ?>" onclick="return confirm('¿Confirma anular definitivamente esta solicitud?')" class="btn-saas btn-saas-danger btn-saas-sm" title="Anular">
                                                <i class="bi bi-trash3"></i>
                                            </a>
                                        <?php elseif ($row['estado_actual'] === 'EN_EVALUACION_OFERTAS'): ?>
                                            <button onclick="abrirModalAdjudicar(<?= $row_json ?>)" class="btn-saas btn-saas-primary btn-saas-sm">
                                                <i class="bi bi-star-fill"></i> Adjudicar
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="p-5 text-center text-muted" style="font-style: italic;">
                                    <i class="bi bi-inbox fs-2 d-block text-secondary mb-2"></i>
                                    No se encontraron solicitudes con los criterios de búsqueda aplicados.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- PAGINACIÓN -->
            <div class="panel-footer">
                <div>
                    Mostrando página <strong><?= $page ?></strong> de <strong><?= max(1, $total_pages) ?></strong> (Total: <?= $total_records ?> solicitudes)
                </div>

                <?php if ($total_pages > 1): ?>
                <nav>
                    <ul class="saas-pagination">
                        <?php if ($page > 1): ?>
                            <li><a class="saas-page-link" href="<?= $base_url . ($page - 1) ?>" title="Anterior">&laquo;</a></li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li><a class="saas-page-link <?= $i == $page ? 'active' : '' ?>" href="<?= $base_url . $i ?>"><?= $i ?></a></li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li><a class="saas-page-link" href="<?= $base_url . ($page + 1) ?>" title="Siguiente">&raquo;</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- DRAWER LATERAL DE DETALLE (SLIDE-OVER SAAS) -->
    <div class="saas-drawer-backdrop" id="drawerBackdrop" onclick="cerrarDrawer()"></div>
    <div class="saas-drawer" id="drawerDetalle">
        <div class="drawer-header">
            <div>
                <span class="saas-badge saas-badge-info" id="dFolio">OPI</span>
                <h3 style="font-size: 16px; font-weight: 700; margin: 4px 0 0;" id="dTitulo">Detalle de Requerimiento</h3>
            </div>
            <button class="btn-saas btn-saas-secondary btn-saas-sm" onclick="cerrarDrawer()"><i class="bi bi-x-lg"></i></button>
        </div>

        <div class="drawer-body">
            <div class="mb-3">
                <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-light);">Motivo / Justificación</label>
                <p style="font-size: 13.5px; color: var(--text-main); font-weight: 500; margin-top: 4px;" id="dMotivo"></p>
            </div>

            <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px; margin-bottom: 20px;">
                <div class="d-flex justify-content-between mb-2">
                    <span style="color: var(--text-muted); font-size: 12px;">Centro de Costo:</span>
                    <strong style="font-size: 12px;" id="dCC"></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span style="color: var(--text-muted); font-size: 12px;">Tipo de Compra:</span>
                    <strong style="font-size: 12px;" id="dTipo"></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span style="color: var(--text-muted); font-size: 12px;">Monto Total Estimado:</span>
                    <strong style="color: var(--primary); font-size: 13px;" id="dMonto"></strong>
                </div>
            </div>

            <h4 style="font-size: 13px; font-weight: 700; margin-bottom: 10px;">Ítems del Requerimiento</h4>
            <div style="border: 1px solid var(--border-color); border-radius: var(--radius-sm); overflow: hidden; margin-bottom: 20px;">
                <table style="width: 100%; font-size: 12px; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 1px solid var(--border-color);">
                            <th style="padding: 8px 10px; font-weight: 700;">Descripción</th>
                            <th style="padding: 8px 10px; text-align: center; font-weight: 700;">Cant.</th>
                            <th style="padding: 8px 10px; text-align: right; font-weight: 700;">Total</th>
                        </tr>
                    </thead>
                    <tbody id="dItemsBody"></tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <button type="button" id="dBtnTrazabilidad" class="btn-saas btn-saas-secondary btn-saas-sm w-100 justify-content-center">
                    <i class="bi bi-clock-history"></i> Ver Línea de Tiempo Completa
                </button>
            </div>
        </div>

        <div class="drawer-footer">
            <button class="btn-saas btn-saas-secondary w-100 justify-content-center" onclick="cerrarDrawer()">Cerrar</button>
        </div>
    </div>

    <!-- MODAL ADJUNTOS -->
    <div class="modal fade" id="modalAdjuntos" tabindex="-1" aria-labelledby="modalAdjuntosLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3 shadow border-0">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="modalAdjuntosLabel" style="font-size: 15px;">Documentos Adjuntos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="bg-light border p-3 rounded-3 mb-3 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-uppercase text-muted fw-bold" style="font-size: 9px;">Expediente:</span>
                            <div id="modalAdjuntosCodigo" class="fw-bold text-primary"></div>
                        </div>
                        <a id="btnDescargarZip" href="#" class="btn-saas btn-saas-primary btn-saas-sm">
                            <i class="bi bi-download"></i> Bajar ZIP
                        </a>
                    </div>
                    <div id="modalAdjuntosLista" class="d-flex flex-column gap-2 overflow-y-auto" style="max-height: 320px;"></div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn-saas btn-saas-secondary btn-saas-sm" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DETALLE ÍTEMS -->
    <div class="modal fade" id="modalVerItems" tabindex="-1" aria-labelledby="modalVerItemsLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-3 shadow border-0">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="modalVerItemsLabel" style="font-size: 15px;">Ítems del Requerimiento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="bg-light border p-3 rounded-3 mb-3">
                        <span class="text-uppercase text-muted fw-bold" style="font-size: 9px;">Expediente:</span>
                        <div id="modalVerItemsCodigo" class="fw-bold text-primary"></div>
                    </div>
                    
                    <div class="table-responsive rounded-3 border">
                        <table class="table table-hover align-middle mb-0" style="font-size: 12.5px;">
                            <thead class="table-light text-uppercase small text-secondary">
                                <tr>
                                    <th class="p-3">Descripción</th>
                                    <th class="p-3 text-center" style="width: 100px;">Cant.</th>
                                    <th class="p-3 text-end" style="width: 140px;">Precio Unit.</th>
                                    <th class="p-3 text-end" style="width: 150px;">Total</th>
                                </tr>
                            </thead>
                            <tbody id="modalVerItemsBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn-saas btn-saas-secondary btn-saas-sm" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODALES EXISTENTES DE TRAZABILIDAD Y ADJUDICACIÓN -->
    <?php include __DIR__ . '/modal_trazabilidad.php'; ?>
    <?php include __DIR__ . '/modal_adjudicacion.php'; ?>

    <!-- Inyección Automática de Token CSRF en Formularios POST -->
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('form[method="post"], form[method="POST"]').forEach(form => {
            if (!form.querySelector('input[name="csrf_token"]')) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'csrf_token';
                input.value = '<?= $_SESSION['csrf_token'] ?? '' ?>';
                form.appendChild(input);
            }
        });
    });
    </script>

    <script>
        function escapeHTML(str) { 
            return str ? str.replace(/[&<>'"]/g, tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag])) : ''; 
        }

        const currencyFormatter = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', minimumFractionDigits: 0 });
        function formatCLP(val) { return currencyFormatter.format(val); }

        let modalAdjuntosObj = null;
        let modalVerItemsObj = null;

        document.addEventListener('DOMContentLoaded', () => {
            modalAdjuntosObj = new bootstrap.Modal(document.getElementById('modalAdjuntos'));
            modalVerItemsObj = new bootstrap.Modal(document.getElementById('modalVerItems'));
        });

        function toggleFiltrosAvanzados() {
            const panel = document.getElementById('panelFechas');
            if (panel) panel.classList.toggle('d-none');
        }

        // CONTROL DEL SLIDE-OVER DRAWER
        function abrirDrawerDetalle(row) {
            document.getElementById('dFolio').innerText = row.codigo_interno;
            document.getElementById('dTitulo').innerText = row.titulo_compra || 'Sin Título';
            document.getElementById('dMotivo').innerText = row.motivo_compra || '';
            document.getElementById('dCC').innerText = row.cc_nombre || '-';
            document.getElementById('dTipo').innerText = row.tipo_nombre || '-';
            document.getElementById('dMonto').innerText = formatCLP(row.monto_definitivo || row.monto_estimado || 0);

            const tbody = document.getElementById('dItemsBody');
            tbody.innerHTML = '';
            const items = row.items_detalle || [];
            if (items.length === 0) {
                tbody.innerHTML = `<tr><td colspan="3" style="padding: 12px; text-align: center; color: var(--text-muted);">Sin ítems registrados</td></tr>`;
            } else {
                items.forEach(it => {
                    const cant = parseFloat(it.cantidad);
                    const prec = parseFloat(it.precio_unitario);
                    tbody.innerHTML += `
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 8px 10px;">${escapeHTML(it.descripcion)}</td>
                            <td style="padding: 8px 10px; text-align: center;">${cant} <span style="font-size: 11px; color: var(--text-muted);">${escapeHTML(it.unidad_medida || '')}</span></td>
                            <td style="padding: 8px 10px; text-align: right; font-weight: 600;">${formatCLP(cant * prec)}</td>
                        </tr>
                    `;
                });
            }

            document.getElementById('dBtnTrazabilidad').onclick = () => {
                cerrarDrawer();
                verTrazabilidad(row.id);
            };

            document.getElementById('drawerBackdrop').classList.add('show');
            document.getElementById('drawerDetalle').classList.add('open');
        }

        function cerrarDrawer() {
            document.getElementById('drawerBackdrop').classList.remove('show');
            document.getElementById('drawerDetalle').classList.remove('open');
        }

        // MODAL ADJUNTOS
        function abrirModalAdjuntos(docsArray, codigo, expId) {
            document.getElementById('modalAdjuntosCodigo').innerText = codigo;
            document.getElementById('btnDescargarZip').href = '?descargar_zip=' + expId;
            const lista = document.getElementById('modalAdjuntosLista');
            lista.innerHTML = '';

            docsArray.forEach(docStr => {
                const parts = docStr.split('::');
                if (parts.length >= 4) {
                    const ruta = parts[0];
                    const nombre = parts[1];
                    const tipo = parts[2].replace(/_/g, ' ');
                    const fecha = parts[3];

                    const a = document.createElement('a');
                    a.href = ruta;
                    a.target = '_blank';
                    a.className = 'd-flex align-items-center justify-content-between p-2.5 bg-light border rounded-3 text-decoration-none text-dark';
                    a.innerHTML = `
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <i class="bi bi-file-earmark-arrow-down text-primary fs-5"></i>
                            <div class="text-truncate">
                                <div class="fw-semibold text-truncate small" style="max-width: 300px;">${escapeHTML(nombre)}</div>
                                <div class="text-muted text-uppercase" style="font-size: 9px;">${escapeHTML(tipo)} &middot; ${fecha}</div>
                            </div>
                        </div>
                        <i class="bi bi-box-arrow-up-right text-muted"></i>
                    `;
                    lista.appendChild(a);
                }
            });
            modalAdjuntosObj.show();
        }

        // MODAL VER ÍTEMS
        function abrirModalVerItems(items, codigo) {
            document.getElementById('modalVerItemsCodigo').innerText = codigo;
            const tbody = document.getElementById('modalVerItemsBody');
            tbody.innerHTML = '';

            if (!items || items.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" class="p-4 text-center text-muted">No hay ítems registrados.</td></tr>`;
            } else {
                items.forEach(it => {
                    const cant = parseFloat(it.cantidad);
                    const prec = parseFloat(it.precio_unitario);
                    tbody.innerHTML += `
                        <tr>
                            <td class="p-3 text-secondary fw-semibold">${escapeHTML(it.descripcion)}</td>
                            <td class="p-3 text-center fw-bold text-dark">${cant} <span class="text-muted d-block" style="font-size: 10px;">${escapeHTML(it.unidad_medida || '')}</span></td>
                            <td class="p-3 text-end text-muted">${formatCLP(prec)}</td>
                            <td class="p-3 text-end fw-bold text-dark">${formatCLP(cant * prec)}</td>
                        </tr>
                    `;
                });
            }
            modalVerItemsObj.show();
        }
    </script>
</body>
</html>