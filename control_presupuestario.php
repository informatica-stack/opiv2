<?php 
// control_presupuestario.php - Vista UI (Diseño SaaS Clean Minimalist)
require_once __DIR__ . '/control_presupuestario_controller.php'; 
$user_name = $_SESSION['user_name'] ?? 'Usuario';
$user_rol = $_SESSION['user_rol'] ?? '';
$user_depto = $_SESSION['user_depto_nombre'] ?? 'Control Presupuestario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php 
    $titulo_pagina = "Control Presupuestario - Sistema OPI";
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
            <a href="control_presupuestario.php" class="saas-nav-item active"><i class="bi bi-calculator"></i> Presupuesto</a>
            <a href="centros_de_costo.php" class="saas-nav-item"><i class="bi bi-wallet2"></i> Centros Costo</a>
            <?php if($user_rol === 'ADQUISICIONES' || $user_rol === 'SYSADMIN'): ?>
                <a href="adquisiciones.php" class="saas-nav-item"><i class="bi bi-cart3"></i> Adquisiciones</a>
            <?php endif; ?>
        </nav>

        <!-- Menú desplegable y Perfil -->
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
                    <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2 active bg-primary text-white" href="control_presupuestario.php"><i class="bi bi-calculator"></i> VB Presupuestario</a></li>
                    <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2" href="centros_de_costo.php"><i class="bi bi-wallet2"></i> Centros de Costo</a></li>
                    <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2" href="mantenedor_cuentas.php"><i class="bi bi-list-columns-reverse"></i> Cuentas Presupuestarias</a></li>
                    <li><a class="dropdown-item rounded-3 py-1.5 small d-flex align-items-center gap-2" href="finanzas.php"><i class="bi bi-file-earmark-check"></i> Firma de CDP</a></li>
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
        <!-- VISTA A: LISTADO DE REQUERIMIENTOS PRESUPUESTARIOS              -->
        <!-- ============================================================== -->
        <?php if($vista !== 'revisar'): ?>
            
            <!-- HEADER Y TÍTULO -->
            <div class="page-header-row">
                <div>
                    <div class="breadcrumbs">
                        <a href="index.php">Inicio</a>
                        <i class="bi bi-chevron-right"></i>
                        <span class="current">Control Presupuestario</span>
                    </div>
                    <div class="page-title">
                        <h2>Control y Visación Presupuestaria (CDP)</h2>
                        <p>Gestión de Certificados de Disponibilidad Presupuestaria y visación financiera de compras.</p>
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
                        <h5>Visación Inicial (Saldo)</h5>
                        <div class="metric-number"><?= number_format($count_pendientes_inicial, 0, ',', '.') ?></div>
                        <div class="metric-sub text-primary fw-bold">
                            <i class="bi bi-hourglass-split"></i> Reserva preliminar
                        </div>
                    </div>
                    <div class="metric-icon-box blue">
                        <i class="bi bi-wallet2"></i>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-info">
                        <h5>Visación Final (Firma 2/3)</h5>
                        <div class="metric-number"><?= number_format($count_pendientes_final, 0, ',', '.') ?></div>
                        <div class="metric-sub text-warning fw-bold">
                            <i class="bi bi-file-earmark-check"></i> Emisión CDP y Firma
                        </div>
                    </div>
                    <div class="metric-icon-box yellow">
                        <i class="bi bi-pen"></i>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-info">
                        <h5>Procesados por Mí</h5>
                        <div class="metric-number"><?= number_format($count_procesados, 0, ',', '.') ?></div>
                        <div class="metric-sub text-success fw-bold">
                            <i class="bi bi-check2-all"></i> Historial autorizado
                        </div>
                    </div>
                    <div class="metric-icon-box green">
                        <i class="bi bi-shield-check"></i>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-info">
                        <h5>Total Requerimientos</h5>
                        <div class="metric-number"><?= number_format($count_todas, 0, ',', '.') ?></div>
                        <div class="metric-sub text-muted">
                            <i class="bi bi-diagram-3"></i> Catálogo presupuestario
                        </div>
                    </div>
                    <div class="metric-icon-box purple">
                        <i class="bi bi-folder2-open"></i>
                    </div>
                </div>
            </div>

            <!-- PANEL PRINCIPAL CON TABS Y TABLA -->
            <div class="saas-panel">
                
                <!-- PESTAÑAS NAVEGABLES (4 TABS) -->
                <div class="saas-tab-nav">
                    <a href="control_presupuestario.php?view=pendientes_inicial" class="saas-tab-item <?= ($vista === 'pendientes_inicial' || $vista === 'pendientes') ? 'active' : '' ?>">
                        <i class="bi bi-hourglass-split"></i>
                        <span>Visación Inicial</span>
                        <span class="saas-tab-badge"><?= $count_pendientes_inicial ?></span>
                    </a>
                    <a href="control_presupuestario.php?view=pendientes_final" class="saas-tab-item <?= $vista === 'pendientes_final' ? 'active' : '' ?>">
                        <i class="bi bi-check-circle"></i>
                        <span>Visación Final / CDP (2/3)</span>
                        <span class="saas-tab-badge"><?= $count_pendientes_final ?></span>
                    </a>
                    <a href="control_presupuestario.php?view=procesados" class="saas-tab-item <?= $vista === 'procesados' ? 'active' : '' ?>">
                        <i class="bi bi-check2-square"></i>
                        <span>Procesados por Mí</span>
                        <span class="saas-tab-badge"><?= $count_procesados ?></span>
                    </a>
                    <a href="control_presupuestario.php?view=todas" class="saas-tab-item <?= $vista === 'todas' ? 'active' : '' ?>">
                        <i class="bi bi-diagram-3"></i>
                        <span>Todas las Solicitudes</span>
                        <span class="saas-tab-badge"><?= $count_todas ?></span>
                    </a>
                </div>

                <!-- TOOLBAR Y PANEL DE FILTROS -->
                <div id="filtroPanel" class="p-3 bg-light border-bottom <?= ($f_q || $f_tipo || $f_estado || $f_desde || $f_hasta) ? '' : 'd-none' ?>">
                    <form method="GET" action="control_presupuestario.php">
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
                                <a href="control_presupuestario.php?view=<?= htmlspecialchars($vista) ?>" class="btn-saas btn-saas-secondary w-100 justify-content-center py-1.5">Limpiar</a>
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
                                        if (in_array($st, ['EN_VALIDACION_PRESUPUESTARIA', 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 'ESPERANDO_CDP_FINANZAS', 'ESPERANDO_CDP_FINANZAS_FINAL'])) $badgeClass = 'saas-badge-warning';
                                        elseif (in_array($st, ['VB_PRESUPUESTO', 'VB_FINANZAS_CDP', 'FINALIZADO', 'ADJUDICADO'])) $badgeClass = 'saas-badge-success';
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
                                        <a href="control_presupuestario.php?view=revisar&id=<?= $row['id'] ?>" class="btn-saas <?= ($vista === 'pendientes' || $vista === 'pendientes_inicial' || $vista === 'pendientes_final') ? 'btn-saas-primary' : 'btn-saas-secondary' ?> btn-saas-sm">
                                            <i class="bi bi-search"></i>
                                            <span><?= ($vista === 'pendientes' || $vista === 'pendientes_inicial' || $vista === 'pendientes_final') ? 'Analizar' : 'Ver Detalle' ?></span>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="p-5 text-center text-muted">
                                        <div class="d-flex flex-column align-items-center justify-content-center py-4">
                                            <i class="bi bi-inbox fs-1 text-slate-300 mb-2"></i>
                                            <p class="mb-0 fw-semibold text-secondary">No se encontraron requerimientos registrados en esta bandeja.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINACIÓN SAAS -->
                <?php if (!empty($total_pages) && $total_pages > 1): ?>
                    <div class="panel-footer">
                        <div>
                            Mostrando página <b><?= $page ?? 1 ?></b> de <b><?= $total_pages ?></b> (Total: <b><?= $total_records ?? count($solicitudes ?? []) ?></b> solicitudes)
                        </div>
                        <ul class="saas-pagination">
                            <?php if(($page ?? 1) > 1): ?>
                                <li><a class="saas-page-link" href="<?= ($base_url ?? '?page=') . (($page ?? 1) - 1) ?>"><i class="bi bi-chevron-left"></i></a></li>
                            <?php endif; ?>
                            
                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <li><a class="saas-page-link <?= $i == ($page ?? 1) ? 'active' : '' ?>" href="<?= ($base_url ?? '?page=') . $i ?>"><?= $i ?></a></li>
                            <?php endfor; ?>
                            
                            <?php if(($page ?? 1) < $total_pages): ?>
                                <li><a class="saas-page-link" href="<?= ($base_url ?? '?page=') . (($page ?? 1) + 1) ?>"><i class="bi bi-chevron-right"></i></a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>

            </div>

        <?php endif; ?>

        <!-- ============================================================== -->
        <!-- VISTA B: REVISIÓN DE EXPEDIENTE PRESUPUESTARIO                 -->
        <!-- ============================================================== -->
        <?php if($vista === 'revisar' && isset($expediente)): ?>
            
            <div class="page-header-row mb-4">
                <div>
                    <div class="breadcrumbs">
                        <a href="index.php">Inicio</a>
                        <i class="bi bi-chevron-right"></i>
                        <a href="control_presupuestario.php">Control Presupuestario</a>
                        <i class="bi bi-chevron-right"></i>
                        <span class="current">Revisión #<?= htmlspecialchars($expediente['codigo_interno']) ?></span>
                    </div>
                    <div class="page-title">
                        <div class="d-flex align-items-center gap-2.5 flex-wrap">
                            <h2>Expediente: <span class="text-primary font-monospace">#<?= htmlspecialchars($expediente['codigo_interno']) ?></span></h2>
                            <span class="saas-badge saas-badge-info">
                                <?= $es_fase_inicial ? 'Fase: Emisión de Certificado de Disponibilidad (CDP)' : 'Fase: Visación Presupuestaria Final' ?>
                            </span>
                            <button type="button" onclick="verTrazabilidad(<?= (int)$expediente['id'] ?>)" class="btn-saas btn-saas-secondary btn-saas-sm">
                                <i class="bi bi-clock-history text-primary"></i> Ver Historial
                            </button>
                        </div>
                    </div>
                </div>
                <div>
                    <a href="control_presupuestario.php" class="btn-saas btn-saas-secondary">
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

                                <!-- Identificadores de Compra -->
                                <div class="d-flex flex-column gap-1.5 pt-2 border-top">
                                    <?php if(!empty($expediente['folio_opi'])): ?>
                                        <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded border">
                                            <span class="form-label-saas mb-0" style="font-size: 9.5px;">Folio OPI:</span> 
                                            <span class="font-monospace fw-bold text-dark"><?= htmlspecialchars($expediente['folio_opi']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($expediente['id_compra_agil'])): ?>
                                        <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded border">
                                            <span class="form-label-saas mb-0" style="font-size: 9.5px;">ID Compra Ágil:</span> 
                                            <span class="font-monospace fw-bold text-primary"><?= htmlspecialchars($expediente['id_compra_agil']) ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if(!empty($expediente['id_licitacion'])): ?>
                                        <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded border">
                                            <span class="form-label-saas mb-0" style="font-size: 9.5px;">ID Licitación:</span> 
                                            <span class="font-monospace fw-bold text-primary"><?= htmlspecialchars($expediente['id_licitacion']) ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if(!empty($expediente['id_contrato_suministro'])): ?>
                                        <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded border">
                                            <span class="form-label-saas mb-0" style="font-size: 9.5px;">ID Suministro:</span> 
                                            <span class="font-monospace fw-bold text-warning"><?= htmlspecialchars($expediente['id_contrato_suministro']) ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if(!empty($expediente['orden_compra_numero'])): ?>
                                        <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded border">
                                            <span class="form-label-saas mb-0" style="font-size: 9.5px;">N° Orden Compra:</span> 
                                            <span class="font-monospace fw-bold text-dark"><?= htmlspecialchars($expediente['orden_compra_numero']) ?></span>
                                        </div>
                                    <?php endif; ?>
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

                    <!-- ARCHIVOS ADJUNTOS -->
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
                                Imputación Contable y Presupuestaria
                            </h6>
                            <div class="text-end">
                                <span class="form-label-saas mb-0 text-end" style="font-size: 10px;">Monto Total</span>
                                <div class="h5 fw-bold text-primary mb-0 price-text"><?= money($expediente['monto_definitivo'] ?? $expediente['monto_estimado']) ?></div>
                            </div>
                        </div>
                        
                        <div class="saas-card-body p-3 bg-light">
                            <div class="d-flex flex-column gap-2.5">
                                <?php 
                                foreach($items as $it): 
                                    $costo_linea = $it['cantidad'] * $it['precio_unitario'];
                                ?>
                                    <div class="bg-white border rounded p-3 shadow-sm">
                                        <!-- Header del Item: Cuenta y Monto -->
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                            <div>
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <span class="badge bg-light text-dark border font-monospace fw-bold" style="font-size: 11.5px;"><?= $it['cuenta_codigo'] ?></span>
                                                    <?php if(!empty($it['cuenta_tipo']) && $it['cuenta_tipo'] === 'COMPLEMENTARIA'): ?>
                                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 9px;">Complementaria</span>
                                                    <?php endif; ?>
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

                                        <!-- Producto / Descripción -->
                                        <div class="bg-light p-2.5 rounded mb-2">
                                            <div class="d-flex justify-content-between align-items-center gap-3">
                                                <div class="text-dark small fw-semibold"><?= htmlspecialchars($it['descripcion']) ?></div>
                                                <div class="text-secondary small fw-bold text-nowrap text-end">
                                                    <?= floatval($it['cantidad']) ?> <span class="text-muted small fw-normal"><?= $it['unidad_medida'] ?></span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Disponible y Convenio Marco -->
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-2 border-top small">
                                            <div>
                                                <span class="text-muted" style="font-size: 11px;">Monto Unitario:</span>
                                                <span class="price-text fw-bold text-secondary ms-1" style="font-size: 12px;">
                                                    <?= money($it['precio_unitario']) ?>
                                                </span>
                                            </div>
                                            <?php if($expediente['tipo_compra_cod'] === 'CONVENIO_MARCO'): ?>
                                                <div class="text-end">
                                                    <span class="text-muted" style="font-size: 11px;">ID Convenio Marco:</span>
                                                    <span class="text-primary fw-bold font-monospace ms-1"><?= htmlspecialchars($it['id_producto_cm']) ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- CRITERIOS DE EVALUACIÓN PUBLICADOS -->
                    <?php if(!empty($criterios)): ?>
                    <div class="saas-card mb-4 border-info">
                        <div class="saas-card-header bg-info-subtle">
                            <h6 class="fw-bold mb-0 text-info-emphasis d-flex align-items-center gap-2" style="font-size: 13px;">
                                <i class="bi bi-calculator-fill text-info"></i>
                                Criterios de Evaluación Publicados
                            </h6>
                        </div>
                        <div class="saas-card-body p-0">
                            <div class="table-responsive">
                                <table class="saas-table mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-center" style="width: 60px;">N°</th>
                                            <th>Descripción del Criterio</th>
                                            <th class="text-center" style="width: 120px;">Ponderación</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($criterios as $cr): ?>
                                        <tr>
                                            <td class="text-center fw-bold text-secondary"><?= $cr['numero_criterio'] ?></td>
                                            <td class="fw-semibold text-dark small"><?= htmlspecialchars($cr['descripcion'] ?? $cr['nombre_criterio'] ?? '') ?></td>
                                            <td class="text-center fw-bold text-primary price-text"><?= floatval($cr['porcentaje']) ?>%</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ACCIONES DE RESOLUCIÓN FINANCIERA -->
                    <div class="saas-card">
                        <div class="saas-card-header">
                            <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2" style="font-size: 13.5px;">
                                <i class="bi bi-wallet2 text-primary"></i>
                                Resolución Financiera
                            </h6>
                        </div>
                        <div class="saas-card-body p-4">
                            
                            <?php if($es_accionable): ?>
                                <p class="text-secondary small mb-4">Verifique la disponibilidad y emita su visación para el expediente.</p>
                                
                                <?php 
                                $transiciones = obtener_transiciones_disponibles($pdo, $expediente['id']); 
                                ?>
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="expediente_id" value="<?= $expediente['id'] ?>">
                                    
                                    <?php if($expediente['estado_actual'] === 'EN_VALIDACION_PRESUPUESTARIA_FINAL'): ?>
                                        <!-- PANEL EMISIÓN Y GESTIÓN OBLIGATORIA DE ARCHIVOS DE RESPALDO -->
                                        <div class="saas-card border <?= $tiene_archivos_respaldo ? 'border-success-subtle bg-success-subtle/10' : 'border-warning-subtle' ?> mb-4">
                                            <div class="saas-card-header py-2.5">
                                                <span class="text-dark d-flex align-items-center gap-2 fw-bold" style="font-size: 13px;">
                                                    <i class="bi <?= $tiene_archivos_respaldo ? 'bi-check2-circle text-success' : 'bi-file-earmark-arrow-up text-warning' ?> fs-5"></i>
                                                    Documentos de Respaldo Presupuestario (Obligatorios)
                                                </span>
                                                <?php if ($tiene_archivos_respaldo): ?>
                                                    <span class="saas-badge saas-badge-success">
                                                        <i class="bi bi-check-lg"></i> Respaldo Completo
                                                    </span>
                                                <?php else: ?>
                                                    <span class="saas-badge saas-badge-warning">
                                                        <i class="bi bi-exclamation-triangle"></i> Pendiente de Carga
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="saas-card-body p-3 d-flex flex-column gap-3">
                                                
                                                <!-- 1. BORRADOR DE CDP -->
                                                <div class="p-3 bg-white border rounded">
                                                    <div class="d-flex justify-content-between align-items-center mb-1.5">
                                                        <label class="form-label-saas mb-0" style="font-size: 10px;">
                                                            1. Borrador de CDP (PDF) <span class="text-danger">*</span>
                                                        </label>
                                                        <?php if ($doc_cdp_borrador): ?>
                                                            <span class="saas-badge saas-badge-success py-0" style="font-size: 9.5px;">Cargado</span>
                                                        <?php else: ?>
                                                            <span class="saas-badge saas-badge-danger py-0" style="font-size: 9.5px;">Faltante</span>
                                                        <?php endif; ?>
                                                    </div>

                                                    <?php if ($doc_cdp_borrador): ?>
                                                        <div class="d-flex align-items-center justify-content-between p-2 bg-light rounded border">
                                                            <div class="d-flex align-items-center gap-2 min-w-0">
                                                                <i class="bi bi-file-earmark-pdf-fill text-danger fs-5"></i>
                                                                <span class="small text-truncate fw-semibold text-dark" style="max-width: 250px;"><?= htmlspecialchars($doc_cdp_borrador['nombre_original']) ?></span>
                                                            </div>
                                                            <a href="<?= htmlspecialchars($doc_cdp_borrador['ruta_archivo']) ?>" target="_blank" class="btn-saas btn-saas-secondary btn-saas-sm">
                                                                <i class="bi bi-box-arrow-up-right"></i> Ver
                                                            </a>
                                                        </div>
                                                    <?php else: ?>
                                                        <input type="file" name="archivo_cdp_borrador" id="archivo_cdp_borrador" accept="application/pdf" class="form-control form-control-sm bg-light">
                                                    <?php endif; ?>
                                                </div>

                                                <!-- 2. SITUACIÓN PRESUPUESTARIA DE GASTOS -->
                                                <div class="p-3 bg-white border rounded">
                                                    <div class="d-flex justify-content-between align-items-center mb-1.5">
                                                        <label class="form-label-saas mb-0" style="font-size: 10px;">
                                                            2. Situación Presupuestaria de Gastos (PDF) <span class="text-danger">*</span>
                                                        </label>
                                                        <?php if ($doc_situacion_gastos): ?>
                                                            <span class="saas-badge saas-badge-success py-0" style="font-size: 9.5px;">Cargado</span>
                                                        <?php else: ?>
                                                            <span class="saas-badge saas-badge-danger py-0" style="font-size: 9.5px;">Faltante</span>
                                                        <?php endif; ?>
                                                    </div>

                                                    <?php if ($doc_situacion_gastos): ?>
                                                        <div class="d-flex align-items-center justify-content-between p-2 bg-light rounded border">
                                                            <div class="d-flex align-items-center gap-2 min-w-0">
                                                                <i class="bi bi-file-earmark-pdf-fill text-danger fs-5"></i>
                                                                <span class="small text-truncate fw-semibold text-dark" style="max-width: 250px;"><?= htmlspecialchars($doc_situacion_gastos['nombre_original']) ?></span>
                                                            </div>
                                                            <a href="<?= htmlspecialchars($doc_situacion_gastos['ruta_archivo']) ?>" target="_blank" class="btn-saas btn-saas-secondary btn-saas-sm">
                                                                <i class="bi bi-box-arrow-up-right"></i> Ver
                                                            </a>
                                                        </div>
                                                    <?php else: ?>
                                                        <input type="file" name="archivo_situacion_gastos" id="archivo_situacion_gastos" accept="application/pdf" class="form-control form-control-sm bg-light">
                                                    <?php endif; ?>
                                                </div>

                                                <?php if (!$tiene_archivos_respaldo): ?>
                                                    <button type="submit" name="accion" value="guardar_documentos_respaldo" class="btn-saas btn-saas-primary w-100 justify-content-center py-2">
                                                        <i class="bi bi-cloud-arrow-up-fill"></i>
                                                        Subir y Guardar Archivos de Respaldo
                                                    </button>
                                                <?php endif; ?>

                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- BADGE CDP FIRMADO (SI EXISTE) -->
                                    <?php if($doc_cdp_firmado): ?>
                                        <div class="alert alert-success d-flex align-items-center justify-content-between gap-3 mb-4 shadow-sm py-2.5">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-patch-check-fill fs-5 text-success shrink-0"></i>
                                                <div>
                                                    <strong class="d-block text-dark small">CDP Firmado por Finanzas Adjunto</strong>
                                                    <span class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($doc_cdp_firmado['nombre_original']) ?></span>
                                                </div>
                                            </div>
                                            <a href="<?= htmlspecialchars($doc_cdp_firmado['ruta_archivo']) ?>" target="_blank" class="btn-saas btn-saas-secondary btn-saas-sm">
                                                <i class="bi bi-file-earmark-pdf text-danger"></i> Ver CDP Firmado
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <!-- BOTÓN DE APROBACIÓN PRINCIPAL / FIRMAGOB -->
                                    <?php 
                                    $t_aprobar = null;
                                    foreach ($transiciones as $t) {
                                        if ($t['accion_codigo'] === 'APROBAR') {
                                            $t_aprobar = $t;
                                            break;
                                        }
                                    }
                                    if ($expediente['estado_actual'] === 'EN_VALIDACION_PRESUPUESTARIA_FINAL'):
                                    ?>
                                        <?php if ($tiene_archivos_respaldo): ?>
                                            <button type="button" onclick="abrirModalFirmaGob({expediente_id: <?= $expediente['id'] ?>, transicion_id: <?= $t_aprobar ? $t_aprobar['id'] : 'null' ?>, etapa: 'PRESUPUESTO', codigo_interno: '<?= htmlspecialchars($expediente['codigo_interno']) ?>', monto: '<?= $expediente['monto_definitivo'] ?: $expediente['monto_estimado'] ?>', doc_titulo: 'OPI - V°B° Presupuestario (2/3)', titulo_compra: '<?= htmlspecialchars(addslashes($expediente['titulo_compra'] ?? '')) ?>', proveedor: '<?= htmlspecialchars(addslashes(($expediente['proveedor_rut'] ? $expediente['proveedor_rut'].' - ' : '').($expediente['proveedor_nombre'] ?? ''))) ?>', firmante_nombre: '<?= htmlspecialchars(addslashes($firmante_activo['nombre'] ?? $_SESSION['user_nombre'] ?? '')) ?>', firmante_rut: '<?= htmlspecialchars(addslashes($firmante_activo['rut'] ?? $_SESSION['user_rut'] ?? '')) ?>', firmante_cargo: '<?= htmlspecialchars(addslashes($firmante_activo['cargo'] ?? 'CONTROL PRESUPUESTARIO')) ?>'})" class="btn-saas btn-saas-primary py-3 w-100 mb-4 justify-content-center fw-bold fs-6 shadow" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
                                                <i class="bi bi-pen-fill fs-5"></i>
                                                Firmar OPI con FirmaGob (2/3)
                                            </button>
                                        <?php else: ?>
                                            <div class="alert alert-warning py-2.5 px-3 small d-flex align-items-center gap-2 mb-3">
                                                <i class="bi bi-shield-lock-fill fs-4 text-warning shrink-0"></i>
                                                <div><strong>Firma Digital 2/3 Bloqueada:</strong> Debe adjuntar y guardar el Borrador de CDP y la Situación Presupuestaria de Gastos para habilitar la firma de la OPI.</div>
                                            </div>
                                            <button type="button" disabled class="btn-saas btn-saas-secondary py-2.5 w-100 mb-4 justify-content-center opacity-50 fw-bold" style="cursor: not-allowed;">
                                                <i class="bi bi-lock-fill"></i>
                                                Firmar OPI con FirmaGob (2/3) - Bloqueado
                                            </button>
                                        <?php endif; ?>

                                    <?php elseif ($t_aprobar): ?>
                                        <button type="submit" name="transicion_id" value="<?= $t_aprobar['id'] ?>" onclick="return confirm('¿Confirma la acción de: <?= htmlspecialchars($t_aprobar['accion_label']) ?>?')" class="btn-saas btn-saas-primary py-2.5 w-100 mb-4 justify-content-center fw-bold shadow-sm" style="font-size: 14px;">
                                            <i class="bi bi-check-circle-fill"></i>
                                            <?= htmlspecialchars($t_aprobar['accion_label']) ?>
                                        </button>
                                    <?php endif; ?>

                                    <!-- REPAROS Y OBSERVACIONES -->
                                    <div class="border-top pt-3">
                                        <label class="form-label-saas mb-1">Reparos y Observaciones (Obligatorio para Devolver o Rechazar)</label>
                                        <textarea name="motivo_rechazo" rows="3" placeholder="Indique las observaciones o motivos financieros..." class="form-control-saas mb-3 bg-light"></textarea>
                                        
                                        <div class="row g-2">
                                            <!-- Botones de Devolución -->
                                            <?php foreach ($transiciones as $t): 
                                                if ($t['accion_codigo'] === 'DEVOLVER'):
                                            ?>
                                                <div class="col-sm-6">
                                                    <button type="submit" name="transicion_id" value="<?= $t['id'] ?>" onclick="return confirm('¿Confirma la acción de: <?= htmlspecialchars($t['accion_label']) ?>?')" class="btn-saas btn-saas-secondary w-100 justify-content-center py-2">
                                                        <i class="bi bi-arrow-counterclockwise text-warning"></i>
                                                        <?= htmlspecialchars($t['accion_label']) ?>
                                                    </button>
                                                </div>
                                            <?php 
                                                endif;
                                            endforeach; ?>

                                            <!-- Botones de Rechazo -->
                                            <?php foreach ($transiciones as $t): 
                                                if ($t['accion_codigo'] === 'RECHAZAR'):
                                            ?>
                                                <div class="col-sm-6">
                                                    <button type="submit" name="transicion_id" value="<?= $t['id'] ?>" onclick="return confirm('¿Confirma la acción de: <?= htmlspecialchars($t['accion_label']) ?>?')" class="btn-saas btn-saas-danger w-100 justify-content-center py-2">
                                                        <i class="bi bi-x-circle text-danger"></i>
                                                        <?= htmlspecialchars($t['accion_label']) ?>
                                                    </button>
                                                </div>
                                            <?php 
                                                endif;
                                            endforeach; ?>
                                        </div>
                                    </div>
                                </form>
                            
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <div class="p-3 bg-success-subtle text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 54px; height: 54px;">
                                        <i class="bi bi-check-lg fs-2"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark mb-1">Visación Completada</h5>
                                    <p class="text-secondary small mb-4">El expediente ha sido procesado por el departamento en esta fase.</p>
                                    
                                    <div class="bg-light rounded p-3 border d-inline-block text-start mx-auto" style="min-width: 280px;">
                                        <span class="form-label-saas mb-1" style="font-size: 9.5px;">Estado Actual del Expediente</span>
                                        <span class="fw-bold text-dark fs-6 d-block"><?= htmlspecialchars($expediente['estado_nombre']) ?></span>
                                        <span class="saas-badge saas-badge-info mt-2">
                                            Responsable: <?= htmlspecialchars($expediente['rol_responsable']) ?>
                                        </span>
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

    function validarSolicitudCDP(event) {
        const borrador = document.getElementById('archivo_cdp_borrador');
        const situacion = document.getElementById('archivo_situacion_gastos');
        
        if (!borrador || !borrador.files.length) {
            alert('Debe adjuntar obligatoriamente el Borrador de CDP (Paso 1).');
            event.preventDefault();
            return false;
        }
        if (!situacion || !situacion.files.length) {
            alert('Debe adjuntar obligatoriamente el documento de Situación Presupuestaria de Gastos (Paso 2).');
            event.preventDefault();
            return false;
        }
        
        return confirm('¿Confirma enviar el CDP a Finanzas para firma?');
    }
    </script>

<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>