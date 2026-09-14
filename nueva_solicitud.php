<?php 
// nueva_solicitud.php - Vista UI Renovada (Estilo Clean SaaS Minimalist)
require_once __DIR__ . '/nueva_solicitud_controller.php'; 

$listado_prov_json = [];
foreach($mis_proveedores as $p) {
    $listado_prov_json[] = [
        'id' => (int)$p['id'],
        'rut' => $p['rut'],
        'razon_social' => $p['razon_social'],
        'direccion' => $p['direccion'] ?? '',
        'frecuente' => true
    ];
}
foreach($otros_proveedores as $p) {
    $ya_esta = false;
    foreach($listado_prov_json as $lp) {
        if ($lp['id'] == $p['id']) {
            $ya_esta = true;
            break;
        }
    }
    if (!$ya_esta) {
        $listado_prov_json[] = [
            'id' => (int)$p['id'],
            'rut' => $p['rut'],
            'razon_social' => $p['razon_social'],
            'direccion' => $p['direccion'] ?? '',
            'frecuente' => false
        ];
    }
}

$user_name = $_SESSION['user_name'] ?? 'Usuario';
$user_rol = $_SESSION['user_rol'] ?? '';
$es_jefe = $_SESSION['es_jefe'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Solicitud OPI - Sistema OPI</title>

    <!-- Tipografía Moderna Limpia con Números Estándar (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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

        * { box-sizing: border-box; }

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

        /* TOPBAR SAAS */
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

        /* MAIN CONTAINER */
        .saas-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 28px 24px 80px;
        }

        /* HEADER ROW */
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
            padding: 9px 16px;
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

        /* BANNER PRESUPUESTO */
        .cc-info-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 16px 20px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-subtle);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .cc-meta-badge {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .cc-icon-box {
            width: 42px;
            height: 42px;
            border-radius: var(--radius-sm);
            background: var(--primary-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        /* CARDS FORMULARIO (PASOS) */
        .saas-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-subtle);
            margin-bottom: 24px;
            overflow: hidden;
        }

        .saas-card-header {
            padding: 16px 22px;
            border-bottom: 1px solid var(--border-color);
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .step-pill {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: var(--primary);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
            margin-right: 10px;
        }

        .saas-card-body {
            padding: 24px;
        }

        /* FORM INPUTS */
        .form-label-saas {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 6px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .form-control-saas, .form-select-saas {
            width: 100%;
            padding: 9px 13px;
            font-size: 13.5px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            background: #ffffff;
            color: var(--text-main);
            outline: none;
            transition: all 0.15s;
        }
        .form-control-saas:focus, .form-select-saas:focus {
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
        }

        /* TABLA DE ÍTEMS */
        table.saas-items-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.saas-items-table th {
            background: #f8fafc;
            padding: 10px 14px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border-color);
        }
        table.saas-items-table td {
            padding: 10px 14px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .summary-box {
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 18px 24px;
            margin-top: 16px;
        }

        /* DROPZONE SUBIDA */
        .dropzone-saas {
            border: 2px dashed var(--border-color);
            border-radius: var(--radius-md);
            padding: 30px;
            text-align: center;
            background: #fafafa;
            cursor: pointer;
            transition: all 0.2s;
        }
        .dropzone-saas:hover {
            border-color: var(--primary);
            background: var(--primary-light);
        }

        .file-pill {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            margin-top: 8px;
            font-size: 12.5px;
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

        <nav class="saas-nav-links d-none d-lg-flex">
            <a href="mis_solicitudes.php" class="saas-nav-item"><i class="bi bi-journal-text"></i> Mis Solicitudes</a>
            <a href="nueva_solicitud.php" class="saas-nav-item active"><i class="bi bi-plus-circle"></i> Nueva Solicitud</a>
            <?php if($es_jefe == 1 || $user_rol === 'JEFE_UNIDAD' || $user_rol === 'ADMIN_MUNICIPAL' || $user_rol === 'SYSADMIN'): ?>
                <a href="jefatura.php" class="saas-nav-item"><i class="bi bi-shield-check"></i> V°B° Jefatura</a>
            <?php endif; ?>
            <?php if($user_rol === 'PRESUPUESTO' || $user_rol === 'ADMIN_MUNICIPAL' || $user_rol === 'SYSADMIN'): ?>
                <a href="control_presupuestario.php" class="saas-nav-item"><i class="bi bi-calculator"></i> Presupuesto</a>
                <a href="centros_de_costo.php" class="saas-nav-item"><i class="bi bi-wallet2"></i> Centros Costo</a>
            <?php endif; ?>
        </nav>

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
                    <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2" href="mis_solicitudes.php"><i class="bi bi-journal-text"></i> Mis Solicitudes</a></li>
                    <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2 active bg-primary text-white" href="nueva_solicitud.php"><i class="bi bi-plus-circle"></i> Nueva Solicitud</a></li>
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
                </ul>
            </div>

            <a href="mis_solicitudes.php" class="btn-saas btn-saas-secondary btn-saas-sm">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </header>

    <main class="saas-container">
        
        <!-- HEADER -->
        <div class="page-header-row">
            <div class="page-title">
                <div class="breadcrumbs">
                    <a href="mis_solicitudes.php">Mis Solicitudes</a>
                    <i class="bi bi-chevron-right" style="font-size: 9px;"></i>
                    <span class="current">Nueva Solicitud OPI</span>
                </div>
                <h2>Nueva Orden de Pedido Interno</h2>
                <p>Ingrese los antecedentes, justificación técnica y desglose de ítems requeridos.</p>
            </div>

            <div>
                <a href="mis_solicitudes.php" class="btn-saas btn-saas-secondary">
                    <i class="bi bi-x-lg"></i> Cancelar
                </a>
            </div>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-4 rounded-3 shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div><?= htmlspecialchars($mensaje) ?></div>
            </div>
        <?php endif; ?>

        <?php if (!$centro_costo): ?>
            <div class="alert alert-warning text-center p-5 rounded-4 shadow-sm mb-4" role="alert">
                <i class="bi bi-exclamation-circle fs-1 text-warning mb-3 d-block"></i>
                <h4 class="alert-heading fw-bold">Sin Presupuesto Asignado</h4>
                <p class="mb-0">Contacte al área de Presupuesto para configurar el Centro de Costo de su Unidad.</p>
            </div>
        <?php else: ?>

        <!-- BANNER DE CENTRO DE COSTO ASIGNADO -->
        <div class="cc-info-card">
            <div class="cc-meta-badge">
                <div class="cc-icon-box"><i class="bi bi-bank"></i></div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Imputación Presupuestaria de Unidad</div>
                    <div style="font-size: 15px; font-weight: 800; color: var(--text-main);"><?= htmlspecialchars($centro_costo['nombre']) ?></div>
                </div>
            </div>

            <div style="background: var(--bg-body); border: 1px solid var(--border-color); padding: 8px 14px; border-radius: var(--radius-sm); font-size: 12.5px;">
                <span style="color: var(--text-muted);">Centro de Costos:</span>
                <strong style="color: var(--primary); font-weight: 700;">#<?= htmlspecialchars($centro_costo['codigo_cuenta']) ?></strong>
            </div>
        </div>

        <!-- FORMULARIO PRINCIPAL -->
        <form method="POST" action="nueva_solicitud.php" enctype="multipart/form-data" id="formCompra" onsubmit="return procesarEnvio(event)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="accion" value="crear">
            <input type="hidden" name="prioridad_id" value="1">

            <!-- PASO 1: ANTECEDENTES GENERALES -->
            <div class="saas-card">
                <div class="saas-card-header">
                    <div class="d-flex align-items-center">
                        <span class="step-pill">1</span>
                        <div>
                            <h3 style="font-size: 15px; font-weight: 700; margin: 0;">Antecedentes del Trámite</h3>
                            <p style="font-size: 12px; color: var(--text-muted); margin: 0;">Defina el título, justificación, tipo de compra y proyecto asociado.</p>
                        </div>
                    </div>
                </div>

                <div class="saas-card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label-saas">Título del Requerimiento <span class="text-danger">*</span></label>
                            <input type="text" name="titulo_compra" required class="form-control-saas" placeholder="Ej: Adquisición de Insumos de Oficina y Tóner para Atención Vecinal" value="<?= htmlspecialchars($post_titulo_compra) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label-saas">Justificación Técnica / Fundamentación <span class="text-danger">*</span></label>
                            <textarea name="motivo" required rows="3" class="form-control-saas" placeholder="Explique la necesidad y destino de los bienes o servicios solicitados..."><?= htmlspecialchars($post_motivo) ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label-saas">Modalidad / Tipo de Compra <span class="text-danger">*</span></label>
                            <select name="tipo_compra_id" id="selTipoCompra" required class="form-select-saas" onchange="evaluarFormularioReactivo()">
                                <option value="">-- Seleccione Tipo de Compra --</option>
                                <?php foreach($tipos_compra as $t): ?>
                                    <option value="<?= $t['id'] ?>" <?= $post_tipo_compra == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label-saas">Rango de Monto Estimado (Art. 10) <span class="text-danger">*</span></label>
                            <select name="rango_utm_id" required class="form-select-saas">
                                <option value="">-- Seleccione el Rango UTM --</option>
                                <?php foreach($rangos_utm as $r): ?>
                                    <option value="<?= $r['id'] ?>" <?= $post_rango_utm == $r['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($r['nombre']) ?> (<?= $r['min_utm'] ?> - <?= $r['max_utm'] ? $r['max_utm'].' UTM' : 'y más' ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="p-3 bg-light border rounded-3">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label-saas">Proyecto (Plan Anual de Compras) <span class="text-danger">*</span></label>
                                        <input type="text" name="plan_compras_proyecto" required class="form-control-saas" placeholder="Nombre del programa o proyecto" value="<?= htmlspecialchars($post_plan_proyecto) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label-saas">Ítem N° (Plan Anual de Compras) <span class="text-danger">*</span></label>
                                        <input type="number" name="plan_compras_item" required min="1" step="1" class="form-control-saas" placeholder="Ej: 1" value="<?= htmlspecialchars($post_plan_item) ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PANEL MONTO DISPONIBLE PARA COTIZACIÓN / LICITACIÓN -->
                        <div class="col-12 d-none" id="divMontoDisponible">
                            <div style="background: var(--primary-light); border: 1px solid #bfdbfe; border-radius: var(--radius-sm); padding: 18px;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label-saas" style="color: var(--primary); margin: 0;">
                                        <i class="bi bi-cash-stack me-1"></i> Monto Máximo Estimado para Cotización <span class="text-danger">*</span>
                                    </label>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <input type="radio" class="btn-check" name="disp_imp_tipo" id="disp_neto" value="NETO" <?= ($post_tipo_impuesto === 'NETO' || empty($post_tipo_impuesto)) ? 'checked' : '' ?> onchange="cambiarRegimenImpuesto('NETO')">
                                        <label class="btn btn-outline-primary btn-sm py-0.5 px-2" for="disp_neto" style="font-size: 11px;">Neto</label>
                                        
                                        <input type="radio" class="btn-check" name="disp_imp_tipo" id="disp_bruto" value="IVA_INCLUIDO" <?= $post_tipo_impuesto === 'IVA_INCLUIDO' ? 'checked' : '' ?> onchange="cambiarRegimenImpuesto('IVA_INCLUIDO')">
                                        <label class="btn btn-outline-primary btn-sm py-0.5 px-2" for="disp_bruto" style="font-size: 11px;">IVA Incluido</label>
                                    </div>
                                </div>

                                <div class="row g-3 align-items-center">
                                    <div class="col-12 col-md-5">
                                        <div class="input-group">
                                            <span class="input-group-text bg-white fw-bold">$</span>
                                            <input type="text" name="monto_disponible_neto" id="inpMontoDisponible" class="form-control fw-bold text-primary fs-6" placeholder="0" oninput="handleMontoInput(this)" value="<?= htmlspecialchars($post_monto_disponible_neto ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-7">
                                        <div class="d-flex justify-content-between p-2 bg-white rounded border small">
                                            <span>Neto: <strong id="dispPreviewNeto">$ 0</strong></span>
                                            <span>IVA (19%): <strong id="dispPreviewIva">$ 0</strong></span>
                                            <span class="text-primary fw-bold">Total: <strong id="dispPreviewTotal">$ 0</strong></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PANEL PROVEEDOR REACTIVO -->
                        <div class="col-12" id="panel-proveedor" style="display: none;">
                            <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 16px;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label-saas mb-0"><i class="bi bi-person-check me-1"></i> Proveedor Asignado / Cotizado</label>
                                    <button type="button" class="btn-saas btn-saas-secondary btn-saas-sm" onclick="abrirModalProveedor()">
                                        <i class="bi bi-search"></i> <span id="btnSelectProvText">Buscar Proveedor</span>
                                    </button>
                                </div>

                                <div id="provResumenVacio" class="text-muted small">
                                    <em>No ha seleccionado un proveedor específico aún.</em>
                                </div>

                                <div id="provResumenDetalle" class="d-none bg-white p-3 border rounded-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold text-primary" id="provResumenRazonSocial"></div>
                                        <div class="text-muted small">RUT: <span id="provResumenRut" style="font-weight: 600;"></span></div>
                                        <div class="text-muted small" id="provResumenDireccion"></div>
                                    </div>
                                    <button type="button" class="btn btn-link text-danger p-0" onclick="deseleccionarProveedor()" title="Quitar"><i class="bi bi-trash"></i></button>
                                </div>

                                <input type="hidden" name="proveedor_id" id="hiddenProveedorId" value="<?= htmlspecialchars($post_proveedor_id ?? '') ?>">
                            </div>
                        </div>

                        <!-- PANEL CONTRATO SUMINISTRO -->
                        <div class="col-12" id="panel-suministro" style="display: none;">
                            <label class="form-label-saas">ID o N° Decreto Contrato de Suministro <span class="text-danger">*</span></label>
                            <input type="text" name="id_contrato_suministro" id="inpSuministro" class="form-control-saas" placeholder="Ej: Decreto Alcaldicio N° 1234/2026" value="<?= htmlspecialchars($post_id_contrato_suministro ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- PASO 2: DESGLOSE DE ÍTEMS -->
            <div class="saas-card" id="cardItems">
                <div class="saas-card-header">
                    <div class="d-flex align-items-center">
                        <span class="step-pill">2</span>
                        <div>
                            <h3 style="font-size: 15px; font-weight: 700; margin: 0;">Desglose de Ítems Solicitados</h3>
                            <p style="font-size: 12px; color: var(--text-muted); margin: 0;">Indique los productos o servicios con su respectiva cuenta presupuestaria.</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <div class="btn-group btn-group-sm">
                            <input type="radio" class="btn-check" name="tipo_impuesto" id="reg_neto" value="NETO" <?= ($post_tipo_impuesto === 'NETO' || empty($post_tipo_impuesto)) ? 'checked' : '' ?> onchange="cambiarRegimenImpuesto('NETO')">
                            <label class="btn btn-outline-secondary btn-sm py-1 px-2.5" for="reg_neto" style="font-size: 11px;">Precios Netos</label>

                            <input type="radio" class="btn-check" name="tipo_impuesto" id="reg_iva" value="IVA_INCLUIDO" <?= $post_tipo_impuesto === 'IVA_INCLUIDO' ? 'checked' : '' ?> onchange="cambiarRegimenImpuesto('IVA_INCLUIDO')">
                            <label class="btn btn-outline-secondary btn-sm py-1 px-2.5" for="reg_iva" style="font-size: 11px;">Con IVA Incluido</label>
                        </div>

                        <button type="button" class="btn-saas btn-saas-primary btn-saas-sm" onclick="agregarItemFila()">
                            <i class="bi bi-plus-lg"></i> Agregar Ítem
                        </button>
                    </div>
                </div>

                <div class="saas-card-body p-0">
                    <div class="table-responsive">
                        <table class="saas-items-table" id="tablaItems">
                            <thead>
                                <tr>
                                    <th style="min-width: 260px;">Descripción del Producto / Servicio</th>
                                    <th style="width: 140px;" class="th-cm d-none">ID Convenio Marco</th>
                                    <th style="width: 110px;">Unidad</th>
                                    <th style="width: 90px; text-align: center;">Cant.</th>
                                    <th style="width: 130px; text-align: right;">Precio Unit.</th>
                                    <th style="min-width: 200px;">Cuenta Presupuestaria</th>
                                    <th style="width: 130px; text-align: right;">Total Línea</th>
                                    <th style="width: 50px; text-align: center;"></th>
                                </tr>
                            </thead>
                            <tbody id="tbodyItems"></tbody>
                        </table>
                    </div>

                    <!-- TOTALES RESUMEN -->
                    <div class="p-4 bg-light border-top">
                        <div class="row justify-content-end">
                            <div class="col-12 col-md-5">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Subtotal Neto:</span>
                                    <strong id="lblSubtotalNeto" style="font-weight: 700;">$ 0</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">IVA Estimado (19%):</span>
                                    <strong id="lblIvaTotal" style="font-weight: 700;">$ 0</strong>
                                </div>
                                <div class="d-flex justify-content-between pt-2 border-top">
                                    <span class="fw-bold fs-6">Total Solicitud:</span>
                                    <strong class="fs-5 text-primary" id="lblGranTotal" style="font-weight: 800;">$ 0</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PASO 3: DOCUMENTOS Y ADJUNTOS -->
            <div class="saas-card">
                <div class="saas-card-header">
                    <div class="d-flex align-items-center">
                        <span class="step-pill">3</span>
                        <div>
                            <h3 style="font-size: 15px; font-weight: 700; margin: 0;">Documentos & Antecedentes Adjuntos</h3>
                            <p style="font-size: 12px; color: var(--text-muted); margin: 0;">Adjunte cotizaciones previas, términos de referencia o especificaciones técnicas.</p>
                        </div>
                    </div>
                </div>

                <div class="saas-card-body">
                    <div class="dropzone-saas" onclick="document.getElementById('inputArchivos').click()">
                        <i class="bi bi-cloud-arrow-up text-primary fs-1 mb-2 d-block"></i>
                        <div class="fw-bold">Haga clic aquí para seleccionar archivos</div>
                        <div class="text-muted small">Formatos permitidos: PDF, Word, Excel, JPG, PNG (Máx 25MB c/u)</div>
                    </div>
                    <input type="file" name="archivos_adjuntos[]" id="inputArchivos" multiple class="d-none" onchange="mostrarArchivosSeleccionados(this)">

                    <div id="listaArchivos" class="mt-3"></div>
                </div>
            </div>

            <!-- BOTONES DE ACCIÓN FINAL -->
            <div class="d-flex justify-content-end gap-3 pt-2">
                <a href="mis_solicitudes.php" class="btn-saas btn-saas-secondary">Cancelar</a>
                <button type="submit" class="btn-saas btn-saas-primary" style="padding: 10px 24px; font-size: 14px;">
                    <i class="bi bi-send-check"></i> Emitir Requerimiento OPI
                </button>
            </div>
        </form>

        <?php endif; ?>
    </main>

    <!-- MODAL SELECCIÓN / CREACIÓN DE PROVEEDOR -->
    <div class="modal fade" id="modalProveedor" tabindex="-1" aria-labelledby="modalProveedorLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-3 shadow border-0">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="modalProveedorLabel">Búsqueda & Registro de Proveedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label-saas">Buscar por RUT o Razón Social</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                            <input type="text" id="filtroProvInput" class="form-control" placeholder="Escriba RUT o nombre..." oninput="filtrarProveedores(this.value)">
                        </div>
                    </div>

                    <div style="max-height: 240px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: var(--radius-sm); margin-bottom: 20px;" id="listaProveedoresContainer">
                        <!-- Render dinámico -->
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small text-muted">¿El proveedor no está registrado en el sistema?</span>
                        <button type="button" class="btn-saas btn-saas-secondary btn-saas-sm" onclick="toggleFormNuevoProv()">
                            <i class="bi bi-plus-lg"></i> Pre-registrar Nuevo Proveedor
                        </button>
                    </div>

                    <div id="formNuevoProv" class="d-none mt-3 p-3 bg-light border rounded-3">
                        <h6 class="fw-bold small mb-2">Datos del Nuevo Proveedor</h6>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <input type="text" id="nProvRut" placeholder="RUT (Ej: 76.123.456-7)" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-8">
                                <input type="text" id="nProvNombre" placeholder="Razón Social / Nombre" class="form-control form-control-sm">
                            </div>
                            <div class="col-12">
                                <input type="text" id="nProvDir" placeholder="Dirección / Contacto" class="form-control form-control-sm">
                            </div>
                            <div class="col-12 text-end mt-2">
                                <button type="button" class="btn-saas btn-saas-primary btn-saas-sm" onclick="confirmarNuevoProveedor()">Usar Este Nuevo Proveedor</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn-saas btn-saas-secondary btn-saas-sm" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- DATOS EN JAVASCRIPT -->
    <script>
        const proveedoresData = <?= json_encode($listado_prov_json) ?>;
        const mapaTipos = <?= json_encode($mapa_tipos) ?>;
        const mapaRequiereCot = <?= json_encode($mapa_requiere_cotizacion) ?>;
        const cuentasPresupuestarias = <?= json_encode($cuentas_disponibles) ?>;
        const itemsPrevios = <?= json_encode($items_old) ?>;

        let regimenActual = '<?= $post_tipo_impuesto ?>';
        let modalProvInstance = null;

        document.addEventListener('DOMContentLoaded', () => {
            modalProvInstance = new bootstrap.Modal(document.getElementById('modalProveedor'));
            renderProveedoresLista(proveedoresData);

            if (itemsPrevios && itemsPrevios.length > 0) {
                itemsPrevios.forEach(it => agregarItemFila(it));
            } else {
                agregarItemFila();
            }

            evaluarFormularioReactivo();
        });

        const clpFormatter = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', minimumFractionDigits: 0 });
        function formatCLP(v) { return clpFormatter.format(v); }

        function toggleFormNuevoProv() {
            document.getElementById('formNuevoProv').classList.toggle('d-none');
        }

        function abrirModalProveedor() {
            modalProvInstance.show();
        }

        function renderProveedoresLista(data) {
            const cont = document.getElementById('listaProveedoresContainer');
            cont.innerHTML = '';
            if (data.length === 0) {
                cont.innerHTML = '<div class="p-3 text-center text-muted small">No se encontraron proveedores.</div>';
                return;
            }
            data.forEach(p => {
                const div = document.createElement('div');
                div.className = 'd-flex justify-content-between align-items-center p-2.5 border-bottom bg-white';
                div.style.cursor = 'pointer';
                div.innerHTML = `
                    <div>
                        <div class="fw-bold text-dark small">${escapeHtml(p.razon_social)}</div>
                        <div class="text-muted" style="font-size: 11px;">RUT: <strong>${escapeHtml(p.rut)}</strong></div>
                    </div>
                    <button type="button" class="btn-saas btn-saas-secondary btn-saas-sm">Seleccionar</button>
                `;
                div.onclick = () => seleccionarProveedor(p);
                cont.appendChild(div);
            });
        }

        function filtrarProveedores(q) {
            const needle = q.toLowerCase();
            const filtered = proveedoresData.filter(p => p.razon_social.toLowerCase().includes(needle) || p.rut.toLowerCase().includes(needle));
            renderProveedoresLista(filtered);
        }

        function seleccionarProveedor(p) {
            document.getElementById('hiddenProveedorId').value = p.id;
            document.getElementById('provResumenRazonSocial').innerText = p.razon_social;
            document.getElementById('provResumenRut').innerText = p.rut;
            document.getElementById('provResumenDireccion').innerText = p.direccion || '';
            document.getElementById('provResumenVacio').classList.add('d-none');
            document.getElementById('provResumenDetalle').classList.remove('d-none');
            document.getElementById('btnSelectProvText').innerText = 'Cambiar';
            modalProvInstance.hide();
        }

        function deseleccionarProveedor() {
            document.getElementById('hiddenProveedorId').value = '';
            document.getElementById('provResumenVacio').classList.remove('d-none');
            document.getElementById('provResumenDetalle').classList.add('d-none');
            document.getElementById('btnSelectProvText').innerText = 'Buscar Proveedor';
        }

        function confirmarNuevoProveedor() {
            const rut = document.getElementById('nProvRut').value.trim();
            const nom = document.getElementById('nProvNombre').value.trim();
            const dir = document.getElementById('nProvDir').value.trim();
            if (!rut || !nom) {
                alert('Debe ingresar RUT y Razón Social del nuevo proveedor.');
                return;
            }
            seleccionarProveedor({ id: 'NUEVO', rut: rut, razon_social: nom + ' (Nuevo Pre-registro)', direccion: dir });
        }

        // EVALUACIÓN REACTIVA DE TIPO DE COMPRA
        function evaluarFormularioReactivo() {
            const tcId = document.getElementById('selTipoCompra').value;
            const tcCodigo = mapaTipos[tcId] || '';
            const reqCot = mapaRequiereCot[tcId] == 1;

            const panelProv = document.getElementById('panel-proveedor');
            const panelSum = document.getElementById('panel-suministro');
            const divMontoDisp = document.getElementById('divMontoDisponible');
            const thCm = document.querySelectorAll('.th-cm');
            const tdCm = document.querySelectorAll('.td-cm');

            if (reqCot) {
                divMontoDisp.classList.remove('d-none');
            } else {
                divMontoDisp.classList.add('d-none');
            }

            if (tcCodigo === 'CONTRATO_SUMINISTRO') {
                panelSum.style.display = 'block';
                panelProv.style.display = 'block';
            } else {
                panelSum.style.display = 'none';
            }

            if (['TRATO_DIRECTO', 'CONVENIO_MARCO'].includes(tcCodigo)) {
                panelProv.style.display = 'block';
            } else if (tcCodigo !== 'CONTRATO_SUMINISTRO') {
                panelProv.style.display = 'none';
            }

            if (tcCodigo === 'CONVENIO_MARCO') {
                thCm.forEach(el => el.classList.remove('d-none'));
                tdCm.forEach(el => el.classList.remove('d-none'));
            } else {
                thCm.forEach(el => el.classList.add('d-none'));
                tdCm.forEach(el => el.classList.add('d-none'));
            }

            recalcularTotales();
        }

        // TABLA DINÁMICA DE ÍTEMS
        function agregarItemFila(data = null) {
            const tbody = document.getElementById('tbodyItems');
            const tcId = document.getElementById('selTipoCompra').value;
            const isCm = (mapaTipos[tcId] || '') === 'CONVENIO_MARCO';

            let opcionesCuentas = '<option value="">-- Imputación --</option>';
            cuentasPresupuestarias.forEach(c => {
                const sel = (data && data.cuenta_id == c.id) ? 'selected' : '';
                opcionesCuentas += `<option value="${c.id}" ${sel}>${escapeHtml(c.codigo)} - ${escapeHtml(c.nombre)}</option>`;
            });

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <input type="text" name="desc[]" required class="form-control-saas" placeholder="Descripción clara del ítem" value="${escapeHtml(data ? data.desc : '')}">
                </td>
                <td class="td-cm ${isCm ? '' : 'd-none'}">
                    <input type="number" name="id_producto_cm[]" class="form-control-saas" placeholder="ID CM" value="${escapeHtml(data ? data.id_cm : '')}">
                </td>
                <td>
                    <select name="uni[]" class="form-select-saas">
                        <option value="UNIDAD">UNIDAD</option>
                        <option value="GLOBAL">GLOBAL</option>
                        <option value="MES">MES</option>
                        <option value="HORA">HORA</option>
                        <option value="METRO">METRO</option>
                        <option value="KILO">KILO</option>
                    </select>
                </td>
                <td>
                    <input type="number" name="cant[]" min="0.01" step="any" required class="form-control-saas text-center item-cant" value="${data ? data.cant : '1'}" oninput="recalcularTotales()">
                </td>
                <td>
                    <input type="number" name="prec[]" min="0" step="any" required class="form-control-saas text-end item-prec" value="${data ? data.prec : '0'}" oninput="recalcularTotales()">
                </td>
                <td>
                    <select name="cuenta_id[]" required class="form-select-saas">${opcionesCuentas}</select>
                </td>
                <td class="text-end fw-bold item-total-linea">$ 0</td>
                <td class="text-center">
                    <button type="button" class="btn btn-link text-danger p-0" onclick="eliminarFila(this)"><i class="bi bi-trash"></i></button>
                </td>
            `;
            tbody.appendChild(tr);
            recalcularTotales();
        }

        function eliminarFila(btn) {
            const tbody = document.getElementById('tbodyItems');
            if (tbody.children.length > 1) {
                btn.closest('tr').remove();
                recalcularTotales();
            } else {
                alert('Debe conservar al menos un ítem.');
            }
        }

        function cambiarRegimenImpuesto(reg) {
            regimenActual = reg;
            recalcularTotales();
        }

        function recalcularTotales() {
            let subtotalNeto = 0;
            let granTotal = 0;

            const rows = document.querySelectorAll('#tbodyItems tr');
            rows.forEach(tr => {
                const cant = parseFloat(tr.querySelector('.item-cant').value) || 0;
                const prec = parseFloat(tr.querySelector('.item-prec').value) || 0;
                const lineaRaw = cant * prec;

                let lineaTotalBruta = 0;
                if (regimenActual === 'NETO') {
                    lineaTotalBruta = Math.round(lineaRaw * 1.19);
                    subtotalNeto += lineaRaw;
                } else {
                    lineaTotalBruta = Math.round(lineaRaw);
                    subtotalNeto += Math.round(lineaRaw / 1.19);
                }

                tr.querySelector('.item-total-linea').innerText = formatCLP(lineaTotalBruta);
                granTotal += lineaTotalBruta;
            });

            const ivaTotal = Math.max(0, granTotal - subtotalNeto);

            document.getElementById('lblSubtotalNeto').innerText = formatCLP(subtotalNeto);
            document.getElementById('lblIvaTotal').innerText = formatCLP(ivaTotal);
            document.getElementById('lblGranTotal').innerText = formatCLP(granTotal);
        }

        function handleMontoInput(inp) {
            let val = inp.value.replace(/\D/g, '');
            if (val === '') val = '0';
            const num = parseInt(val, 10);
            inp.value = num.toLocaleString('es-CL');

            let neto = 0, iva = 0, tot = 0;
            if (regimenActual === 'NETO') {
                neto = num;
                iva = Math.round(num * 0.19);
                tot = neto + iva;
            } else {
                tot = num;
                neto = Math.round(num / 1.19);
                iva = tot - neto;
            }

            document.getElementById('dispPreviewNeto').innerText = formatCLP(neto);
            document.getElementById('dispPreviewIva').innerText = formatCLP(iva);
            document.getElementById('dispPreviewTotal').innerText = formatCLP(tot);
        }

        function mostrarArchivosSeleccionados(input) {
            const cont = document.getElementById('listaArchivos');
            cont.innerHTML = '';
            if (input.files.length > 0) {
                Array.from(input.files).forEach(f => {
                    const pill = document.createElement('div');
                    pill.className = 'file-pill';
                    pill.innerHTML = `
                        <div><i class="bi bi-file-earmark-check text-primary me-2"></i><strong>${escapeHtml(f.name)}</strong> <span class="text-muted">(${(f.size/1024/1024).toFixed(2)} MB)</span></div>
                        <i class="bi bi-check-circle-fill text-success"></i>
                    `;
                    cont.appendChild(pill);
                });
            }
        }

        function procesarEnvio(e) {
            return true;
        }

        function escapeHtml(text) {
            if (!text) return '';
            return String(text).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[m]);
        }
    </script>
</body>
</html>