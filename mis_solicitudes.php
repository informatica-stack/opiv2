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
    <?php 
    $titulo_pagina = "Mis Solicitudes - Sistema OPI";
    include __DIR__ . '/head.php'; 
    ?>
</head>
<body>

    <?php include __DIR__ . '/nav.php'; ?>

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

        <!-- TARJETAS DE MÉTRICAS KPI (DINÁMICAS DEL USUARIO - INTERACTIVAS) -->
        <div class="metrics-grid">
            <a href="mis_solicitudes.php" class="metric-card <?= (empty($f_estado) && empty($f_q) && empty($f_tipo) && empty($f_desde) && empty($f_hasta)) ? 'active' : '' ?>">
                <div class="metric-info">
                    <h5>Total Solicitudes</h5>
                    <div class="metric-number"><?= $kpi_total_user ?></div>
                    <div class="metric-sub"><i class="bi bi-folder2-open text-primary"></i> En su historial personal</div>
                </div>
                <div class="metric-icon-box blue"><i class="bi bi-file-earmark-text"></i></div>
            </a>

            <a href="mis_solicitudes.php?f_estado=EN_REVISION_JEFATURA" class="metric-card <?= $f_estado === 'EN_REVISION_JEFATURA' ? 'active' : '' ?>">
                <div class="metric-info">
                    <h5>Pendiente Jefatura</h5>
                    <div class="metric-number"><?= $kpi_pend_user ?></div>
                    <div class="metric-sub" style="color: <?= $kpi_pend_user > 0 ? 'var(--warning)' : 'var(--text-muted)' ?>;">
                        <i class="bi bi-clock-history"></i> <?= $kpi_pend_user > 0 ? 'En revisión / firma' : 'Al día' ?>
                    </div>
                </div>
                <div class="metric-icon-box yellow"><i class="bi bi-pen"></i></div>
            </a>

            <a href="mis_solicitudes.php?f_estado=VB_PRESUPUESTO" class="metric-card <?= $f_estado === 'VB_PRESUPUESTO' ? 'active' : '' ?>">
                <div class="metric-info">
                    <h5>Validación Presupuesto</h5>
                    <div class="metric-number"><?= $kpi_pres_count ?></div>
                    <div class="metric-sub"><i class="bi bi-cash-stack text-info"></i> <?= money($kpi_pres_monto) ?> comprometidos</div>
                </div>
                <div class="metric-icon-box cyan"><i class="bi bi-calculator"></i></div>
            </a>

            <a href="mis_solicitudes.php?f_estado=EN_GESTION_ADQUISICIONES" class="metric-card <?= $f_estado === 'EN_GESTION_ADQUISICIONES' ? 'active' : '' ?>">
                <div class="metric-info">
                    <h5>En Adquisiciones / Listas</h5>
                    <div class="metric-number"><?= $kpi_adq_user ?></div>
                    <div class="metric-sub" style="color: var(--success);"><i class="bi bi-check2-circle"></i> Trámite avanzado</div>
                </div>
                <div class="metric-icon-box green"><i class="bi bi-bag-check"></i></div>
            </a>
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
                    Mostrando página <strong><?= $page ?? 1 ?></strong> de <strong><?= max(1, $total_pages ?? 1) ?></strong> (Total: <?= $total_records ?? count($solicitudes ?? []) ?> solicitudes)
                </div>

                <?php if (!empty($total_pages) && $total_pages > 1): ?>
                <nav>
                    <ul class="saas-pagination">
                        <?php if (($page ?? 1) > 1): ?>
                            <li><a class="saas-page-link" href="<?= ($base_url ?? '?page=') . (($page ?? 1) - 1) ?>" title="Anterior">&laquo;</a></li>
                        <?php endif; ?>

                        <?php for ($i = max(1, ($page ?? 1) - 2); $i <= min($total_pages, ($page ?? 1) + 2); $i++): ?>
                            <li><a class="saas-page-link <?= $i == ($page ?? 1) ? 'active' : '' ?>" href="<?= ($base_url ?? '?page=') . $i ?>"><?= $i ?></a></li>
                        <?php endfor; ?>

                        <?php if (($page ?? 1) < $total_pages): ?>
                            <li><a class="saas-page-link" href="<?= ($base_url ?? '?page=') . (($page ?? 1) + 1) ?>" title="Siguiente">&raquo;</a></li>
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