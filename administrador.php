<?php 
// administrador.php - Vista Principal (V5.5 - Rediseño Profesionalizado de Bandeja y Firma)
require_once __DIR__ . '/admin_controller.php'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php 
    $titulo_pagina = "Aprobación Administración Municipal";
    include __DIR__ . '/head.php'; 
    ?>
</head>
<body class="bg-light text-slate-800 pb-20 font-sans">

    <?php include __DIR__ . '/nav.php'; ?>

    <!-- Inclusión de Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <div class="container mt-4 px-3 px-md-4">

        <!-- MENSAJES DE ALERTA -->
        <?php if($mensaje): ?>
            <?php 
            $alertClass = ($tipo_mensaje === 'error') ? 'danger' : (($tipo_mensaje === 'warning') ? 'warning' : 'success');
            $iconClass = ($tipo_mensaje === 'error') ? 'exclamation-triangle-fill' : (($tipo_mensaje === 'warning') ? 'arrow-counterclockwise' : 'check-circle-fill');
            ?>
            <div class="alert alert-<?= $alertClass ?> d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
                <i class="bi bi-<?= $iconClass ?> shrink-0"></i>
                <div class="small fw-semibold"><?= htmlspecialchars($mensaje) ?></div>
            </div>
        <?php endif; ?>

        <!-- VISTA: BANDEJA TABULADA DE ADM MUNICIPAL -->
        <?php if($vista === 'lista'): ?>
            <div class="row align-items-center mb-4 g-3">
                <div class="col-12 col-md">
                    <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="bi bi-bank text-primary"></i>
                        Bandeja de Administración Municipal
                    </h1>
                    <p class="text-muted small mb-0">Gestión centralizada de autorizaciones de cotización y firmas de OPIs institucionales.</p>
                </div>
            </div>

            <!-- PESTAÑAS NAVEGABLES (NAV TABS) -->
            <ul class="nav nav-tabs border-bottom mb-4" id="adminTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold d-flex align-items-center gap-2 py-2.5 px-3" id="cotizaciones-tab" data-bs-toggle="tab" data-bs-target="#cotizaciones-pane" type="button" role="tab">
                        <i class="bi bi-card-checklist text-primary"></i>
                        <span>Autorizar Cotizaciones</span>
                        <span class="badge bg-primary rounded-pill"><?= $count_cotizacion ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold d-flex align-items-center gap-2 py-2.5 px-3" id="opis-tab" data-bs-toggle="tab" data-bs-target="#opis-pane" type="button" role="tab">
                        <i class="bi bi-pen-fill text-indigo" style="color: #6366f1 !important;"></i>
                        <span>Firmar OPIs Definitivas</span>
                        <span class="badge bg-indigo rounded-pill" style="background-color: #6366f1 !important;"><?= $count_opi ?></span>
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="adminTabsContent">
                
                <!-- PESTAÑA 1: AUTORIZAR COTIZACIONES -->
                <div class="tab-pane fade show active" id="cotizaciones-pane" role="tabpanel" tabindex="0">
                    <div class="card shadow-sm border-light">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark">Solicitudes Pendientes de Autorización de Cotizar</h6>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1" style="font-size: 10px;"><?= $count_cotizacion ?> pendientes</span>
                        </div>
                        <div class="table-responsive rounded-bottom">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-uppercase small" style="font-size: 10px;">
                                    <tr>
                                        <th class="p-3">Requerimiento</th>
                                        <th class="p-3">Solicitante / Centro de Costo</th>
                                        <th class="p-3">Tipo de Compra</th>
                                        <th class="p-3 text-end" style="width: 180px;">Monto Estimado</th>
                                        <th class="p-3 text-center" style="width: 170px;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <?php if(empty($pendientes_cotizacion)): ?>
                                        <tr>
                                            <td colspan="5" class="p-4 text-center text-muted italic">No hay cotizaciones pendientes de autorización.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach($pendientes_cotizacion as $p): ?>
                                        <tr>
                                            <td class="p-3">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="fw-bold text-dark font-monospace"><?= $p['codigo_interno'] ?></span>
                                                    <button type="button" onclick="verTrazabilidad(<?= (int)$p['id'] ?>)" class="btn btn-link p-0 border-0 d-flex align-items-center" title="Ver Historial">
                                                        <i class="bi bi-clock-history text-primary" style="font-size: 13px;"></i>
                                                    </button>
                                                </div>
                                                <div class="text-truncate text-secondary small" style="max-width: 250px; font-size: 11px;"><?= htmlspecialchars($p['titulo_compra'] ?? '') ?></div>
                                            </td>
                                            <td class="p-3">
                                                <div class="fw-semibold text-secondary small"><?= htmlspecialchars($p['centro_costo']) ?></div>
                                                <div class="text-muted" style="font-size: 10px;"><?= htmlspecialchars($p['solicitante']) ?></div>
                                            </td>
                                            <td class="p-3">
                                                <span class="badge bg-light text-dark border px-2 py-1" style="font-size: 9px;">
                                                    <?= htmlspecialchars($p['tipo_compra_nom']) ?>
                                                </span>
                                            </td>
                                            <td class="p-3 text-end fw-bold font-monospace text-dark">
                                                <?= money($p['monto_estimado']) ?>
                                            </td>
                                            <td class="p-3 text-center">
                                                <a href="?view=revisar&id=<?= $p['id'] ?>" class="btn btn-primary btn-sm fw-bold px-3 shadow-sm d-inline-flex align-items-center gap-1">
                                                    <i class="bi bi-check2-square"></i> Revisar y Autorizar
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- PESTAÑA 2: FIRMAR OPIS DEFINITIVAS -->
                <div class="tab-pane fade" id="opis-pane" role="tabpanel" tabindex="0">
                    <div class="card shadow-sm border-light">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark">OPIs Pendientes de Firma Final (Con CDP Firmado por Finanzas)</h6>
                            <span class="badge bg-indigo-subtle text-indigo border border-indigo-subtle px-2.5 py-1" style="font-size: 10px; color: #6366f1 !important;"><?= $count_opi ?> pendientes</span>
                        </div>
                        <div class="table-responsive rounded-bottom">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-uppercase small" style="font-size: 10px;">
                                    <tr>
                                        <th class="p-3">Requerimiento</th>
                                        <th class="p-3">Proveedor Adjudicado</th>
                                        <th class="p-3">Solicitante / Centro Costo</th>
                                        <th class="p-3 text-end" style="width: 180px;">Monto Definitivo</th>
                                        <th class="p-3 text-center" style="width: 170px;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <?php if(empty($pendientes_opi)): ?>
                                        <tr>
                                            <td colspan="5" class="p-4 text-center text-muted italic">Bandeja al día. No hay OPIs pendientes de firma.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach($pendientes_opi as $p): ?>
                                        <tr>
                                            <td class="p-3">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="fw-bold text-dark font-monospace"><?= $p['codigo_interno'] ?></span>
                                                    <button type="button" onclick="verTrazabilidad(<?= (int)$p['id'] ?>)" class="btn btn-link p-0 border-0 d-flex align-items-center" title="Ver Historial">
                                                        <i class="bi bi-clock-history text-primary" style="font-size: 13px;"></i>
                                                    </button>
                                                </div>
                                                <div class="text-muted small text-uppercase tracking-wider" style="font-size: 9px;"><?= htmlspecialchars($p['tipo_compra_nom']) ?></div>
                                            </td>
                                            <td class="p-3">
                                                <?php if($p['proveedor']): ?>
                                                    <span class="fw-bold text-success font-monospace" style="font-size: 11px;"><i class="bi bi-building me-1"></i><?= htmlspecialchars($p['proveedor']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted small italic">Pendiente de adjudicación</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3">
                                                <div class="fw-semibold text-secondary small"><?= htmlspecialchars($p['centro_costo']) ?></div>
                                                <div class="text-muted" style="font-size: 10px;"><?= htmlspecialchars($p['solicitante']) ?></div>
                                            </td>
                                            <td class="p-3 text-end fw-bold font-monospace text-dark">
                                                <?= money($p['monto_definitivo'] ?? $p['monto_estimado']) ?>
                                            </td>
                                            <td class="p-3 text-center">
                                                <a href="?view=revisar&id=<?= $p['id'] ?>" class="btn btn-dark btn-sm fw-bold px-3 shadow-sm d-inline-flex align-items-center gap-1">
                                                    <i class="bi bi-pen-fill"></i> Revisar y Firmar OPI
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        <?php endif; ?>

        <!-- VISTA: REVISIÓN DE EXPEDIENTE -->
        <?php if($vista === 'revisar' && isset($exp)): ?>
            <?php $es_etapa_cotizacion = ($exp['estado_actual'] === 'EN_AUTORIZACION_COTIZACION'); ?>
            
            <div class="row align-items-center mb-4 g-3">
                <div class="col-12 col-md">
                    <?php if($es_etapa_cotizacion): ?>
                        <span class="badge bg-primary text-uppercase tracking-wider mb-1.5" style="font-size: 9px; letter-spacing: 0.5px;">📋 Autorización de Cotización Inicial</span>
                    <?php else: ?>
                        <span class="badge bg-indigo text-uppercase tracking-wider mb-1.5" style="font-size: 9px; letter-spacing: 0.5px; background-color: #6366f1 !important;">✍️ Firma y Emisión de OPI Final</span>
                    <?php endif; ?>

                    <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        Expediente: <span class="font-monospace text-primary">#<?= htmlspecialchars($exp['codigo_interno']) ?></span>
                        <button type="button" onclick="verTrazabilidad(<?= (int)$exp['id'] ?>)" class="btn btn-outline-primary btn-sm px-2.5 py-1 fw-bold shadow-sm d-inline-flex align-items-center gap-1.5" style="font-size: 11px;">
                            <i class="bi bi-clock-history"></i> Ver Historial
                        </button>
                    </h1>
                </div>
                <div class="col-12 col-md-auto text-start text-md-end">
                    <a href="administrador.php" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                        <i class="bi bi-arrow-left me-1"></i> Volver a la Bandeja
                    </a>
                </div>
            </div>

            <div class="row g-4">
                
                <!-- COLUMNA IZQUIERDA: RESUMEN Y ARCHIVOS -->
                <div class="col-lg-4 space-y-4">
                    
                    <!-- CONTEXTO -->
                    <div class="card shadow-sm border-light">
                        <div class="card-header bg-white py-3">
                            <h6 class="fw-bold mb-0 text-dark uppercase tracking-wider" style="font-size: 11px; letter-spacing: 0.5px;">Contexto de la Solicitud</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-flex flex-column gap-3 small">
                                <?php if($exp['titulo_compra']): ?>
                                    <div>
                                        <span class="text-muted fw-bold d-block text-uppercase" style="font-size: 9px;">Título Compra:</span>
                                        <span class="fw-bold text-dark fs-6"><?= htmlspecialchars($exp['titulo_compra']) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="row g-3">
                                    <div class="col-6">
                                        <span class="text-muted fw-bold d-block text-uppercase" style="font-size: 9px;">Unidad Solicitante:</span>
                                        <span class="text-secondary fw-semibold"><?= htmlspecialchars($exp['unidad']) ?></span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted fw-bold d-block text-uppercase" style="font-size: 9px;">Tipo de Compra:</span>
                                        <span class="text-secondary fw-semibold"><?= htmlspecialchars($exp['tipo_compra_nom']) ?></span>
                                    </div>
                                </div>
                                
                                <div>
                                    <span class="text-muted fw-bold d-block text-uppercase" style="font-size: 9px;">Centro de Costo:</span>
                                    <span class="badge bg-primary-subtle text-primary fw-bold text-wrap text-start mt-1 px-2.5 py-1.5 fs-6" style="border: 1px solid rgba(13,110,253,0.1);"><?= htmlspecialchars($exp['centro_costo']) ?></span>
                                </div>

                                <div class="d-flex flex-column gap-2 pt-2 border-top">
                                    <?php if($exp['id_contrato_suministro']): ?>
                                        <div class="d-flex justify-content-between align-items-center bg-teal-subtle text-teal-emphasis p-2 rounded-3 border border-teal-subtle">
                                            <span class="fw-bold text-uppercase" style="font-size: 9px;">ID Suministro:</span> 
                                            <span class="font-monospace fw-bold"><?= htmlspecialchars($exp['id_contrato_suministro']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if($exp['id_licitacion']): ?>
                                        <div class="d-flex justify-content-between align-items-center bg-purple-subtle text-purple-emphasis p-2 rounded-3 border border-purple-subtle">
                                            <span class="fw-bold text-uppercase" style="font-size: 9px;">ID Licitación (MP):</span> 
                                            <span class="font-monospace fw-bold"><?= htmlspecialchars($exp['id_licitacion']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if($exp['orden_compra_numero']): ?>
                                        <div class="d-flex justify-content-between align-items-center bg-dark text-white p-2 rounded-3">
                                            <span class="fw-bold text-uppercase" style="font-size: 9px;">N° Orden de Compra:</span> 
                                            <span class="font-monospace fw-bold"><?= htmlspecialchars($exp['orden_compra_numero']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if($exp['proveedor_adjudicado_id']): ?>
                                    <div class="bg-success-subtle border border-success-subtle p-3 rounded-3 mt-1 text-success-emphasis">
                                        <span class="fw-bold d-block text-uppercase mb-1" style="font-size: 9px;">Proveedor Adjudicado</span>
                                        <p class="font-bold mb-1 leading-tight fs-6"><?= htmlspecialchars($exp['proveedor_nombre']) ?></p>
                                        <p class="font-monospace small mb-0 text-muted">RUT: <?= htmlspecialchars($exp['proveedor_rut']) ?></p>
                                    </div>
                                <?php endif; ?>

                                <div class="pt-2 border-t mt-2">
                                    <span class="text-muted fw-bold d-block text-uppercase mb-1" style="font-size: 9px;">Justificación Técnica:</span>
                                    <div class="bg-light p-2.5 rounded border small text-secondary leading-relaxed" style="max-height: 180px; overflow-y: auto; font-size: 11px;">
                                        <?= nl2br(htmlspecialchars($exp['motivo_compra'])) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ARCHIVOS DE RESPALDO -->
                    <div class="card shadow-sm border-light">
                        <div class="card-header bg-white py-3">
                            <h6 class="fw-bold mb-0 text-dark uppercase tracking-wider" style="font-size: 11px; letter-spacing: 0.5px;">Archivos de Respaldo (<?= count($docs) ?>)</h6>
                        </div>
                        <div class="card-body p-3">
                            <?php if(empty($docs)): ?>
                                <div class="text-center py-4 bg-light border border-dashed rounded-3">
                                    <p class="text-muted small mb-0 italic">No hay documentos cargados en el expediente.</p>
                                </div>
                            <?php else: ?>
                                <div class="d-flex flex-column gap-2">
                                    <?php foreach($docs as $doc): ?>
                                        <a href="<?= htmlspecialchars($doc['ruta_archivo']) ?>" target="_blank" class="d-flex align-items-center justify-content-between p-2.5 bg-light border rounded-3 text-decoration-none hover-bg-gray transition">
                                            <div class="d-flex align-items-center gap-2 min-w-0 flex-grow-1">
                                                <i class="bi bi-file-earmark-text text-primary fs-5 shrink-0"></i>
                                                <div class="text-truncate">
                                                    <p class="mb-0 text-truncate small fw-bold text-dark"><?= htmlspecialchars($doc['nombre_original']) ?></p>
                                                    <p class="mb-0 text-uppercase text-muted" style="font-size: 9px;"><?= str_replace('_', ' ', $doc['tipo_doc']) ?></p>
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

                <!-- COLUMNA DERECHA: TABLA Y FORMULARIO DE ACCIÓN -->
                <div class="col-lg-8 space-y-4">
                    
                    <!-- TABLA DETALLE PRODUCTOS -->
                    <div class="card shadow-sm border-light">
                        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                            <h6 class="fw-bold mb-0 text-dark">
                                <i class="bi bi-cart3 text-secondary me-1"></i>
                                Detalle de Productos
                            </h6>
                            <div class="text-end">
                                <span class="text-muted text-uppercase fw-bold" style="font-size: 8px;"><?= $exp['monto_definitivo'] ? 'Monto Final Aprobado' : 'Monto Estimado Inicial' ?></span>
                                <div class="h5 fw-black text-success font-monospace mb-0"><?= money($exp['monto_definitivo'] ?? $exp['monto_estimado']) ?></div>
                            </div>
                        </div>
                        <div class="card-body bg-slate-50 p-3">
                            <div class="d-flex flex-column gap-3">
                                <?php foreach($items as $it): 
                                    $costo_linea = $it['cantidad'] * $it['precio_unitario'];
                                ?>
                                    <!-- Item Card -->
                                    <div class="bg-white border rounded-3 p-3 shadow-sm">
                                        <!-- Header del Item: Cuenta y Monto -->
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                            <div>
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <span class="font-monospace text-dark fw-bold small" style="font-size: 12px;"><?= $it['cuenta_codigo'] ?></span>
                                                    <?php if(!empty($it['ag_codigo'])): ?>
                                                        <span class="badge bg-secondary-subtle text-secondary-emphasis" style="font-size: 8px;">AG: <?= $it['ag_codigo'] ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <span class="text-muted d-block text-uppercase fw-bold" style="font-size: 8px;">Monto Total</span>
                                                <span class="fw-bold font-monospace text-dark" style="font-size: 14px;">
                                                    <?= money($costo_linea) ?>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Producto / Descripción -->
                                        <div class="bg-light p-2.5 rounded-3 mb-2">
                                            <div class="d-flex justify-content-between align-items-center gap-3">
                                                <div class="text-slate-800 small fw-bold leading-normal"><?= htmlspecialchars($it['descripcion']) ?></div>
                                                <div class="text-secondary small fw-bold text-nowrap text-end">
                                                    <?= floatval($it['cantidad']) ?> <span class="text-muted small" style="font-weight: normal;"><?= $it['unidad_medida'] ?></span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Precio Unitario y Convenio Marco -->
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-2 border-top border-light-subtle small">
                                            <div>
                                                <span class="text-muted" style="font-size: 10px;">Monto Unitario:</span>
                                                <span class="font-monospace fw-bold text-secondary" style="font-size: 11px;">
                                                    <?= money($it['precio_unitario']) ?>
                                                </span>
                                            </div>
                                            <?php if($exp['tipo_compra_cod'] === 'CONVENIO_MARCO'): ?>
                                                <div class="text-end">
                                                     <span class="text-muted" style="font-size: 10px;">ID Convenio Marco:</span>
                                                    <span class="font-monospace text-primary fw-bold" style="font-size: 11px;"><?= htmlspecialchars($it['id_producto_cm']) ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- CRITERIOS EVALUACIÓN LICITACIÓN -->
                    <?php if(!empty($criterios)): ?>
                    <div class="card border-purple bg-purple-subtle shadow-sm">
                        <div class="card-header bg-white border-purple text-purple-emphasis py-3">
                            <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">
                                <i class="bi bi-ui-checks text-purple"></i>
                                Criterios de Evaluación Licitación
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="table-responsive rounded-3 border border-purple-subtle">
                                <table class="table table-sm table-striped align-middle mb-0 bg-white">
                                    <thead class="table-light text-uppercase" style="font-size: 10px;">
                                        <tr>
                                            <th class="p-2 text-center" style="width: 60px;">N°</th>
                                            <th class="p-2">Descripción del Criterio</th>
                                            <th class="p-2 text-center" style="width: 120px;">Ponderación</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-purple-50">
                                        <?php foreach($criterios as $cr): ?>
                                        <tr>
                                            <td class="p-2 text-center fw-bold text-muted"><?= $cr['numero_criterio'] ?></td>
                                            <td class="p-2 text-dark small fw-medium"><?= htmlspecialchars($cr['descripcion']) ?></td>
                                            <td class="p-2 text-center fw-black text-primary"><?= floatval($cr['porcentaje']) ?>%</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ACCIÓN REQUERIDA DINÁMICA -->
                    <?php 
                    $transiciones = obtener_transiciones_disponibles($pdo, $exp['id']);
                    $t_aprobar = null;
                    $t_devolver = null;
                    $t_rechazar = null;
                    foreach ($transiciones as $t) {
                        if ($t['accion_codigo'] === 'APROBAR') $t_aprobar = $t;
                        if ($t['accion_codigo'] === 'DEVOLVER') $t_devolver = $t;
                        if ($t['accion_codigo'] === 'RECHAZAR') $t_rechazar = $t;
                    }
                    ?>

                    <?php if (!empty($es_accionable)): ?>
                        <?php if($es_etapa_cotizacion): ?>
                            <!-- CASO A: AUTORIZACIÓN DE COTIZACIÓN PREVIA -->
                            <div class="card shadow-sm border-light overflow-hidden mb-4">
                                <div class="card-header bg-white border-bottom py-3">
                                    <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                                        <i class="bi bi-card-checklist text-primary"></i>
                                        Acción Requerida: Autorizar Inicio de Cotización
                                    </h5>
                                </div>
                                <div class="card-body p-4">
                                    <div class="alert alert-primary-subtle text-primary-emphasis border border-primary-subtle p-3 rounded-3 mb-4">
                                        <div class="d-flex align-items-start gap-2">
                                            <i class="bi bi-info-circle-fill fs-5 shrink-0 text-primary"></i>
                                            <div>
                                                <h6 class="fw-bold mb-1">Autorización Previa para Adquisiciones</h6>
                                                <p class="mb-0 small">Este requerimiento cuenta con reserva presupuestaria inicial otorgada. Al autorizar la cotización, Adquisiciones procederá a la publicación y búsqueda de ofertas en el portal de Mercado Público / Compra Ágil.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($t_aprobar): ?>
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                            <input type="hidden" name="transicion_id" value="<?= $t_aprobar['id'] ?>">
                                            <input type="hidden" name="expediente_id" value="<?= $exp['id'] ?>">
                                            
                                            <button type="submit" onclick="return confirm('¿Confirma autorizar el inicio de cotización para este expediente?')" class="btn btn-primary py-2.5 w-100 fw-semibold shadow-sm transition d-flex justify-content-center align-items-center gap-2">
                                                <i class="bi bi-check-circle-fill"></i>
                                                <?= htmlspecialchars($t_aprobar['accion_label']) ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>

                        <?php else: ?>
                            <!-- CASO B: FIRMA Y EMISIÓN DE OPI FINAL -->
                            <div class="card shadow-sm border-light overflow-hidden mb-4">
                                <div class="card-header bg-white border-bottom py-3">
                                    <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                                        <i class="bi bi-pen-fill text-primary"></i>
                                        Acción Requerida: Firma y Emisión de OPI
                                    </h5>
                                </div>
                                <div class="card-body p-4">

                                    <!-- DESCARGAR OPI -->
                                    <div class="d-flex align-items-center justify-content-between p-3 bg-light border rounded-3 mb-4">
                                        <div class="d-flex align-items-center gap-2.5">
                                            <i class="bi bi-file-earmark-pdf-fill fs-3 text-primary"></i>
                                            <div>
                                                <h6 class="fw-bold mb-0 text-dark">Documento OPI Oficial</h6>
                                                <span class="text-muted small">Descargue el documento oficial para su revisión y firma</span>
                                            </div>
                                        </div>
                                        <a href="imprimir_opi.php?id=<?= $exp['id'] ?>&auto_download=1" target="_blank" class="btn btn-outline-primary btn-sm fw-semibold px-3 py-2 shadow-sm d-inline-flex align-items-center gap-1.5">
                                            <i class="bi bi-download"></i> Descargar OPI (PDF)
                                        </a>
                                    </div>

                                    <!-- SUBIR OPI FIRMADA Y AUTORIZAR -->
                                    <?php if ($t_aprobar): ?>
                                        <div class="bg-white border rounded-3 p-3.5 mb-2">
                                            <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                                                <i class="bi bi-upload text-primary"></i>
                                                Subir OPI Firmada y Finalizar
                                            </h6>
                                            
                                            <form method="POST" enctype="multipart/form-data">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                                <input type="hidden" name="transicion_id" value="<?= $t_aprobar['id'] ?>">
                                                <input type="hidden" name="expediente_id" value="<?= $exp['id'] ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label text-secondary fw-bold small text-uppercase" style="font-size: 9px;">Seleccionar Archivo OPI Firmado (PDF)</label>
                                                    <input type="file" name="pdf_firmado" accept="application/pdf" required class="form-control form-control-sm bg-light">
                                                </div>
                                                
                                                <button type="submit" onclick="return confirm('¿Confirma la acción de: <?= htmlspecialchars($t_aprobar['accion_label']) ?>?')" class="btn btn-primary py-2.5 w-100 fw-semibold shadow-sm transition d-flex justify-content-center align-items-center gap-2">
                                                    <i class="bi bi-shield-check"></i>
                                                    Subir OPI Firmada y Autorizar
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-secondary text-center small py-2 mb-4">No hay transiciones de firma/aprobación disponibles en esta fase.</div>
                                    <?php endif; ?>

                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- ACCIONES DE RECHAZO / DEVOLUCIÓN (COMUNES) -->
                        <div class="card border-light shadow-sm p-3.5 mb-4">
                            <div class="row g-3">
                                
                                <!-- DEVOLVER -->
                                <div class="col-md-6">
                                    <div class="card border-light-subtle bg-light h-100">
                                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                                            <div>
                                                <h6 class="fw-bold text-dark mb-1">Devolver a Corrección</h6>
                                                <p class="text-muted small mb-3" style="font-size: 11px;">Envíe el expediente de regreso si detecta inconsistencias en los antecedentes.</p>
                                            </div>
                                            <?php if ($t_devolver): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                                    <input type="hidden" name="transicion_id" value="<?= $t_devolver['id'] ?>">
                                                    <input type="hidden" name="expediente_id" value="<?= $exp['id'] ?>">
                                                    <textarea name="motivo_rechazo" required rows="2" class="form-control form-control-sm text-sm mb-2" placeholder="Motivo de la devolución..."></textarea>
                                                    <button type="submit" onclick="return confirm('¿Confirma la acción de: <?= htmlspecialchars($t_devolver['accion_label']) ?>?')" class="btn btn-outline-secondary btn-sm w-100 fw-semibold">
                                                        <?= htmlspecialchars($t_devolver['accion_label']) ?>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <div class="text-center text-muted small py-2 italic bg-white border rounded">Sin retorno configurable</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- RECHAZAR -->
                                <div class="col-md-6">
                                    <div class="card border-light-subtle bg-light h-100">
                                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                                            <div>
                                                <h6 class="fw-bold text-dark mb-1">Rechazar Definitivamente</h6>
                                                <p class="text-muted small mb-3" style="font-size: 11px;">Cancele permanentemente el expediente de compra.</p>
                                            </div>
                                            <?php if ($t_rechazar): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                                    <input type="hidden" name="transicion_id" value="<?= $t_rechazar['id'] ?>">
                                                    <input type="hidden" name="expediente_id" value="<?= $exp['id'] ?>">
                                                    <textarea name="motivo_rechazo" required rows="2" class="form-control form-control-sm text-sm mb-2" placeholder="Motivo del rechazo..."></textarea>
                                                    <button type="submit" onclick="return confirm('¿Confirma la acción de: <?= htmlspecialchars($t_rechazar['accion_label']) ?>?')" class="btn btn-danger btn-sm w-100 fw-bold">
                                                        <?= htmlspecialchars($t_rechazar['accion_label']) ?>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <div class="text-center text-muted small py-2 italic bg-white border rounded">Sin rechazo configurable</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    <?php else: ?>
                        <!-- BANNER DE VISACIÓN COMPLETADA -->
                        <div class="card shadow-sm border-light">
                            <div class="card-body p-4 text-center">
                                <div class="p-3 bg-success-subtle text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                                    <i class="bi bi-check-lg fs-3"></i>
                                </div>
                                <h5 class="fw-bold text-dark mb-1">Visación y Firma Completada</h5>
                                <p class="text-secondary small mb-4">El requerimiento ha sido procesado por la Administración Municipal.</p>
                                
                                <div class="bg-light rounded-3 p-3 border d-inline-block text-start mx-auto" style="min-width: 280px;">
                                    <span class="text-muted fw-bold d-block text-uppercase mb-1" style="font-size: 8px;">Estado Actual del Expediente</span>
                                    <span class="fw-bold text-dark fs-6 d-block"><?= htmlspecialchars($exp['estado_nombre'] ?? $exp['estado_actual']) ?></span>
                                    <span class="badge bg-primary-subtle text-primary-emphasis mt-2 px-2 py-1 fs-6" style="font-size: 9px; font-weight: bold;">
                                        Cargo Responsable: <?= htmlspecialchars($exp['rol_responsable'] ?? 'Siguiente Etapa') ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

        <?php endif; ?>

    </div>
</body>
</html>