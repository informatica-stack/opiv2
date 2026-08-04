<?php
// jefatura.php - Visación Técnica de Jefatura (Vista UI V5.0 - Homologada con mis_solicitudes.php)
require_once __DIR__ . '/jefatura_controller.php';
?><!DOCTYPE html>
<html lang="es">
<head>
    <?php 
    $titulo_pagina = "Bandeja de Entrada Jefatura";
    include __DIR__ . '/head.php'; 
    ?>
</head>
<body class="bg-slate-50 text-slate-800 pb-20 font-sans">

    <?php include __DIR__ . '/nav.php'; ?>

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

        <!-- VISTA: LISTA DE SOLICITUDES (TABS Y FILTROS) -->
        <?php if($vista !== 'revisar'): ?>
            
            <!-- CABECERA PRINCIPAL -->
            <div class="row align-items-center mb-4 g-3">
                <div class="col-12 col-md">
                    <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="bi bi-shield-check text-primary"></i>
                        Bandeja de Visación de Jefatura
                    </h1>
                    <p class="text-muted small mb-0">
                        <?php if(isset($_SESSION['es_subrogante']) && $_SESSION['es_subrogante']): ?>
                            <span class="badge bg-warning text-dark me-2">SUBROGANTE</span>
                        <?php endif; ?>
                        Revise, controle y autorice las solicitudes de su unidad municipal.
                    </p>
                </div>
                <div class="col-12 col-md-auto d-flex flex-wrap gap-2">
                    <button onclick="toggleFiltros()" class="btn btn-outline-secondary btn-sm shadow-sm d-flex align-items-center gap-1.5">
                        <i class="bi bi-funnel"></i>
                        Filtros
                    </button>
                </div>
            </div>

            <!-- PESTAÑAS NAVEGABLES (TABS) -->
            <div class="d-flex border-bottom mb-4 overflow-x-auto">
                <a href="jefatura.php?view=pendientes" class="px-4 py-2.5 text-decoration-none fw-bold small border-bottom border-2 <?= $vista === 'pendientes' ? 'border-primary text-primary bg-white rounded-top' : 'border-transparent text-secondary hover-text-dark' ?> text-nowrap d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history"></i>
                    Pendientes de Visación
                    <span class="badge rounded-pill <?= $vista === 'pendientes' ? 'bg-primary text-white' : 'bg-secondary-subtle text-secondary-emphasis' ?>"><?= $count_pendientes ?></span>
                </a>
                <a href="jefatura.php?view=procesadas" class="px-4 py-2.5 text-decoration-none fw-bold small border-bottom border-2 <?= $vista === 'procesadas' ? 'border-primary text-primary bg-white rounded-top' : 'border-transparent text-secondary hover-text-dark' ?> text-nowrap d-flex align-items-center gap-2">
                    <i class="bi bi-check2-square"></i>
                    Procesadas por Mí
                    <span class="badge rounded-pill <?= $vista === 'procesadas' ? 'bg-primary text-white' : 'bg-secondary-subtle text-secondary-emphasis' ?>"><?= $count_procesadas ?></span>
                </a>
                <a href="jefatura.php?view=todas" class="px-4 py-2.5 text-decoration-none fw-bold small border-bottom border-2 <?= $vista === 'todas' ? 'border-primary text-primary bg-white rounded-top' : 'border-transparent text-secondary hover-text-dark' ?> text-nowrap d-flex align-items-center gap-2">
                    <i class="bi bi-diagram-3"></i>
                    Todas la Unidad
                    <span class="badge rounded-pill <?= $vista === 'todas' ? 'bg-primary text-white' : 'bg-secondary-subtle text-secondary-emphasis' ?>"><?= $count_todas ?></span>
                </a>
            </div>

            <!-- PANEL DE FILTROS -->
            <div id="filtroPanel" class="card shadow-sm mb-4 <?= ($f_q || $f_tipo || $f_estado || $f_desde || $f_hasta) ? '' : 'd-none' ?>">
                <div class="card-body p-3">
                    <form method="GET" action="jefatura.php">
                        <input type="hidden" name="view" value="<?= htmlspecialchars($vista) ?>">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-sm-6 col-lg-3">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Buscar (ID o Título)</label>
                                <input type="text" name="f_q" value="<?= htmlspecialchars($f_q) ?>" class="form-control form-control-sm">
                            </div>
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Tipo de Compra</label>
                                <select name="f_tipo" class="form-select form-select-sm bg-white">
                                    <option value="">Todos</option>
                                    <?php foreach($tipos_compra_filtro as $t): ?>
                                        <option value="<?= $t['id'] ?>" <?= $f_tipo==$t['id']?'selected':'' ?>><?= htmlspecialchars($t['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Estado</label>
                                <select name="f_estado" class="form-select form-select-sm bg-white">
                                    <option value="">Todos</option>
                                    <?php foreach($estados_filtro as $e): ?>
                                        <option value="<?= $e['codigo'] ?>" <?= $f_estado==$e['codigo']?'selected':'' ?>><?= htmlspecialchars($e['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Desde</label>
                                        <input type="date" name="f_desde" value="<?= htmlspecialchars($f_desde) ?>" class="form-control form-control-sm text-secondary">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Hasta</label>
                                        <input type="date" name="f_hasta" value="<?= htmlspecialchars($f_hasta) ?>" class="form-control form-control-sm text-secondary">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-2 d-flex gap-2 justify-content-end mt-2 mt-lg-0">
                                <a href="jefatura.php?view=<?= htmlspecialchars($vista) ?>" class="btn btn-light btn-sm w-100 fw-bold border">Limpiar</a>
                                <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold shadow-sm">Aplicar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TABLA PRINCIPAL DE SOLICITUDES HOMOLOGADA -->
            <div class="card shadow-sm border-light mb-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0" style="min-width: 1000px;">
                        <thead class="table-light text-uppercase small text-secondary">
                            <tr>
                                <th class="p-3 text-nowrap" style="width: 180px;">ID / Fecha / Prioridad</th>
                                <th class="p-3" style="min-width: 250px;">Trámite / Solicitante / CC</th>
                                <th class="p-3 text-nowrap" style="width: 150px;">Clasificación</th>
                                <th class="p-3 text-nowrap" style="width: 180px;">Estado Actual</th>
                                <th class="p-3 text-end text-nowrap" style="width: 150px;">Monto Estimado</th>
                                <th class="p-3 text-center text-nowrap" style="width: 150px;">Gestión</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($solicitudes) > 0): ?>
                                <?php foreach($solicitudes as $row): ?>
                                <tr>
                                    <td class="p-3 text-nowrap">
                                        <button type="button" onclick="abrirModalVerItems(<?= htmlspecialchars(json_encode($row['items_detalle']), ENT_QUOTES, 'UTF-8') ?>, '<?= $row['codigo_interno'] ?>')" class="btn btn-link p-0 text-start font-monospace fw-bold text-primary text-decoration-underline mb-1">
                                            <?= htmlspecialchars($row['codigo_interno']) ?>
                                        </button>
                                        <div class="text-muted small" style="font-size: 11px;">
                                            <i class="bi bi-clock me-1"></i>
                                            <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?>
                                        </div>
                                        <span class="badge border mt-2 d-inline-block <?= $row['prioridad_css'] ?>" style="font-size: 10px;">
                                            <?= htmlspecialchars($row['prioridad_nombre']) ?>
                                        </span>
                                    </td>

                                    <td class="p-3">
                                        <div class="fw-bold text-dark mb-1 leading-snug break-words" style="max-width: 450px;">
                                            <?= htmlspecialchars($row['titulo_compra'] ?? 'Sin Título') ?>
                                        </div>
                                        <div class="text-secondary small mb-1 break-words" style="max-width: 450px; font-size: 11px; line-height: 1.4;">
                                            <?= htmlspecialchars($row['motivo_compra']) ?>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 flex-wrap text-muted fw-bold" style="font-size: 9px; letter-spacing: 0.5px;">
                                            <span><i class="bi bi-person-fill me-0.5"></i> <?= htmlspecialchars($row['solicitante']) ?></span>
                                            <span>•</span>
                                            <span><i class="bi bi-tag-fill me-0.5"></i> CC: <?= htmlspecialchars($row['cc_nombre']) ?></span>
                                        </div>
                                        
                                        <?php if($row['docs_adjuntos']): 
                                            $docs_array = explode('||', $row['docs_adjuntos']);
                                            $docs_count = count($docs_array);
                                            $docs_json = htmlspecialchars(json_encode($docs_array), ENT_QUOTES, 'UTF-8');
                                        ?>
                                            <div class="mt-2">
                                                <button type="button" onclick="abrirModalAdjuntos(<?= $docs_json ?>, '<?= htmlspecialchars($row['codigo_interno']) ?>', <?= $row['id'] ?>)" class="btn btn-outline-primary btn-sm py-1 px-2.5 d-inline-flex align-items-center gap-1.5 font-bold" style="font-size: 10px;">
                                                    <i class="bi bi-paperclip"></i>
                                                    Ver Archivos (<?= $docs_count ?>)
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td class="p-3 text-nowrap">
                                        <span class="badge bg-light text-dark border px-2 py-1.5 fw-bold" style="font-size: 11px;">
                                            <?= htmlspecialchars($row['tipo_compra_nom']) ?>
                                        </span>
                                    </td>

                                    <td class="p-3 text-nowrap">
                                        <span class="badge px-2.5 py-1.5 <?= color_estado($row['estado_actual']) ?>" style="font-size: 11px;">
                                            <?= htmlspecialchars($row['estado_nombre']) ?>
                                        </span>

                                        <div class="mt-1.5">
                                            <button type="button" onclick="verTrazabilidad(<?= (int)$row['id'] ?>)" class="btn btn-link p-0 text-decoration-none text-secondary d-flex align-items-center gap-1" style="font-size: 11px;">
                                                <i class="bi bi-clock-history text-primary"></i>
                                                Ver Historial
                                            </button>
                                        </div>
                                    </td>

                                    <td class="p-3 text-end font-monospace fw-bold text-dark text-nowrap">
                                        <?= money($row['monto_estimado']) ?>
                                    </td>

                                    <td class="p-3 text-center text-nowrap">
                                        <a href="jefatura.php?view=revisar&id=<?= $row['id'] ?>" class="btn btn-primary btn-sm fw-bold px-3 shadow-sm d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-search"></i>
                                            <span>Revisar</span>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="p-5 text-center text-muted">
                                        <div class="d-flex flex-column align-items-center justify-content-center">
                                            <i class="bi bi-inbox fs-1 text-slate-300 mb-2"></i>
                                            <p class="mb-0 fw-semibold">No se encontraron solicitudes en esta vista.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PAGINACIÓN -->
            <?php if($total_pages > 1): ?>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                    <div class="text-muted small">
                        Mostrando página <b><?= $page ?></b> de <b><?= $total_pages ?></b> (Total: <b><?= $total_records ?></b> solicitudes)
                    </div>
                    <nav aria-label="Navegación de solicitudes">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= $base_url . ($page - 1) ?>">Anterior</a>
                            </li>
                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= $base_url . $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= $base_url . ($page + 1) ?>">Siguiente</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>

        <?php endif; ?>

        <!-- VISTA: REVISIÓN DE EXPEDIENTE -->
        <?php if($vista === 'revisar' && isset($exp)): ?>
            
            <div class="row align-items-center mb-4 g-3">
                <div class="col-12 col-md">
                    <span class="badge bg-primary text-uppercase tracking-wider mb-1.5" style="font-size: 9px; letter-spacing: 0.5px;">Fase: Visación de Jefatura</span>
                    <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        Expediente: <span class="font-monospace text-primary">#<?= htmlspecialchars($exp['codigo_interno']) ?></span>
                        <button type="button" onclick="verTrazabilidad(<?= (int)$exp['id'] ?>)" class="btn btn-outline-primary btn-sm px-2.5 py-1 fw-bold shadow-sm d-inline-flex align-items-center gap-1.5" style="font-size: 11px;">
                            <i class="bi bi-clock-history"></i> Ver Historial
                        </button>
                    </h1>
                </div>
                <div class="col-12 col-md-auto text-start text-md-end">
                    <a href="jefatura.php" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                        <i class="bi bi-arrow-left me-1"></i> Volver a la Bandeja
                    </a>
                </div>
            </div>

            <div class="row g-4">
                
                <!-- COLUMNA IZQUIERDA: RESUMEN Y ARCHIVOS -->
                <div class="col-lg-4 space-y-4">
                    
                    <!-- INFO GENERAL -->
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
                                
                                <div>
                                    <span class="text-muted fw-bold d-block text-uppercase" style="font-size: 9px;">Solicitante:</span>
                                    <span class="text-secondary fw-semibold"><?= htmlspecialchars($exp['solicitante']) ?></span>
                                </div>
                                
                                <div>
                                    <span class="text-muted fw-bold d-block text-uppercase" style="font-size: 9px;">Unidad Origen:</span>
                                    <span class="text-secondary fw-semibold"><?= htmlspecialchars($exp['unidad']) ?></span>
                                </div>
                                
                                <div>
                                    <span class="text-muted fw-bold d-block text-uppercase" style="font-size: 9px;">Centro de Costo:</span>
                                    <span class="text-secondary fw-semibold"><?= htmlspecialchars($exp['centro_costo']) ?></span>
                                </div>
                                
                                <div>
                                    <span class="text-muted fw-bold d-block text-uppercase" style="font-size: 9px;">Tipo de Compra:</span>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($exp['tipo_compra_nom']) ?></span>
                                </div>
                                
                                <div>
                                    <span class="text-muted fw-bold d-block text-uppercase" style="font-size: 9px;">Justificación / Motivo:</span>
                                    <p class="text-secondary mb-0 leading-relaxed bg-light p-2.5 rounded border border-light-subtle" style="font-size: 11px;">
                                        <?= nl2br(htmlspecialchars($exp['motivo_compra'])) ?>
                                    </p>
                                </div>

                                <?php if($exp['proveedor_nombre']): ?>
                                    <div>
                                        <span class="text-muted fw-bold d-block text-uppercase" style="font-size: 9px;">Proveedor Sugerido / Adjudicado:</span>
                                        <span class="fw-bold text-primary"><?= htmlspecialchars($exp['proveedor_rut']) ?> - <?= htmlspecialchars($exp['proveedor_nombre']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- ARCHIVOS ADJUNTOS -->
                    <div class="card shadow-sm border-light">
                        <div class="card-header bg-white py-3">
                            <h6 class="fw-bold mb-0 text-dark uppercase tracking-wider" style="font-size: 11px; letter-spacing: 0.5px;">Documentos de Respaldo</h6>
                        </div>
                        <div class="card-body p-3">
                            <?php if(empty($docs)): ?>
                                <p class="text-muted small mb-0 italic">No hay archivos adjuntos a este expediente.</p>
                            <?php else: ?>
                                <div class="d-flex flex-column gap-2">
                                    <?php foreach($docs as $doc): 
                                        $is_anulada = ($doc['tipo_doc'] === 'OPI_ANULADA');
                                    ?>
                                        <a href="<?= htmlspecialchars($doc['ruta_archivo']) ?>" target="_blank" class="d-flex align-items-center justify-content-between p-2.5 bg-light border rounded-3 text-decoration-none hover-bg-gray transition">
                                            <div class="d-flex align-items-center gap-2 min-w-0 flex-1">
                                                <i class="bi bi-file-earmark-text text-primary fs-5 shrink-0"></i>
                                                <div class="text-truncate flex-1">
                                                    <p class="mb-0 text-truncate small <?= $is_anulada ? 'text-danger text-decoration-line-through' : 'text-dark fw-bold' ?>" style="max-width: 220px;"><?= htmlspecialchars($doc['nombre_original']) ?></p>
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

                <!-- COLUMNA DERECHA: TABLA DE PRODUCTOS E ITEMS -->
                <div class="col-lg-8 space-y-4">
                    
                    <div class="card shadow-sm border-light">
                        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                            <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                                <i class="bi bi-cart3 text-secondary"></i>
                                Detalle de Productos / Servicios
                            </h6>
                            <div class="text-end">
                                <span class="text-muted text-uppercase fw-bold mb-0" style="font-size: 9px;">Monto Estimado Solicitado</span>
                                <div class="h5 fw-black text-primary mb-0 font-monospace"><?= money($exp['monto_estimado']) ?></div>
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
                                                <span class="text-muted d-block text-uppercase fw-bold" style="font-size: 8px;">Monto C/IVA</span>
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
                                                    <span class="text-muted" style="font-size: 10px;">ID CM:</span>
                                                    <span class="font-monospace text-primary fw-bold" style="font-size: 11px;"><?= htmlspecialchars($it['id_producto_cm']) ?></span>
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
                    <div class="card border-info bg-info-subtle shadow-sm">
                        <div class="card-header bg-white border-info text-info-emphasis py-3">
                            <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">
                                <i class="bi bi-calculator-fill text-info"></i>
                                Criterios de Evaluación Propuestos
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="table-responsive rounded-3 border">
                                <table class="table table-sm table-striped align-middle mb-0 bg-white">
                                    <thead class="table-light text-uppercase" style="font-size: 10px;">
                                        <tr>
                                            <th class="p-2 text-center" style="width: 60px;">N°</th>
                                            <th class="p-2">Descripción del Criterio</th>
                                            <th class="p-2 text-center" style="width: 100px;">Ponderación</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($criterios as $cr): ?>
                                        <tr>
                                            <td class="p-2 text-center fw-bold text-secondary"><?= $cr['numero_criterio'] ?></td>
                                            <td class="p-2 fw-semibold text-dark small"><?= htmlspecialchars($cr['nombre_criterio']) ?></td>
                                            <td class="p-2 text-center fw-black text-primary"><?= floatval($cr['porcentaje']) ?>%</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- TARJETA DE RESOLUCIÓN -->
                    <div class="card shadow-lg border-primary">
                        <div class="card-header bg-primary text-white py-3">
                            <h5 class="fw-bold mb-0">Resolución de Jefatura</h5>
                        </div>
                        <div class="card-body p-4">
                            <?php if (!empty($es_accionable)): ?>
                                <p class="text-secondary small mb-4">Seleccione una de las acciones autorizadas por el flujo para proceder con el expediente.</p>
                                
                                <?php 
                                $transiciones = obtener_transiciones_disponibles($pdo, $exp['id']); 
                                ?>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="expediente_id" value="<?= $exp['id'] ?>">
                                    
                                    <!-- ACCIONES DE APROBACIÓN (BOTÓN VERDE PRINCIPAL) -->
                                    <?php 
                                    $has_approvals = false;
                                    foreach ($transiciones as $t): 
                                        if ($t['accion_codigo'] === 'APROBAR'): 
                                            $has_approvals = true;
                                    ?>
                                        <button type="submit" name="transicion_id" value="<?= $t['id'] ?>" onclick="return confirm('¿Confirma la acción de <?= htmlspecialchars($t['accion_label']) ?>?')" class="btn btn-success btn-lg w-100 py-3 mb-4 shadow d-flex align-items-center justify-content-center gap-2 fw-bold">
                                            <i class="bi bi-check-circle-fill"></i>
                                            <?= htmlspecialchars($t['accion_label']) ?>
                                        </button>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    if (!$has_approvals):
                                    ?>
                                        <div class="alert alert-secondary text-center small py-2.5 mb-4">No hay transiciones de aprobación disponibles.</div>
                                    <?php endif; ?>

                                    <!-- ÁREA DE COMENTARIOS (OBLIGATORIA PARA RETORNAR O RECHAZAR) -->
                                    <div class="border-top pt-4">
                                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Comentario / Motivo de la Observación (Obligatorio para Devolver/Rechazar)</label>
                                        <textarea name="motivo_rechazo" rows="3" class="form-control text-sm mb-4 bg-light"></textarea>
                                        
                                        <div class="row g-2">
                                            <!-- BOTONES DE DEVOLUCIÓN -->
                                            <?php foreach ($transiciones as $t): 
                                                if ($t['accion_codigo'] === 'DEVOLVER'):
                                            ?>
                                                <div class="col-sm-6">
                                                    <button type="submit" name="transicion_id" value="<?= $t['id'] ?>" onclick="return confirm('¿Confirma la acción de <?= htmlspecialchars($t['accion_label']) ?>?')" class="btn btn-warning w-100 py-2.5 fw-bold text-dark shadow-sm d-flex align-items-center justify-content-center gap-2">
                                                        <i class="bi bi-arrow-counterclockwise"></i>
                                                        <?= htmlspecialchars($t['accion_label']) ?>
                                                    </button>
                                                </div>
                                            <?php 
                                                endif;
                                            endforeach; ?>

                                            <!-- BOTONES DE RECHAZO -->
                                            <?php foreach ($transiciones as $t): 
                                                if ($t['accion_codigo'] === 'RECHAZAR'):
                                            ?>
                                                <div class="col-sm-6">
                                                    <button type="submit" name="transicion_id" value="<?= $t['id'] ?>" onclick="return confirm('¿Confirma la acción de <?= htmlspecialchars($t['accion_label']) ?>?')" class="btn btn-outline-danger w-100 py-2.5 fw-bold d-flex align-items-center justify-content-center gap-2">
                                                        <i class="bi bi-x-circle-fill"></i>
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
                                <div class="text-center py-2">
                                    <div class="p-3 bg-success-subtle text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                                        <i class="bi bi-check-lg fs-3"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark mb-1">Visación Completada</h5>
                                    <p class="text-secondary small mb-4">La solicitud ha sido procesada por la Jefatura de Unidad en esta fase.</p>
                                    
                                    <div class="bg-light rounded-3 p-3 border d-inline-block text-start mx-auto" style="min-width: 280px;">
                                        <span class="text-muted fw-bold d-block text-uppercase mb-1" style="font-size: 8px;">Estado Actual del Expediente</span>
                                        <span class="fw-bold text-dark fs-6 d-block"><?= htmlspecialchars($exp['estado_nombre'] ?? $exp['estado_actual']) ?></span>
                                        <span class="badge bg-primary-subtle text-primary-emphasis mt-2 px-2 py-1 fs-6" style="font-size: 9px; font-weight: bold;">
                                            Cargo Responsable: <?= htmlspecialchars($exp['rol_responsable'] ?? 'Siguiente Etapa') ?>
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

    <!-- MODAL ADJUNTOS (BOOTSTRAP 5) -->
    <div class="modal fade" id="modalAdjuntos" tabindex="-1" aria-labelledby="modalAdjuntosLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-dark" id="modalAdjuntosLabel">Documentos Adjuntos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="bg-light border p-3 rounded-3 mb-3 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
                        <div>
                            <span class="text-uppercase text-muted fw-bold" style="font-size: 9px; letter-spacing: 0.5px;">Expediente:</span>
                            <div id="modalAdjuntosCodigo" class="font-monospace fw-bold text-dark"></div>
                        </div>
                        <a id="btnDescargarZip" href="#" class="btn btn-primary btn-sm fw-bold shadow-sm d-flex align-items-center gap-1.5 w-100 w-sm-auto justify-content-center">
                            <i class="bi bi-download"></i>
                            Bajar ZIP
                        </a>
                    </div>
                    
                    <div id="modalAdjuntosLista" class="d-flex flex-column gap-2 overflow-y-auto" style="max-height: 350px;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar Visor</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DETALLE ÍTEMS (BOOTSTRAP 5) -->
    <div class="modal fade" id="modalVerItems" tabindex="-1" aria-labelledby="modalVerItemsLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-3 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-dark" id="modalVerItemsLabel">Detalle de Ítems del Requerimiento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="bg-light border p-3 rounded-3 mb-3">
                        <span class="text-uppercase text-muted fw-bold" style="font-size: 9px; letter-spacing: 0.5px;">Expediente:</span>
                        <div id="modalVerItemsCodigo" class="font-monospace fw-bold text-primary"></div>
                    </div>
                    
                    <div class="table-responsive rounded-3 border">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-uppercase small text-secondary">
                                <tr>
                                    <th class="p-3">Descripción del Producto/Servicio</th>
                                    <th class="p-3 text-center" style="width: 100px;">Cant.</th>
                                    <th class="p-3 text-end" style="width: 150px;">Valor Unit. Ingresado</th>
                                    <th class="p-3 text-end" style="width: 160px;">Total Línea</th>
                                </tr>
                            </thead>
                            <tbody id="modalVerItemsBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar Visor</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Inclusión de Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

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
                    link.className = 'd-flex align-items-center justify-content-between p-2.5 bg-light border rounded-3 text-decoration-none hover-bg-gray transition mb-2';
                    
                    link.innerHTML = `
                        <div class="d-flex align-items-center gap-2 min-w-0 flex-1">
                            <i class="bi bi-file-earmark-text text-primary fs-5 shrink-0"></i>
                            <div class="text-truncate flex-1">
                                <p class="mb-0 text-truncate small ${titleClass}" style="max-width: 320px;">${nombreOriginal}</p>
                                <p class="mb-0 small text-uppercase tracking-wide ${subtitleClass}" style="font-size: 9px;">${tipoDoc} - ${fecha}</p>
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
                tbody.innerHTML = `<tr><td colspan="4" class="p-4 text-center text-muted italic">No hay ítems registrados.</td></tr>`;
            } else {
                items.forEach(item => {
                    const cant = parseFloat(item.cantidad);
                    const prec = parseFloat(item.precio_unitario);
                    const tr = `
                        <tr class="align-middle">
                            <td class="p-3 text-secondary fw-semibold small">${escapeHTML(item.descripcion)}</td>
                            <td class="p-3 text-center fw-bold text-dark">${cant} <span class="text-muted d-block" style="font-size: 10px;">${item.unidad_medida}</span></td>
                            <td class="p-3 text-end text-muted font-monospace">${formatCurrency(prec)}</td>
                            <td class="p-3 text-end fw-bold text-dark font-monospace">${formatCurrency(cant * prec)}</td>
                        </tr>
                    `;
                    tbody.innerHTML += tr;
                });
            }
            
            if (modalVerItemsInstance) modalVerItemsInstance.show();
        }
    </script>
</body>
</html>