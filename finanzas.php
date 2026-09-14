<?php 
// finanzas.php - Vista del Módulo de Finanzas (Diseño SaaS Clean Minimalist)
require_once __DIR__ . '/finanzas_controller.php'; 
$user_name = $_SESSION['user_name'] ?? 'Usuario';
$user_rol = $_SESSION['user_rol'] ?? '';
$user_depto = $_SESSION['user_depto_nombre'] ?? 'Dirección de Finanzas';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php 
    $titulo_pagina = "Firma de CDP - Finanzas - Sistema OPI";
    include __DIR__ . '/head.php'; 
    ?>
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
            <a href="mis_solicitudes.php" class="saas-nav-item"><i class="bi bi-journal-text"></i> Mis Solicitudes</a>
            <a href="nueva_solicitud.php" class="saas-nav-item"><i class="bi bi-plus-circle"></i> Nueva Solicitud</a>
            <?php if(isset($_SESSION['es_jefe']) && $_SESSION['es_jefe'] == 1 || $user_rol === 'JEFE_UNIDAD' || $user_rol === 'ADMIN_MUNICIPAL' || $user_rol === 'SYSADMIN'): ?>
                <a href="jefatura.php" class="saas-nav-item"><i class="bi bi-shield-check"></i> V°B° Jefatura</a>
            <?php endif; ?>
            <a href="control_presupuestario.php" class="saas-nav-item"><i class="bi bi-calculator"></i> Presupuesto</a>
            <a href="finanzas.php" class="saas-nav-item active"><i class="bi bi-file-earmark-check"></i> Firma CDP</a>
            <?php if($user_rol === 'ADQUISICIONES' || $user_rol === 'SYSADMIN'): ?>
                <a href="adquisiciones.php" class="saas-nav-item"><i class="bi bi-cart3"></i> Adquisiciones</a>
            <?php endif; ?>
        </nav>

        <!-- Menú y Perfil -->
        <div class="d-flex align-items-center gap-2">
            <div class="dropdown">
                <button class="btn-saas btn-saas-secondary btn-saas-sm dropdown-toggle d-flex align-items-center gap-1.5" type="button" id="dropdownGlobalNav" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-grid-fill text-primary"></i>
                    <span class="d-none d-sm-inline">Módulos</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-light mt-2 p-2" aria-labelledby="dropdownGlobalNav" style="min-width: 250px;">
                    <li><span class="dropdown-header text-uppercase text-secondary fw-bold" style="font-size: 9px;">Panel Principal</span></li>
                    <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard OPIs</a></li>
                    <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2" href="mis_solicitudes.php"><i class="bi bi-journal-text"></i> Mis Solicitudes</a></li>
                    <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2" href="nueva_solicitud.php"><i class="bi bi-plus-circle"></i> Nueva Solicitud</a></li>
                    <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2" href="subrogancia.php"><i class="bi bi-person-gear"></i> Configurar Suplente</a></li>
                    
                    <li><hr class="dropdown-divider"></li>
                    <li><span class="dropdown-header text-uppercase text-secondary fw-bold" style="font-size: 9px;">Presupuesto y Finanzas</span></li>
                    <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2" href="control_presupuestario.php"><i class="bi bi-calculator"></i> VB Presupuestario</a></li>
                    <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2" href="centros_de_costo.php"><i class="bi bi-wallet2"></i> Centros de Costo</a></li>
                    <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2" href="mantenedor_cuentas.php"><i class="bi bi-list-columns-reverse"></i> Cuentas Presupuestarias</a></li>
                    <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2 active bg-primary text-white" href="finanzas.php"><i class="bi bi-file-earmark-check"></i> Firma de CDP</a></li>
                </ul>
            </div>

            <div class="user-pill d-none d-sm-flex">
                <div class="user-avatar-circle"><?= strtoupper(substr($user_name, 0, 2)) ?></div>
                <div class="d-none d-md-block text-start pe-2">
                    <div class="fw-bold text-dark text-truncate" style="max-width: 140px; font-size: 12px;"><?= htmlspecialchars($user_name) ?></div>
                    <div class="text-muted text-truncate" style="max-width: 140px; font-size: 10.5px;"><?= htmlspecialchars($user_depto) ?></div>
                </div>
            </div>

            <a href="logout.php" class="btn-saas btn-saas-secondary btn-saas-sm" title="Cerrar Sesión">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </header>

    <div class="saas-container">

        <!-- MENSAJES DE ALERTA -->
        <?php if($mensaje): ?>
            <?php 
            $alertClass = ($tipo_mensaje === 'error') ? 'danger' : (($tipo_mensaje === 'warning') ? 'warning' : 'success');
            $iconClass = ($tipo_mensaje === 'error') ? 'exclamation-triangle-fill' : (($tipo_mensaje === 'warning') ? 'arrow-counterclockwise' : 'check-circle-fill');
            ?>
            <div class="alert alert-<?= $alertClass ?> d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
                <i class="bi bi-<?= $iconClass ?> shrink-0 fs-5"></i>
                <div class="small fw-semibold"><?= htmlspecialchars($mensaje) ?></div>
            </div>
        <?php endif; ?>

        <!-- ============================================================== -->
        <!-- VISTA A: LISTADO DE BANDEJA FINANZAS (TABS, FILTROS Y TABLA)    -->
        <!-- ============================================================== -->
        <?php if($vista !== 'revisar'): ?>
            
            <!-- HEADER Y TÍTULO -->
            <div class="page-header-row">
                <div>
                    <div class="breadcrumbs">
                        <a href="index.php">Inicio</a>
                        <i class="bi bi-chevron-right"></i>
                        <span class="current">Finanzas</span>
                    </div>
                    <div class="page-title">
                        <h2>Firma de Certificados de Disponibilidad Presupuestaria (CDP)</h2>
                        <p>Dirección de Administración y Finanzas (DAF). Firma digital oficial y carga de certificados SMC.</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button onclick="toggleFiltros()" class="btn-saas btn-saas-secondary">
                        <i class="bi bi-funnel"></i>
                        <span>Filtros</span>
                    </button>
                </div>
            </div>

            <!-- TARJETAS KPI DE GESTIÓN -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-info">
                        <h5>Pendientes de Certificado</h5>
                        <div class="metric-number"><?= number_format($count_pendientes, 0, ',', '.') ?></div>
                        <div class="metric-sub text-warning fw-bold">
                            <i class="bi bi-hourglass-split"></i> En espera de firma DAF
                        </div>
                    </div>
                    <div class="metric-icon-box yellow">
                        <i class="bi bi-file-earmark-check"></i>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-info">
                        <h5>Procesados por Mí</h5>
                        <div class="metric-number"><?= number_format($count_procesados, 0, ',', '.') ?></div>
                        <div class="metric-sub text-success fw-bold">
                            <i class="bi bi-check2-all"></i> CDPs emitidos / visados
                        </div>
                    </div>
                    <div class="metric-icon-box green">
                        <i class="bi bi-shield-check"></i>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-info">
                        <h5>Total de Solicitudes</h5>
                        <div class="metric-number"><?= number_format($count_todas, 0, ',', '.') ?></div>
                        <div class="metric-sub text-muted">
                            <i class="bi bi-diagram-3"></i> Catálogo financiero DAF
                        </div>
                    </div>
                    <div class="metric-icon-box purple">
                        <i class="bi bi-folder2-open"></i>
                    </div>
                </div>
            </div>

            <!-- PANEL PRINCIPAL CON TABS Y TABLA -->
            <div class="saas-panel">
                
                <!-- PESTAÑAS NAVEGABLES (3 TABS) -->
                <div class="saas-tab-nav">
                    <a href="finanzas.php?view=pendientes" class="saas-tab-item <?= $vista === 'pendientes' ? 'active' : '' ?>">
                        <i class="bi bi-clock-history"></i>
                        <span>Pendientes de Certificado</span>
                        <span class="saas-tab-badge"><?= $count_pendientes ?></span>
                    </a>
                    <a href="finanzas.php?view=procesados" class="saas-tab-item <?= $vista === 'procesados' ? 'active' : '' ?>">
                        <i class="bi bi-check2-square"></i>
                        <span>Procesados por Mí</span>
                        <span class="saas-tab-badge"><?= $count_procesados ?></span>
                    </a>
                    <a href="finanzas.php?view=todas" class="saas-tab-item <?= $vista === 'todas' ? 'active' : '' ?>">
                        <i class="bi bi-diagram-3"></i>
                        <span>Todas las Solicitudes</span>
                        <span class="saas-tab-badge"><?= $count_todas ?></span>
                    </a>
                </div>

                <!-- TOOLBAR Y PANEL DE FILTROS -->
                <div id="filtroPanel" class="p-3 bg-light border-bottom <?= ($f_q || $f_tipo || $f_estado || $f_desde || $f_hasta) ? '' : 'd-none' ?>">
                    <form method="GET" action="finanzas.php">
                        <input type="hidden" name="view" value="<?= htmlspecialchars($vista) ?>">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-3">
                                <label class="form-label-saas mb-1" style="font-size: 11px;">Buscar (Código / Título / Solicitante)</label>
                                <input type="text" name="f_q" value="<?= htmlspecialchars($f_q) ?>" placeholder="Ej: EXP-2026..." class="form-control-saas py-1.5">
                            </div>
                            <div class="col-12 col-sm-6 col-md-2">
                                <label class="form-label-saas mb-1" style="font-size: 11px;">Tipo de Compra</label>
                                <select name="f_tipo" class="form-control-saas py-1.5">
                                    <option value="">Todos</option>
                                    <?php foreach($tipos_compra_filtro as $t): ?>
                                        <option value="<?= $t['id'] ?>" <?= $f_tipo==$t['id']?'selected':'' ?>><?= htmlspecialchars($t['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-md-2">
                                <label class="form-label-saas mb-1" style="font-size: 11px;">Estado</label>
                                <select name="f_estado" class="form-control-saas py-1.5">
                                    <option value="">Todos</option>
                                    <?php foreach($estados_filtro as $e): ?>
                                        <option value="<?= $e['codigo'] ?>" <?= $f_estado==$e['codigo']?'selected':'' ?>><?= htmlspecialchars($e['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-md-3">
                                <div class="row g-1">
                                    <div class="col-6">
                                        <label class="form-label-saas mb-1" style="font-size: 11px;">Desde</label>
                                        <input type="date" name="f_desde" value="<?= htmlspecialchars($f_desde) ?>" class="form-control-saas py-1.5">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label-saas mb-1" style="font-size: 11px;">Hasta</label>
                                        <input type="date" name="f_hasta" value="<?= htmlspecialchars($f_hasta) ?>" class="form-control-saas py-1.5">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-md-2 d-flex gap-2">
                                <a href="finanzas.php?view=<?= htmlspecialchars($vista) ?>" class="btn-saas btn-saas-secondary w-100 justify-content-center py-1.5">Limpiar</a>
                                <button type="submit" class="btn-saas btn-saas-primary w-100 justify-content-center py-1.5">Aplicar</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- TABLA DE SOLICITUDES -->
                <div class="table-responsive">
                    <table class="saas-table" style="min-width: 980px;">
                        <thead>
                            <tr>
                                <th style="width: 170px;">ID / Fecha</th>
                                <th style="min-width: 280px;">Título / Destino / Solicitante</th>
                                <th style="width: 160px;">Clasificación</th>
                                <th style="width: 180px;">Fase / Estado Actual</th>
                                <th style="width: 140px;" class="text-end">Monto Total</th>
                                <th style="width: 140px;" class="text-center">Gestión</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($solicitudes) > 0): ?>
                                <?php foreach($solicitudes as $row): ?>
                                <tr>
                                    <!-- ID y Fecha -->
                                    <td>
                                        <button type="button" onclick="abrirModalVerItems(<?= htmlspecialchars(json_encode($row['items_detalle']), ENT_QUOTES, 'UTF-8') ?>, '<?= $row['codigo_interno'] ?>')" class="saas-code-btn d-block mb-1">
                                            <?= htmlspecialchars($row['codigo_interno']) ?>
                                        </button>
                                        <div class="row-sub d-flex align-items-center gap-1">
                                            <i class="bi bi-clock"></i>
                                            <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?>
                                        </div>
                                        <div class="mt-1.5">
                                            <span class="priority-indicator <?= $row['prioridad_css'] ?>">
                                                <span class="prio-dot"></span>
                                                <?= htmlspecialchars($row['prioridad_nombre']) ?>
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Título y Destino -->
                                    <td>
                                        <div class="row-title mb-1" style="max-width: 420px; font-size: 13.5px;">
                                            <?= htmlspecialchars($row['titulo_compra'] ?? 'Sin Título') ?>
                                        </div>
                                        <div class="row-sub mb-2 text-truncate" style="max-width: 420px; font-size: 12px; color: var(--text-muted);">
                                            <?= htmlspecialchars($row['motivo_compra']) ?>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 flex-wrap" style="font-size: 11px; color: var(--text-muted); font-weight: 500;">
                                            <span class="d-flex align-items-center gap-1"><i class="bi bi-person-fill text-primary"></i> <?= htmlspecialchars($row['solicitante']) ?> (<?= htmlspecialchars($row['unidad_nombre']) ?>)</span>
                                            <span>•</span>
                                            <span class="d-flex align-items-center gap-1"><i class="bi bi-tag-fill text-secondary"></i> CC: <?= htmlspecialchars($row['cc_nombre']) ?></span>
                                        </div>
                                        
                                        <?php if(!empty($row['docs_adjuntos'])): 
                                            $docs_array = explode('||', $row['docs_adjuntos']);
                                            $docs_count = count($docs_array);
                                            $docs_json = htmlspecialchars(json_encode($docs_array), ENT_QUOTES, 'UTF-8');
                                        ?>
                                            <div class="mt-2">
                                                <button type="button" onclick="abrirModalAdjuntos(<?= $docs_json ?>, '<?= htmlspecialchars($row['codigo_interno']) ?>', <?= $row['id'] ?>)" class="btn-saas btn-saas-secondary btn-saas-sm py-0.5 px-2" style="font-size: 11px;">
                                                    <i class="bi bi-paperclip text-primary"></i>
                                                    Archivos (<?= $docs_count ?>)
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Clasificación -->
                                    <td>
                                        <span class="saas-badge saas-badge-neutral d-inline-block mb-1">
                                            <?= htmlspecialchars($row['tipo_compra_nom']) ?>
                                        </span>
                                        <?php if(!empty($row['rango_utm_nombre'])): ?>
                                            <span class="badge bg-light text-secondary border d-block" style="font-size: 9.5px; width: fit-content;">
                                                <?= htmlspecialchars($row['rango_utm_nombre']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Estado y Trazabilidad -->
                                    <td>
                                        <?php 
                                        $st = $row['estado_actual'];
                                        $badgeClass = 'saas-badge-neutral';
                                        if (in_array($st, ['ESPERANDO_CDP_FINANZAS', 'ESPERANDO_CDP_FINANZAS_FINAL'])) $badgeClass = 'saas-badge-warning';
                                        elseif (in_array($st, ['VB_FINANZAS_CDP', 'FINALIZADO', 'ADJUDICADO'])) $badgeClass = 'saas-badge-success';
                                        elseif (in_array($st, ['RECHAZADO', 'ANULADO'])) $badgeClass = 'saas-badge-danger';
                                        ?>
                                        <span class="saas-badge <?= $badgeClass ?> mb-1">
                                            <?= htmlspecialchars($row['estado_nombre']) ?>
                                        </span>
                                        <div>
                                            <button type="button" onclick="verTrazabilidad(<?= (int)$row['id'] ?>)" class="btn btn-link p-0 text-decoration-none d-flex align-items-center gap-1" style="font-size: 11.5px; color: var(--primary);">
                                                <i class="bi bi-clock-history"></i>
                                                Historial
                                            </button>
                                        </div>
                                    </td>

                                    <!-- Monto Total -->
                                    <td class="text-end">
                                        <div class="price-text" style="font-size: 14px;">
                                            <?= money($row['monto_definitivo'] ?? $row['monto_estimado']) ?>
                                        </div>
                                    </td>

                                    <!-- Acción -->
                                    <td class="text-center">
                                        <a href="finanzas.php?view=revisar&id=<?= $row['id'] ?>" class="btn-saas <?= $vista === 'pendientes' ? 'btn-saas-primary' : 'btn-saas-secondary' ?> btn-saas-sm">
                                            <i class="bi bi-file-earmark-check"></i>
                                            <span><?= $vista === 'pendientes' ? 'Gestionar' : 'Ver Detalle' ?></span>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="p-5 text-center text-muted">
                                        <div class="d-flex flex-column align-items-center justify-content-center py-4">
                                            <i class="bi bi-inbox fs-1 text-slate-300 mb-2"></i>
                                            <p class="mb-0 fw-semibold text-secondary">No se encontraron requerimientos registrados en esta bandeja de finanzas.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>

        <?php endif; ?>

        <!-- ============================================================== -->
        <!-- VISTA B: REVISIÓN DE EXPEDIENTE Y FIRMA DE CDP                  -->
        <!-- ============================================================== -->
        <?php if($vista === 'revisar' && isset($expediente)): ?>
            
            <div class="page-header-row mb-4">
                <div>
                    <div class="breadcrumbs">
                        <a href="index.php">Inicio</a>
                        <i class="bi bi-chevron-right"></i>
                        <a href="finanzas.php">Finanzas</a>
                        <i class="bi bi-chevron-right"></i>
                        <span class="current">Revisión #<?= htmlspecialchars($expediente['codigo_interno']) ?></span>
                    </div>
                    <div class="page-title">
                        <div class="d-flex align-items-center gap-2.5 flex-wrap">
                            <h2>Expediente: <span class="text-primary font-monospace">#<?= htmlspecialchars($expediente['codigo_interno']) ?></span></h2>
                            <span class="saas-badge saas-badge-info">
                                Área Finanzas: Emisión y Firma de CDP
                            </span>
                            <button type="button" onclick="verTrazabilidad(<?= (int)$expediente['id'] ?>)" class="btn-saas btn-saas-secondary btn-saas-sm">
                                <i class="bi bi-clock-history text-primary"></i> Ver Historial
                            </button>
                        </div>
                    </div>
                </div>
                <div>
                    <a href="finanzas.php" class="btn-saas btn-saas-secondary">
                        <i class="bi bi-arrow-left"></i> Volver a la Bandeja
                    </a>
                </div>
            </div>

            <div class="row g-4">
                
                <!-- COLUMNA IZQUIERDA: RESUMEN Y ARCHIVOS -->
                <div class="col-lg-4">
                    
                    <!-- CONTEXTO GENERAL -->
                    <div class="saas-card mb-4">
                        <div class="saas-card-header">
                            <h6 class="fw-bold mb-0 text-dark" style="font-size: 13px;">
                                <i class="bi bi-info-circle text-primary me-1.5"></i>
                                Contexto de la Solicitud
                            </h6>
                        </div>
                        <div class="saas-card-body p-3">
                            <div class="d-flex flex-column gap-3 small">
                                
                                <?php if($expediente['titulo_compra']): ?>
                                    <div>
                                        <span class="form-label-saas mb-1">Título de la Compra:</span>
                                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($expediente['titulo_compra']) ?></div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="row g-2">
                                    <div class="col-6">
                                        <span class="form-label-saas mb-1">Unidad:</span>
                                        <div class="text-secondary fw-semibold"><?= htmlspecialchars($expediente['unidad']) ?></div>
                                    </div>
                                    <div class="col-6">
                                        <span class="form-label-saas mb-1">Tipo de Compra:</span>
                                        <div class="text-secondary fw-semibold"><?= htmlspecialchars($expediente['tipo_compra_nom']) ?></div>
                                    </div>
                                </div>

                                <div>
                                    <span class="form-label-saas mb-1">Centro de Costo:</span>
                                    <div class="p-2 bg-light rounded border fw-semibold text-dark">
                                        <span class="badge bg-primary text-white me-1">ID: <?= $expediente['centro_costo_id'] ?></span>
                                        <?= htmlspecialchars($expediente['centro_costo']) ?>
                                    </div>
                                </div>

                                <?php if(!empty($expediente['proveedor_nombre'])): ?>
                                    <div class="p-2.5 bg-success-subtle border border-success-subtle rounded text-success-emphasis">
                                        <span class="form-label-saas mb-1" style="font-size: 9.5px; color: var(--success);">Proveedor Adjudicado:</span>
                                        <div class="fw-bold mb-0.5"><?= htmlspecialchars($expediente['proveedor_nombre']) ?></div>
                                        <div class="small font-monospace text-muted">RUT: <?= htmlspecialchars($expediente['proveedor_rut']) ?></div>
                                    </div>
                                <?php endif; ?>

                                <div class="pt-2 border-top">
                                    <span class="form-label-saas mb-1">Destino de los Bienes / Justificación:</span>
                                    <div class="bg-light p-2.5 rounded border small text-secondary" style="max-height: 180px; overflow-y: auto; font-size: 11.5px; line-height: 1.5;">
                                        <?= nl2br(htmlspecialchars($expediente['motivo_compra'] ?? '')) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DOCUMENTOS ADJUNTOS -->
                    <div class="saas-card">
                        <div class="saas-card-header">
                            <h6 class="fw-bold mb-0 text-dark" style="font-size: 13px;">
                                <i class="bi bi-paperclip text-primary me-1.5"></i>
                                Archivos Adjuntos (<?= count($docs) ?>)
                            </h6>
                        </div>
                        <div class="saas-card-body p-3">
                            <?php if(empty($docs)): ?>
                                <p class="text-muted small mb-0 fst-italic">No hay archivos adjuntos cargados.</p>
                            <?php else: ?>
                                <div class="d-flex flex-column gap-2">
                                    <?php foreach($docs as $doc): ?>
                                        <a href="<?= htmlspecialchars($doc['ruta_archivo']) ?>" target="_blank" class="d-flex align-items-center justify-content-between p-2.5 bg-light border rounded text-decoration-none transition">
                                            <div class="d-flex align-items-center gap-2 min-w-0 flex-grow-1">
                                                <i class="bi bi-file-earmark-text text-primary fs-5 shrink-0"></i>
                                                <div class="text-truncate">
                                                    <p class="mb-0 text-truncate small fw-bold text-dark"><?= htmlspecialchars($doc['nombre_original']) ?></p>
                                                    <p class="mb-0 text-uppercase text-muted" style="font-size: 9.5px;"><?= str_replace('_', ' ', $doc['tipo_doc']) ?></p>
                                                </div>
                                            </div>
                                            <i class="bi bi-arrow-right-short text-secondary fs-4 shrink-0"></i>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- COLUMNA DERECHA: TABLA DE CUENTAS E IMPUTACIÓN -->
                <div class="col-lg-8">
                    
                    <div class="saas-card mb-4">
                        <div class="saas-card-header">
                            <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2" style="font-size: 13.5px;">
                                <i class="bi bi-diagram-3 text-primary"></i>
                                Imputación Contable
                            </h6>
                            <div class="text-end">
                                <span class="form-label-saas mb-0 text-end" style="font-size: 10px;">Monto Total</span>
                                <div class="h5 fw-bold text-primary mb-0 price-text"><?= money($expediente['monto_definitivo'] ?? $expediente['monto_estimado']) ?></div>
                            </div>
                        </div>
                        
                        <div class="saas-card-body p-3 bg-light">
                            <div class="d-flex flex-column gap-2.5">
                                <?php foreach($items as $it): 
                                    $costo_linea = $it['cantidad'] * $it['precio_unitario'];
                                ?>
                                    <div class="bg-white border rounded p-3 shadow-sm">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                            <div>
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <span class="badge bg-light text-dark border font-monospace fw-bold" style="font-size: 11.5px;"><?= $it['cuenta_codigo'] ?></span>
                                                    <?php if($it['ag_codigo']): ?>
                                                        <span class="badge bg-secondary-subtle text-secondary-emphasis" style="font-size: 9px;">AG: <?= $it['ag_codigo'] ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-muted text-uppercase mt-1" style="font-size: 10.5px; font-weight: 600;" title="<?= $it['cuenta_nombre'] ?>">
                                                    <?= htmlspecialchars($it['cuenta_nombre']) ?>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <span class="text-muted d-block text-uppercase fw-bold" style="font-size: 9px;">Total Línea</span>
                                                <span class="fw-bold price-text text-dark" style="font-size: 14px;">
                                                    <?= money($costo_linea) ?>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="bg-light p-2.5 rounded">
                                            <div class="d-flex justify-content-between align-items-center gap-3">
                                                <div class="text-dark small fw-semibold"><?= htmlspecialchars($it['descripcion']) ?></div>
                                                <div class="text-secondary small fw-bold text-nowrap text-end">
                                                    <?= floatval($it['cantidad']) ?> <span class="text-muted small fw-normal"><?= $it['unidad_medida'] ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- ACCIONES DE RESOLUCIÓN Y FIRMA DE CDP -->
                    <div class="saas-card">
                        <div class="saas-card-header">
                            <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2" style="font-size: 13.5px;">
                                <i class="bi bi-file-earmark-check text-primary"></i>
                                Firma de Certificado de Disponibilidad (CDP) - Finanzas
                            </h6>
                        </div>
                        <div class="saas-card-body p-4">
                            
                            <?php if($es_accionable): ?>
                                <p class="text-secondary small mb-3">Revise los antecedentes presupuestarios y proceda a estampar la firma electrónica en el Certificado de Disponibilidad (CDP).</p>
                                
                                <?php 
                                $transiciones = obtener_transiciones_disponibles($pdo, $expediente['id']); 
                                $doc_borrador = null;
                                $doc_situacion = null;
                                $doc_opi_visada = null;
                                foreach ($docs as $d) {
                                    if ($d['tipo_doc'] === 'CDP_BORRADOR' && !$doc_borrador) $doc_borrador = $d;
                                    if ($d['tipo_doc'] === 'SITUACION_PRESUPUESTARIA' && !$doc_situacion) $doc_situacion = $d;
                                    if ($d['tipo_doc'] === 'OPI_FIRMADA_PDF' && !$doc_opi_visada) $doc_opi_visada = $d;
                                }

                                $t_aprobar = null;
                                foreach ($transiciones as $t) {
                                    if ($t['accion_codigo'] === 'APROBAR' || $t['accion_codigo'] === 'FIRMAR_CDP_FINANZAS') {
                                        $t_aprobar = $t;
                                        break;
                                    }
                                }
                                ?>

                                <!-- PASO 1: REVISIÓN DE DOCUMENTOS DE RESPALDO -->
                                <div class="saas-card border bg-light mb-4">
                                    <div class="saas-card-header py-2.5">
                                        <span class="text-dark fw-bold" style="font-size: 13px;">
                                            <i class="bi bi-file-earmark-ruled text-primary me-1.5"></i>
                                            1. Documentos de Respaldo Presupuestario
                                        </span>
                                    </div>
                                    <div class="saas-card-body p-3">
                                        <div class="d-flex flex-column gap-2 bg-white p-2.5 rounded border">
                                            <?php if($doc_borrador): ?>
                                                <a href="<?= htmlspecialchars($doc_borrador['ruta_archivo']) ?>" target="_blank" class="btn-saas btn-saas-secondary text-start justify-content-between py-2 w-100">
                                                    <span><i class="bi bi-file-earmark-pdf-fill text-danger me-2 fs-5 align-middle"></i> <strong>Borrador de CDP</strong> (Emitido por Control Presupuestario)</span>
                                                    <i class="bi bi-box-arrow-up-right"></i>
                                                </a>
                                            <?php else: ?>
                                                <div class="alert alert-warning py-2 px-3 mb-0 small d-flex align-items-center gap-2">
                                                    <i class="bi bi-exclamation-triangle-fill fs-5 shrink-0"></i>
                                                    <div><strong>Atención:</strong> Control Presupuestario no adjuntó archivo de Borrador de CDP. Puede solicitarlo devolviendo la solicitud o adjuntar directamente el certificado desde SMC.</div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if($doc_situacion): ?>
                                                <a href="<?= htmlspecialchars($doc_situacion['ruta_archivo']) ?>" target="_blank" class="btn-saas btn-saas-secondary text-start justify-content-between py-2 w-100">
                                                    <span><i class="bi bi-file-earmark-text-fill text-info me-2 fs-5 align-middle"></i> <strong>Situación Presupuestaria de Gastos</strong></span>
                                                    <i class="bi bi-box-arrow-up-right"></i>
                                                </a>
                                            <?php endif; ?>

                                            <?php if($doc_opi_visada): ?>
                                                <a href="<?= htmlspecialchars($doc_opi_visada['ruta_archivo']) ?>" target="_blank" class="btn-saas btn-saas-secondary text-start justify-content-between py-2 w-100">
                                                    <span><i class="bi bi-file-earmark-check-fill text-success me-2 fs-5 align-middle"></i> <strong>OPI con V°B° Presupuestario (2/3)</strong></span>
                                                    <i class="bi bi-box-arrow-up-right"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- PASO 2: FIRMA DIGITAL FIRMAGOB DEL CDP -->
                                <div class="saas-card border-primary-subtle mb-4" style="background-color: #f8faff;">
                                    <div class="saas-card-body p-3 text-center">
                                        <h6 class="fw-bold text-dark mb-1 d-flex align-items-center justify-content-center gap-2">
                                            <i class="bi bi-shield-lock-fill text-primary"></i>
                                            2. Firma Electrónica Oficial del CDP
                                        </h6>
                                        <p class="text-secondary small mb-3" style="font-size: 11.5px;">Estampe su Firma Electrónica Avanzada Oficial FirmaGob en el Certificado de Disponibilidad Presupuestaria.</p>
                                        
                                        <?php if ($t_aprobar): ?>
                                            <button type="button" onclick="abrirModalFirmaGob({expediente_id: <?= $expediente['id'] ?>, transicion_id: <?= $t_aprobar['id'] ?>, etapa: 'CDP_FINANZAS', codigo_interno: '<?= htmlspecialchars($expediente['codigo_interno']) ?>', monto: '<?= $expediente['monto_definitivo'] ?: $expediente['monto_estimado'] ?>', doc_titulo: 'Certificado de Disponibilidad Presupuestaria (CDP)'})" class="btn-saas btn-saas-primary py-2.5 px-4 justify-content-center fw-bold w-100 fs-6 shadow-sm">
                                                <i class="bi bi-fingerprint fs-5"></i>
                                                <span>Firmar CDP Oficial con FirmaGob (OTP)</span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- ALTERNATIVA: SUBIDA MANUAL SMC -->
                                <div class="accordion mb-4" id="accFirmaManual">
                                    <div class="accordion-item border rounded overflow-hidden">
                                        <h2 class="accordion-header" id="headingManual">
                                            <button class="accordion-button collapsed py-2.5 px-3 bg-light text-secondary small fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseManual" aria-expanded="false" aria-controls="collapseManual" style="font-size: 11.5px;">
                                                <i class="bi bi-upload me-2 text-primary"></i> Alternativa: Cargar CDP Firmado desde Sistema SMC (DocDigital / Externo)
                                            </button>
                                        </h2>
                                        <div id="collapseManual" class="accordion-collapse collapse" aria-labelledby="headingManual" data-bs-parent="#accFirmaManual">
                                            <div class="accordion-body p-3 bg-white">
                                                <form method="POST" enctype="multipart/form-data">
                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                                    <input type="hidden" name="expediente_id" value="<?= $expediente['id'] ?>">
                                                    <input type="hidden" name="accion" value="cargar_cdp_manual">
                                                    <?php if ($t_aprobar): ?>
                                                        <input type="hidden" name="transicion_id" value="<?= $t_aprobar['id'] ?>">
                                                    <?php endif; ?>
                                                    
                                                    <label class="form-label-saas mb-1">Adjuntar Certificado PDF Emitido y Firmado:</label>
                                                    <div class="input-group mb-2">
                                                        <input type="file" name="archivo_cdp" accept="application/pdf" class="form-control form-control-sm" required>
                                                        <button type="submit" class="btn-saas btn-saas-primary btn-saas-sm">
                                                            <i class="bi bi-cloud-arrow-up-fill"></i> Cargar CDP y Finalizar
                                                        </button>
                                                    </div>
                                                    <span class="text-muted small" style="font-size: 10.5px;"><i class="bi bi-info-circle me-1"></i> Use esta vía si el certificado fue firmado externamente fuera del portal FirmaGob.</span>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- FORMULARIO DE DEVOLUCIÓN Y RECHAZO -->
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="expediente_id" value="<?= $expediente['id'] ?>">

                                    <div class="border-top pt-3">
                                        <label class="form-label-saas mb-1">Reparos y Observaciones (Requerido para Devolver o Rechazar)</label>
                                        <textarea name="motivo_rechazo" rows="3" placeholder="Indique las razones de devolución u observaciones para Control Presupuestario..." class="form-control-saas mb-3 bg-light"></textarea>
                                        
                                        <div class="row g-2">
                                            <!-- Botones de Devolución -->
                                            <?php foreach ($transiciones as $t): 
                                                if ($t['accion_codigo'] === 'DEVOLVER'):
                                            ?>
                                                <div class="col-sm-6">
                                                    <button type="submit" name="transicion_id" value="<?= $t['id'] ?>" formnovalidate onclick="return confirm('¿Confirma devolver la solicitud a Control Presupuestario?')" class="btn-saas btn-saas-secondary w-100 justify-content-center py-2">
                                                        <i class="bi bi-arrow-counterclockwise text-warning"></i>
                                                        <?= htmlspecialchars($t['accion_label']) ?>
                                                    </button>
                                                </div>
                                            <?php endif; endforeach; ?>

                                            <!-- Botones de Rechazo -->
                                            <?php foreach ($transiciones as $t): 
                                                if ($t['accion_codigo'] === 'RECHAZAR'):
                                            ?>
                                                <div class="col-sm-6">
                                                    <button type="submit" name="transicion_id" value="<?= $t['id'] ?>" formnovalidate onclick="return confirm('¿Confirma rechazar definitivamente la solicitud?')" class="btn-saas btn-saas-danger w-100 justify-content-center py-2">
                                                        <i class="bi bi-x-circle text-danger"></i>
                                                        <?= htmlspecialchars($t['accion_label']) ?>
                                                    </button>
                                                </div>
                                            <?php endif; endforeach; ?>
                                        </div>
                                    </div>
                                </form>
                            
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <div class="p-3 bg-success-subtle text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 54px; height: 54px;">
                                        <i class="bi bi-check-lg fs-2"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark mb-1">CDP Tramitado</h5>
                                    <p class="text-secondary small mb-4">El certificado fue emitido y cargado de forma exitosa.</p>
                                    
                                    <div class="bg-light rounded p-3 border d-inline-block text-start mx-auto" style="min-width: 280px;">
                                        <span class="form-label-saas mb-1" style="font-size: 9.5px;">Estado Actual del Trámite</span>
                                        <span class="fw-bold text-dark fs-6 d-block"><?= htmlspecialchars($expediente['estado_nombre']) ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>

                </div>
            </div>

        <?php endif; ?>

    </div>

    <!-- MODAL ADJUNTOS -->
    <div class="modal fade" id="modalAdjuntos" tabindex="-1" aria-labelledby="modalAdjuntosLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3 shadow border-0">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark" id="modalAdjuntosLabel" style="font-size: 15px;">Documentos Adjuntos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="bg-light border p-3 rounded mb-3 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
                        <div>
                            <span class="form-label-saas mb-0" style="font-size: 10px;">Expediente:</span>
                            <div id="modalAdjuntosCodigo" class="fw-bold text-dark font-monospace"></div>
                        </div>
                        <a id="btnDescargarZip" href="#" class="btn-saas btn-saas-primary btn-saas-sm">
                            <i class="bi bi-download"></i>
                            Bajar ZIP
                        </a>
                    </div>
                    
                    <div id="modalAdjuntosLista" class="d-flex flex-column gap-2 overflow-y-auto" style="max-height: 350px;"></div>
                </div>
                <div class="modal-footer border-top py-2.5">
                    <button type="button" class="btn-saas btn-saas-secondary btn-saas-sm" data-bs-dismiss="modal">Cerrar Visor</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DETALLE ÍTEMS -->
    <div class="modal fade" id="modalVerItems" tabindex="-1" aria-labelledby="modalVerItemsLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-3 shadow border-0">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark" id="modalVerItemsLabel" style="font-size: 15px;">Detalle de Ítems del Requerimiento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="bg-light border p-3 rounded mb-3">
                        <span class="form-label-saas mb-0" style="font-size: 10px;">Expediente:</span>
                        <div id="modalVerItemsCodigo" class="fw-bold text-primary font-monospace"></div>
                    </div>
                    
                    <div class="table-responsive rounded border">
                        <table class="saas-table mb-0">
                            <thead>
                                <tr>
                                    <th>Descripción del Producto / Servicio</th>
                                    <th class="text-center" style="width: 100px;">Cant.</th>
                                    <th class="text-end" style="width: 140px;">Valor Unitario</th>
                                    <th class="text-end" style="width: 150px;">Total Línea</th>
                                </tr>
                            </thead>
                            <tbody id="modalVerItemsBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-top py-2.5">
                    <button type="button" class="btn-saas btn-saas-secondary btn-saas-sm" data-bs-dismiss="modal">Cerrar Visor</button>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/modal_trazabilidad.php'; ?>
    <?php include __DIR__ . '/components/modal_firmagob.php'; ?>

    <script>
    function escapeHTML(str) { 
        return str ? str.replace(/[&<>'"]/g, tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag])) : ''; 
    }

    const formatter = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', minimumFractionDigits: 0 });
    function formatCurrency(value) {
        return formatter.format(value);
    }

    let modalAdjuntosInstance = null;
    let modalVerItemsInstance = null;

    document.addEventListener('DOMContentLoaded', () => {
        const elAdj = document.getElementById('modalAdjuntos');
        const elItems = document.getElementById('modalVerItems');
        if (elAdj) modalAdjuntosInstance = new bootstrap.Modal(elAdj);
        if (elItems) modalVerItemsInstance = new bootstrap.Modal(elItems);
    });

    function toggleFiltros() {
        const panel = document.getElementById('filtroPanel');
        if (!panel) return;
        panel.classList.toggle('d-none');
    }

    function abrirModalAdjuntos(docsArray, codigo, expId) {
        document.getElementById('modalAdjuntosCodigo').innerText = codigo;
        document.getElementById('btnDescargarZip').href = '?descargar_zip=' + expId;
        
        const listaContainer = document.getElementById('modalAdjuntosLista');
        listaContainer.innerHTML = ''; 

        docsArray.forEach(docStr => {
            const parts = docStr.split('::');
            if(parts.length >= 4) {
                const ruta = parts[0];
                const nombreOriginal = parts[1];
                const tipoDoc = parts[2].replace(/_/g, ' '); 
                const fecha = parts[3];
                
                const isAnulada = parts[2] === 'OPI_ANULADA';
                const titleClass = isAnulada ? 'text-danger text-decoration-line-through' : 'text-dark fw-bold';
                const subtitleClass = isAnulada ? 'text-danger' : 'text-muted';
                
                const link = document.createElement('a');
                link.href = ruta;
                link.target = '_blank';
                link.title = nombreOriginal;
                link.className = 'd-flex align-items-center justify-content-between p-2.5 bg-light border rounded text-decoration-none transition mb-2';
                
                link.innerHTML = `
                    <div class="d-flex align-items-center gap-2 min-w-0 flex-1">
                        <i class="bi bi-file-earmark-text text-primary fs-5 shrink-0"></i>
                        <div class="text-truncate flex-1">
                            <p class="mb-0 text-truncate small ${titleClass}" style="max-width: 320px;">${nombreOriginal}</p>
                            <p class="mb-0 small text-uppercase ${subtitleClass}" style="font-size: 9.5px;">${tipoDoc} - ${fecha}</p>
                        </div>
                    </div>
                    <i class="bi bi-arrow-right-short text-secondary fs-4 shrink-0"></i>
                `;
                listaContainer.appendChild(link);
            }
        });

        if (modalAdjuntosInstance) modalAdjuntosInstance.show();
    }

    function abrirModalVerItems(items, codigo) {
        document.getElementById('modalVerItemsCodigo').innerText = codigo;
        const tbody = document.getElementById('modalVerItemsBody');
        tbody.innerHTML = '';
        
        if (!items || items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" class="p-4 text-center text-muted fst-italic">No hay ítems registrados.</td></tr>`;
        } else {
            items.forEach(item => {
                const cant = parseFloat(item.cantidad);
                const prec = parseFloat(item.precio_unitario);
                const tr = `
                    <tr class="align-middle">
                        <td class="text-secondary fw-semibold small">${escapeHTML(item.descripcion)}</td>
                        <td class="text-center fw-bold text-dark">${cant} <span class="text-muted d-block" style="font-size: 10px;">${escapeHTML(item.unidad_medida)}</span></td>
                        <td class="text-end text-muted price-text">${formatCurrency(prec)}</td>
                        <td class="text-end fw-bold text-dark price-text">${formatCurrency(cant * prec)}</td>
                    </tr>
                `;
                tbody.innerHTML += tr;
            });
        }
        
        if (modalVerItemsInstance) modalVerItemsInstance.show();
    }
    </script>

<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
