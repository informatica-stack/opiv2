<?php
// jefatura.php - Visación Técnica y Firma de Jefatura (Diseño SaaS Clean Minimalist)
require_once __DIR__ . '/jefatura_controller.php';
$user_name = $_SESSION['user_name'] ?? 'Usuario';
$user_rol = $_SESSION['user_rol'] ?? '';
$user_depto = $_SESSION['user_depto_nombre'] ?? 'Municipalidad';
$es_subrogante = isset($_SESSION['es_subrogante']) && $_SESSION['es_subrogante'];
?><!DOCTYPE html>
<html lang="es">
<head>
    <?php 
    $titulo_pagina = "Bandeja de Entrada Jefatura - Sistema OPI";
    include __DIR__ . '/head.php'; 
    ?>
</head>
<body>

    <?php include __DIR__ . '/nav.php'; ?>

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
        <!-- VISTA A: LISTA DE SOLICITUDES (TABS, FILTROS Y TABLA)           -->
        <!-- ============================================================== -->
        <?php if($vista !== 'revisar'): ?>
            
            <!-- HEADER Y TÍTULO -->
            <div class="page-header-row">
                <div>
                    <div class="breadcrumbs">
                        <a href="index.php">Inicio</a>
                        <i class="bi bi-chevron-right"></i>
                        <span class="current">Bandeja de Jefatura</span>
                    </div>
                    <div class="page-title">
                        <h2>
                            Bandeja de Visación y Firma de Jefatura
                            <?php if($es_subrogante): ?>
                                <span class="badge bg-warning text-dark ms-2 fw-bold" style="font-size: 11px;">SUBROGANTE</span>
                            <?php endif; ?>
                        </h2>
                        <p>Revise, controle y autorice oportunamente los requerimientos de compra de su unidad.</p>
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
                        <h5>Pendientes de Visación / Firma</h5>
                        <div class="metric-number"><?= number_format($count_pendientes, 0, ',', '.') ?></div>
                        <div class="metric-sub text-primary fw-bold">
                            <i class="bi bi-clock-history"></i> En espera de su revisión
                        </div>
                    </div>
                    <div class="metric-icon-box blue">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-info">
                        <h5>Procesadas por Mí</h5>
                        <div class="metric-number"><?= number_format($count_procesadas, 0, ',', '.') ?></div>
                        <div class="metric-sub text-success fw-bold">
                            <i class="bi bi-check2-all"></i> Historial de trámites visados
                        </div>
                    </div>
                    <div class="metric-icon-box green">
                        <i class="bi bi-shield-check"></i>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-info">
                        <h5>Total Requerimientos Unidad</h5>
                        <div class="metric-number"><?= number_format($count_todas, 0, ',', '.') ?></div>
                        <div class="metric-sub text-muted">
                            <i class="bi bi-diagram-3"></i> Catálogo completo de la unidad
                        </div>
                    </div>
                    <div class="metric-icon-box purple">
                        <i class="bi bi-folder2-open"></i>
                    </div>
                </div>
            </div>

            <!-- PANEL PRINCIPAL CON TABS Y TABLA -->
            <div class="saas-panel">
                
                <!-- PESTAÑAS NAVEGABLES (TABS) -->
                <div class="saas-tab-nav">
                    <a href="jefatura.php?view=pendientes" class="saas-tab-item <?= $vista === 'pendientes' ? 'active' : '' ?>">
                        <i class="bi bi-clock-history"></i>
                        <span>Pendientes de Visación</span>
                        <span class="saas-tab-badge"><?= $count_pendientes ?></span>
                    </a>
                    <a href="jefatura.php?view=procesadas" class="saas-tab-item <?= $vista === 'procesadas' ? 'active' : '' ?>">
                        <i class="bi bi-check2-square"></i>
                        <span>Procesadas por Mí</span>
                        <span class="saas-tab-badge"><?= $count_procesadas ?></span>
                    </a>
                    <a href="jefatura.php?view=todas" class="saas-tab-item <?= $vista === 'todas' ? 'active' : '' ?>">
                        <i class="bi bi-diagram-3"></i>
                        <span>Todas de la Unidad</span>
                        <span class="saas-tab-badge"><?= $count_todas ?></span>
                    </a>
                </div>

                <!-- TOOLBAR Y PANEL DE FILTROS -->
                <div id="filtroPanel" class="p-3 bg-light border-bottom <?= ($f_q || $f_tipo || $f_estado || $f_desde || $f_hasta) ? '' : 'd-none' ?>">
                    <form method="GET" action="jefatura.php">
                        <input type="hidden" name="view" value="<?= htmlspecialchars($vista) ?>">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-3">
                                <label class="form-label-saas mb-1" style="font-size: 11px;">Buscar (Código / Título / Solicitante)</label>
                                <input type="text" name="f_q" value="<?= htmlspecialchars($f_q) ?>" placeholder="Ej: EXP-2026 o Insumos..." class="form-control-saas py-1.5">
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
                                <a href="jefatura.php?view=<?= htmlspecialchars($vista) ?>" class="btn-saas btn-saas-secondary w-100 justify-content-center py-1.5">Limpiar</a>
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
                                <th style="min-width: 280px;">Título / Destino de los Bienes</th>
                                <th style="width: 160px;">Clasificación</th>
                                <th style="width: 180px;">Estado Trámite</th>
                                <th style="width: 140px;" class="text-end">Monto Total</th>
                                <th style="width: 140px;" class="text-center">Acción</th>
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
                                            <span class="d-flex align-items-center gap-1"><i class="bi bi-person-fill text-primary"></i> <?= htmlspecialchars($row['solicitante']) ?></span>
                                            <span>•</span>
                                            <span class="d-flex align-items-center gap-1"><i class="bi bi-tag-fill text-secondary"></i> CC: <?= htmlspecialchars($row['cc_nombre']) ?></span>
                                            <?php if (!empty($row['count_cc_externos'])): ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 10px;" title="Involucra fondos de otros Centros de Costos">
                                                    <i class="bi bi-arrow-left-right me-1"></i>Multi-CC
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if($row['docs_adjuntos']): 
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

                                    <!-- Clasificación / Tipo de Compra -->
                                    <td>
                                        <span class="saas-badge saas-badge-neutral">
                                            <?= htmlspecialchars($row['tipo_compra_nom']) ?>
                                        </span>
                                    </td>

                                    <!-- Estado Actual y Trazabilidad -->
                                    <td>
                                        <?php 
                                        $st = $row['estado_actual'];
                                        $badgeClass = 'saas-badge-neutral';
                                        if (in_array($st, ['EN_REVISION_JEFATURA', 'EN_REVISION_PRESUPUESTO'])) $badgeClass = 'saas-badge-warning';
                                        elseif (in_array($st, ['VB_PRESUPUESTO', 'VB_FINANZAS_CDP', 'FINALIZADO', 'ADJUDICADO'])) $badgeClass = 'saas-badge-success';
                                        elseif (in_array($st, ['EN_FIRMA_JEFATURA', 'EN_FIRMA_ALCALDE', 'EN_GESTION_ADQUISICIONES'])) $badgeClass = 'saas-badge-info';
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

                                    <!-- Monto Total con Números Estándar -->
                                    <td class="text-end">
                                        <div class="price-text" style="font-size: 14px;">
                                            <?= money($row['monto_estimado']) ?>
                                        </div>
                                    </td>

                                    <!-- Botón de Acción -->
                                    <td class="text-center">
                                        <?php if ($row['estado_actual'] === 'EN_FIRMA_JEFATURA'): ?>
                                            <a href="jefatura.php?view=revisar&id=<?= $row['id'] ?>" class="btn-saas btn-saas-primary btn-saas-sm">
                                                <i class="bi bi-pen-fill"></i>
                                                <span>Firmar (1/3)</span>
                                            </a>
                                        <?php else: ?>
                                            <a href="jefatura.php?view=revisar&id=<?= $row['id'] ?>" class="btn-saas btn-saas-secondary btn-saas-sm">
                                                <i class="bi bi-search text-primary"></i>
                                                <span>Revisar</span>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="p-5 text-center text-muted">
                                        <div class="d-flex flex-column align-items-center justify-content-center py-4">
                                            <i class="bi bi-inbox fs-1 text-slate-300 mb-2"></i>
                                            <p class="mb-0 fw-semibold text-secondary">No se encontraron requerimientos en esta vista.</p>
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
        <!-- VISTA B: REVISIÓN DETALLADA DE EXPEDIENTE                       -->
        <!-- ============================================================== -->
        <?php if($vista === 'revisar' && isset($exp)): ?>
            
            <div class="page-header-row mb-4">
                <div>
                    <div class="breadcrumbs">
                        <a href="index.php">Inicio</a>
                        <i class="bi bi-chevron-right"></i>
                        <a href="jefatura.php">Bandeja Jefatura</a>
                        <i class="bi bi-chevron-right"></i>
                        <span class="current">Revisión #<?= htmlspecialchars($exp['codigo_interno']) ?></span>
                    </div>
                    <div class="page-title">
                        <div class="d-flex align-items-center gap-2.5 flex-wrap">
                            <h2>Expediente: <span class="text-primary font-monospace">#<?= htmlspecialchars($exp['codigo_interno']) ?></span></h2>
                            <button type="button" onclick="verTrazabilidad(<?= (int)$exp['id'] ?>)" class="btn-saas btn-saas-secondary btn-saas-sm">
                                <i class="bi bi-clock-history text-primary"></i> Ver Historial
                            </button>
                        </div>
                    </div>
                </div>
                <div>
                    <a href="jefatura.php" class="btn-saas btn-saas-secondary">
                        <i class="bi bi-arrow-left"></i> Volver a la Bandeja
                    </a>
                </div>
            </div>

            <!-- BANNER ESPECIAL DE ETAPA EN FIRMA -->
            <?php if ($exp['estado_actual'] === 'EN_FIRMA_JEFATURA'): ?>
                <div class="alert alert-primary border-primary-subtle d-flex align-items-center justify-content-between p-3 rounded-3 shadow-sm mb-4" style="background-color: #eef2ff; border-color: #c7d2fe;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-2 text-white rounded-circle d-flex align-items-center justify-content-center shrink-0" style="width: 44px; height: 44px; background-color: #4f46e5;">
                            <i class="bi bi-pen-fill fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0" style="color: #3730a3;">Expediente Adjudicado listo para Firma Digital de Jefatura (1/3)</h6>
                            <p class="small text-secondary mb-0">Adquisiciones ha completado el cuadro comparativo y adjudicado la compra. Corresponde estampar la primera firma electrónica en la Orden de Pedido Interno (OPI) oficial.</p>
                        </div>
                    </div>
                    <a href="imprimir_solicitud.php?id=<?= $exp['id'] ?>" target="_blank" class="btn-saas btn-saas-secondary shadow-sm text-nowrap">
                        <i class="bi bi-file-earmark-pdf-fill text-danger"></i>
                        Ver OPI Oficial a Firmar
                    </a>
                </div>
            <?php endif; ?>

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
                                <?php if($exp['titulo_compra']): ?>
                                    <div>
                                        <span class="form-label-saas mb-1">Título de la Compra:</span>
                                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($exp['titulo_compra']) ?></div>
                                    </div>
                                <?php endif; ?>
                                
                                <div>
                                    <span class="form-label-saas mb-1">Solicitante:</span>
                                    <div class="text-secondary fw-semibold"><?= htmlspecialchars($exp['solicitante']) ?></div>
                                </div>
                                
                                <div>
                                    <span class="form-label-saas mb-1">Unidad Origen:</span>
                                    <div class="text-secondary fw-semibold"><?= htmlspecialchars($exp['unidad']) ?></div>
                                </div>
                                
                                <div>
                                    <span class="form-label-saas mb-1">Centro de Costo:</span>
                                    <div class="text-secondary fw-semibold"><?= htmlspecialchars($exp['centro_costo']) ?></div>
                                </div>
                                
                                <div>
                                    <span class="form-label-saas mb-1">Tipo de Compra:</span>
                                    <span class="saas-badge saas-badge-neutral"><?= htmlspecialchars($exp['tipo_compra_nom']) ?></span>
                                </div>
                                
                                <div>
                                    <span class="form-label-saas mb-1">Los presentes bienes serán destinados a:</span>
                                    <p class="text-secondary mb-0 p-2.5 rounded border bg-light" style="font-size: 12px; line-height: 1.5;">
                                        <?= nl2br(htmlspecialchars($exp['motivo_compra'])) ?>
                                    </p>
                                </div>

                                <?php if($exp['proveedor_nombre']): ?>
                                    <div>
                                        <span class="form-label-saas mb-1">Proveedor Sugerido / Adjudicado:</span>
                                        <div class="fw-bold text-primary"><?= htmlspecialchars($exp['proveedor_rut']) ?> - <?= htmlspecialchars($exp['proveedor_nombre']) ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- AUTORIZACIONES INTER-CC (V°B° PARALELO) -->
                    <?php if (!empty($autorizaciones_cc)): ?>
                    <div class="saas-card mb-4 border-primary-subtle">
                        <div class="saas-card-header bg-primary-subtle">
                            <h6 class="fw-bold mb-0 text-primary-emphasis d-flex align-items-center gap-2" style="font-size: 13px;">
                                <i class="bi bi-shield-check text-primary"></i>
                                Visación y Autorización de Fondos (Paralelo)
                            </h6>
                        </div>
                        <div class="saas-card-body p-3">
                            <p class="text-muted small mb-2.5" style="font-size: 11px;">
                                Estado de autorizaciones requeridas para los Centros de Costos involucrados:
                            </p>
                            <div class="d-flex flex-column gap-2.5">
                                <?php foreach($autorizaciones_cc as $aut): 
                                    $es_orig = ($aut['tipo_autorizacion'] === 'UNIDAD_ORIGEN');
                                    $stAut = $aut['estado'];
                                    $badgeSt = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                                    $iconSt = 'bi-hourglass-split';
                                    if ($stAut === 'APROBADO') {
                                        $badgeSt = 'bg-success-subtle text-success-emphasis border border-success-subtle';
                                        $iconSt = 'bi-check-circle-fill';
                                    } elseif ($stAut === 'DEVUELTO') {
                                        $badgeSt = 'bg-danger-subtle text-danger-emphasis border border-danger-subtle';
                                        $iconSt = 'bi-arrow-counterclockwise';
                                    } elseif ($stAut === 'RECHAZADO') {
                                        $badgeSt = 'bg-danger-subtle text-danger-emphasis border border-danger-subtle';
                                        $iconSt = 'bi-x-circle-fill';
                                    }
                                    $es_mi_aut = ($aut['unidad_responsable_id'] == $unidad_id);
                                ?>
                                    <div class="p-2.5 rounded border bg-white shadow-sm <?= $es_mi_aut ? 'border-primary' : '' ?>">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1.5">
                                            <div>
                                                <span class="badge <?= $es_orig ? 'bg-primary-subtle text-primary' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' ?> mb-1" style="font-size: 9.5px;">
                                                    <?= $es_orig ? 'JEFATURA REQUIRENTE' : 'CEDENTE DE FONDOS (CC EXTERNO)' ?>
                                                </span>
                                                <div class="fw-bold text-dark small">
                                                    <?= htmlspecialchars($aut['cc_nombre']) ?> 
                                                </div>
                                                <div class="text-muted small" style="font-size: 10.5px;">
                                                    Unidad: <?= htmlspecialchars($aut['unidad_nombre']) ?>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge <?= $badgeSt ?>" style="font-size: 10.5px;">
                                                    <i class="bi <?= $iconSt ?> me-1"></i><?= $stAut ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center pt-1.5 border-top small text-muted" style="font-size: 11px;">
                                            <div>
                                                <span>Imputado: </span><strong class="text-dark"><?= money($aut['monto_imputado']) ?></strong>
                                            </div>
                                            <?php if($aut['visador_nombre']): ?>
                                                <div class="text-truncate" style="max-width: 140px;" title="<?= htmlspecialchars($aut['visador_nombre']) ?>">
                                                    <i class="bi bi-person-check text-success"></i> <?= htmlspecialchars($aut['visador_nombre']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if($aut['comentario']): ?>
                                            <div class="mt-1.5 p-1.5 bg-light rounded text-muted" style="font-size: 11px;">
                                                <em>"<?= htmlspecialchars($aut['comentario']) ?>"</em>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- DOCUMENTOS DE RESPALDO -->
                    <div class="saas-card">
                        <div class="saas-card-header">
                            <h6 class="fw-bold mb-0 text-dark" style="font-size: 13px;">
                                <i class="bi bi-paperclip text-primary me-1.5"></i>
                                Documentos de Respaldo
                            </h6>
                        </div>
                        <div class="saas-card-body p-3">
                            <?php if(empty($docs)): ?>
                                <p class="text-muted small mb-0 fst-italic">No hay archivos adjuntos a este requerimiento.</p>
                            <?php else: ?>
                                <div class="d-flex flex-column gap-2">
                                    <?php foreach($docs as $doc): 
                                        $is_anulada = ($doc['tipo_doc'] === 'OPI_ANULADA');
                                    ?>
                                        <a href="<?= htmlspecialchars($doc['ruta_archivo']) ?>" target="_blank" class="d-flex align-items-center justify-content-between p-2.5 bg-light border rounded text-decoration-none transition">
                                            <div class="d-flex align-items-center gap-2 min-w-0 flex-1">
                                                <i class="bi bi-file-earmark-text text-primary fs-5 shrink-0"></i>
                                                <div class="text-truncate flex-1">
                                                    <p class="mb-0 text-truncate small <?= $is_anulada ? 'text-danger text-decoration-line-through' : 'text-dark fw-bold' ?>" style="max-width: 220px;"><?= htmlspecialchars($doc['nombre_original']) ?></p>
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

                <!-- COLUMNA DERECHA: TABLA DE PRODUCTOS E ITEMS -->
                <div class="col-lg-8">
                    
                    <!-- DETALLE PRODUCTOS -->
                    <div class="saas-card mb-4">
                        <div class="saas-card-header">
                            <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2" style="font-size: 13.5px;">
                                <i class="bi bi-cart3 text-primary"></i>
                                Detalle de Productos / Servicios
                            </h6>
                            <div class="text-end">
                                <span class="form-label-saas mb-0 text-end" style="font-size: 10px;">Monto Total</span>
                                <div class="h5 fw-bold text-primary mb-0 price-text"><?= money($exp['monto_definitivo'] ?? $exp['monto_estimado']) ?></div>
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
                                                    <?php if(!empty($it['ag_codigo'])): ?>
                                                        <span class="badge bg-secondary-subtle text-secondary-emphasis" style="font-size: 9px;">AG: <?= $it['ag_codigo'] ?></span>
                                                    <?php endif; ?>
                                                    <?php if(!empty($it['cc_nombre']) && isset($it['es_propia']) && $it['es_propia'] == 0): ?>
                                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 9.5px;"><i class="bi bi-arrow-left-right me-1"></i>CC Ext: <?= htmlspecialchars($it['cc_nombre']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <span class="text-muted d-block text-uppercase fw-bold" style="font-size: 9px;">Total Línea</span>
                                                <span class="fw-bold price-text text-dark" style="font-size: 14px;">
                                                    <?= money($costo_linea) ?>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="bg-light p-2.5 rounded mb-2">
                                            <div class="d-flex justify-content-between align-items-center gap-3">
                                                <div class="text-dark small fw-semibold"><?= htmlspecialchars($it['descripcion']) ?></div>
                                                <div class="text-secondary small fw-bold text-nowrap text-end">
                                                    <?= floatval($it['cantidad']) ?> <span class="text-muted small fw-normal"><?= $it['unidad_medida'] ?></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-2 border-top small">
                                            <div>
                                                <span class="text-muted" style="font-size: 11px;">Monto Unitario:</span>
                                                <span class="price-text fw-bold text-secondary ms-1" style="font-size: 12px;">
                                                    <?= money($it['precio_unitario']) ?>
                                                </span>
                                            </div>
                                            <?php if($exp['tipo_compra_cod'] === 'CONVENIO_MARCO'): ?>
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

                    <!-- CRITERIOS DE EVALUACIÓN (SI APLICAN) -->
                    <?php if(!empty($criterios)): ?>
                    <div class="saas-card mb-4 border-info">
                        <div class="saas-card-header bg-info-subtle">
                            <h6 class="fw-bold mb-0 text-info-emphasis d-flex align-items-center gap-2" style="font-size: 13px;">
                                <i class="bi bi-calculator-fill text-info"></i>
                                Criterios de Evaluación Propuestos
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
                                            <td class="fw-semibold text-dark small"><?= htmlspecialchars($cr['nombre_criterio']) ?></td>
                                            <td class="text-center fw-bold text-primary price-text"><?= floatval($cr['porcentaje']) ?>%</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- TARJETA DE RESOLUCIÓN -->
                    <div class="saas-card">
                        <div class="saas-card-header">
                            <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2" style="font-size: 13.5px;">
                                <i class="bi bi-shield-check text-primary"></i>
                                Resolución de Jefatura
                            </h6>
                        </div>
                        <div class="saas-card-body p-4">
                            <?php if (!empty($es_accionable)): ?>
                                <p class="text-secondary small mb-4">Seleccione una de las acciones autorizadas por el flujo para proceder con el requerimiento.</p>
                                
                                <?php 
                                $transiciones = obtener_transiciones_disponibles($pdo, $exp['id']); 
                                ?>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="expediente_id" value="<?= $exp['id'] ?>">
                                    
                                    <!-- ACCIONES DE APROBACIÓN O FIRMA DIGITAL -->
                                    <?php 
                                    $t_aprobar = null;
                                    foreach ($transiciones as $t) {
                                         if ($t['accion_codigo'] === 'APROBAR' || $t['accion_codigo'] === 'FIRMAR_JEFATURA') {
                                             $t_aprobar = $t;
                                             break;
                                         }
                                     }
                                     if ($exp['estado_actual'] === 'EN_FIRMA_JEFATURA'):
                                         $monto_calc = $exp['monto_definitivo'] ?: $exp['monto_estimado'];
                                         $firmante_nom = $firmante_activo['nombre_completo'] ?? $firmante_activo['nombre'] ?? $_SESSION['user_nombre'] ?? 'Jefe de Unidad';
                                         $firmante_rut = $firmante_activo['rut'] ?? $_SESSION['user_rut'] ?? '';
                                         $firmante_cargo = $firmante_activo['cargo'] ?? $_SESSION['user_cargo'] ?? 'Jefe de Unidad';
                                         $firmante_unidad = $exp['unidad'] ?? $_SESSION['user_unidad_nombre'] ?? '';
                                         $prov_str = ($exp['proveedor_rut'] ? $exp['proveedor_rut'].' - ' : '').($exp['proveedor_nombre'] ?? '');
                                     ?>
                                         <button type="button" onclick="abrirModalFirmaGob({
                                             expediente_id: <?= $exp['id'] ?>, 
                                             transicion_id: <?= $t_aprobar ? $t_aprobar['id'] : 'null' ?>, 
                                             etapa: 'JEFATURA', 
                                             codigo_interno: '<?= htmlspecialchars($exp['codigo_interno']) ?>', 
                                             monto: '<?= $monto_calc ?>', 
                                             doc_titulo: 'OPI Adjudicada - V°B° Jefatura (1/3)',
                                             titulo_compra: '<?= htmlspecialchars(addslashes($exp['titulo_compra'] ?? '')) ?>',
                                             proveedor: '<?= htmlspecialchars(addslashes($prov_str)) ?>',
                                             firmante_nombre: '<?= htmlspecialchars(addslashes($firmante_nom)) ?>',
                                             firmante_rut: '<?= htmlspecialchars(addslashes($firmante_rut)) ?>',
                                             firmante_cargo: '<?= htmlspecialchars(addslashes($firmante_cargo)) ?>',
                                             firmante_unidad: '<?= htmlspecialchars(addslashes($firmante_unidad)) ?>'
                                         })" class="btn-saas btn-saas-primary py-2.5 w-100 mb-4 justify-content-center fw-bold shadow-sm" style="background-color: #4f46e5; border-color: #4338ca; font-size: 14px;">
                                             <i class="bi bi-pen-fill"></i>
                                             Firmar OPI con FirmaGob (1/3)
                                         </button>
                                     <?php elseif ($t_aprobar): ?>
                                         <button type="submit" name="transicion_id" value="<?= $t_aprobar['id'] ?>" onclick="return confirm('¿Confirma la visación y autorización de este requerimiento?')" class="btn-saas btn-saas-primary py-2.5 w-100 mb-4 justify-content-center fw-bold shadow-sm" style="font-size: 14px;">
                                             <i class="bi bi-check-circle-fill"></i>
                                             <?= htmlspecialchars($t_aprobar['accion_label']) ?>
                                         </button>
                                     <?php elseif ($exp['estado_actual'] === 'EN_REVISION_JEFATURA'): ?>
                                         <button type="submit" name="accion" value="aprobar" onclick="return confirm('¿Confirma la visación y autorización de este requerimiento?')" class="btn-saas btn-saas-primary py-2.5 w-100 mb-4 justify-content-center fw-bold shadow-sm" style="font-size: 14px;">
                                             <i class="bi bi-check-circle-fill"></i>
                                             Aprobar V°B° / Autorizar Fondos
                                         </button>
                                     <?php else: ?>
                                         <div class="alert alert-secondary text-center small py-2 mb-4">No hay transiciones de aprobación disponibles.</div>
                                     <?php endif; ?>

                                    <!-- ÁREA DE OBSERVACIONES / MOTIVO -->
                                    <div class="border-top pt-3">
                                        <label class="form-label-saas mb-1">Observación / Motivo (Obligatorio para Devolver o Rechazar)</label>
                                        <textarea name="motivo_rechazo" rows="3" placeholder="Indique las razones de la devolución o rechazo..." class="form-control-saas mb-3 bg-light"></textarea>
                                        
                                        <div class="row g-2">
                                            <!-- BOTONES DE DEVOLUCIÓN -->
                                            <?php 
                                            $hay_devolver = false;
                                            foreach ($transiciones as $t): 
                                                if ($t['accion_codigo'] === 'DEVOLVER'):
                                                    $hay_devolver = true;
                                            ?>
                                                <div class="col-sm-6">
                                                    <button type="submit" name="transicion_id" value="<?= $t['id'] ?>" onclick="return confirm('¿Confirma la acción de <?= htmlspecialchars($t['accion_label']) ?>?')" class="btn-saas btn-saas-secondary w-100 justify-content-center py-2">
                                                        <i class="bi bi-arrow-counterclockwise text-warning"></i>
                                                        <?= htmlspecialchars($t['accion_label']) ?>
                                                    </button>
                                                </div>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            if (!$hay_devolver && $exp['estado_actual'] === 'EN_REVISION_JEFATURA'): ?>
                                                <div class="col-sm-6">
                                                    <button type="submit" name="accion" value="devolver" onclick="return confirm('¿Confirma devolver para corrección?')" class="btn-saas btn-saas-secondary w-100 justify-content-center py-2">
                                                        <i class="bi bi-arrow-counterclockwise text-warning"></i>
                                                        Devolver Solicitud
                                                    </button>
                                                </div>
                                            <?php endif; ?>

                                            <!-- BOTONES DE RECHAZO -->
                                            <?php 
                                            $hay_rechazar = false;
                                            foreach ($transiciones as $t): 
                                                if ($t['accion_codigo'] === 'RECHAZAR'):
                                                    $hay_rechazar = true;
                                            ?>
                                                <div class="col-sm-6">
                                                    <button type="submit" name="transicion_id" value="<?= $t['id'] ?>" onclick="return confirm('¿Confirma la acción de <?= htmlspecialchars($t['accion_label']) ?>?')" class="btn-saas btn-saas-danger w-100 justify-content-center py-2">
                                                        <i class="bi bi-x-circle text-danger"></i>
                                                        <?= htmlspecialchars($t['accion_label']) ?>
                                                    </button>
                                                </div>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            if (!$hay_rechazar && $exp['estado_actual'] === 'EN_REVISION_JEFATURA'): ?>
                                                <div class="col-sm-6">
                                                    <button type="submit" name="accion" value="rechazar" onclick="return confirm('¿Confirma el rechazo definitivo?')" class="btn-saas btn-saas-danger w-100 justify-content-center py-2">
                                                        <i class="bi bi-x-circle text-danger"></i>
                                                        Rechazar Solicitud
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="text-center py-3">
                                    <div class="p-3 bg-success-subtle text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 54px; height: 54px;">
                                        <i class="bi bi-check-lg fs-2"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark mb-1">Visación Completada</h5>
                                    <p class="text-secondary small mb-4">La solicitud ya ha sido procesada por la Jefatura de Unidad en esta fase.</p>
                                    
                                    <div class="bg-light rounded p-3 border d-inline-block text-start mx-auto" style="min-width: 280px;">
                                        <span class="form-label-saas mb-1" style="font-size: 9.5px;">Estado Actual del Expediente</span>
                                        <span class="fw-bold text-dark fs-6 d-block"><?= htmlspecialchars($exp['estado_nombre'] ?? $exp['estado_actual']) ?></span>
                                        <span class="saas-badge saas-badge-info mt-2">
                                            Responsable: <?= htmlspecialchars($exp['rol_responsable'] ?? 'Siguiente Etapa') ?>
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
            if (!str) return '';
            return String(str)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        const formatter = new Intl.NumberFormat('es-CL', {
            style: 'currency',
            currency: 'CLP',
            minimumFractionDigits: 0
        });

        function formatCurrency(value) {
            return formatter.format(value);
        }

        let modalAdjuntosInstance = null;
        let modalVerItemsInstance = null;

        document.addEventListener('DOMContentLoaded', () => {
            const elAdj = document.getElementById('modalAdjuntos');
            const elItm = document.getElementById('modalVerItems');
            if (elAdj) modalAdjuntosInstance = new bootstrap.Modal(elAdj);
            if (elItm) modalVerItemsInstance = new bootstrap.Modal(elItm);
        });

        function toggleFiltros() {
            const panel = document.getElementById('filtroPanel');
            if (!panel) return;
            panel.classList.toggle('d-none');
        }

        function abrirModalAdjuntos(docsArray, codigo, expId) {
            const codeEl = document.getElementById('modalAdjuntosCodigo');
            if (codeEl) codeEl.innerText = codigo;
            
            const btnZip = document.getElementById('btnDescargarZip');
            if (btnZip) btnZip.href = '?descargar_zip=' + expId;

            const listaContainer = document.getElementById('modalAdjuntosLista');
            if (!listaContainer) return;
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
                            <td class="text-center fw-bold text-dark">${cant} <span class="text-muted d-block" style="font-size: 10px;">${item.unidad_medida}</span></td>
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