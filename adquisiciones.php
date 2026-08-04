<?php 
// adquisiciones.php - Vista Separada (V5.3 - Paso Previo Recepción y UI Actualizada)
require_once __DIR__ . '/adquisiciones_controller.php'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php 
    $titulo_pagina = "Bandeja de Adquisiciones";
    include __DIR__ . '/head.php'; 
    ?>
</head>
<body class="bg-light text-slate-800 pb-20 font-sans">

    <?php include __DIR__ . '/nav.php'; ?>

    <!-- Inclusión de Bootstrap 5 JS Bundle después de nav.php -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <div class="container mt-4 px-3 px-md-4">
        
        <!-- ALERTAS DE SISTEMA -->
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

        <!-- VISTA: TABLA PRINCIPAL DE ADQUISICIONES HOMOLOGADA -->
        <?php if($vista !== 'gestionar'): ?>
            
            <!-- CABECERA PRINCIPAL -->
            <div class="row align-items-center mb-4 g-3">
                <div class="col-12 col-md">
                    <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="bi bi-cart3 text-primary"></i>
                        Bandeja de Adquisiciones
                    </h1>
                    <p class="text-muted small mb-0">Todas las tareas asignadas a su departamento por el flujo de compras municipal.</p>
                </div>
                <div class="col-12 col-md-auto d-flex flex-wrap gap-2">
                    <button onclick="toggleFiltros()" class="btn btn-outline-secondary btn-sm shadow-sm d-flex align-items-center gap-1.5">
                        <i class="bi bi-funnel"></i>
                        Filtros
                    </button>
                </div>
            </div>

            <!-- PESTAÑAS NAVEGABLES (TABS) HOMOLOGADAS -->
            <div class="d-flex border-bottom mb-4 overflow-x-auto">
                <a href="adquisiciones.php?view=pendientes" class="px-4 py-2.5 text-decoration-none fw-bold small border-bottom border-2 <?= ($vista === 'pendientes' || $vista === 'lista') ? 'border-primary text-primary bg-white rounded-top' : 'border-transparent text-secondary hover-text-dark' ?> text-nowrap d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history"></i>
                    Pendientes de Gestión
                    <span class="badge rounded-pill <?= ($vista === 'pendientes' || $vista === 'lista') ? 'bg-primary text-white' : 'bg-secondary-subtle text-secondary-emphasis' ?>"><?= $count_pendientes ?></span>
                </a>
                <a href="adquisiciones.php?view=procesadas" class="px-4 py-2.5 text-decoration-none fw-bold small border-bottom border-2 <?= $vista === 'procesadas' ? 'border-primary text-primary bg-white rounded-top' : 'border-transparent text-secondary hover-text-dark' ?> text-nowrap d-flex align-items-center gap-2">
                    <i class="bi bi-check2-square"></i>
                    Procesados por Mí
                    <span class="badge rounded-pill <?= $vista === 'procesadas' ? 'bg-primary text-white' : 'bg-secondary-subtle text-secondary-emphasis' ?>"><?= $count_procesadas ?></span>
                </a>
                <a href="adquisiciones.php?view=todas" class="px-4 py-2.5 text-decoration-none fw-bold small border-bottom border-2 <?= $vista === 'todas' ? 'border-primary text-primary bg-white rounded-top' : 'border-transparent text-secondary hover-text-dark' ?> text-nowrap d-flex align-items-center gap-2">
                    <i class="bi bi-diagram-3"></i>
                    Todas las Solicitudes
                    <span class="badge rounded-pill <?= $vista === 'todas' ? 'bg-primary text-white' : 'bg-secondary-subtle text-secondary-emphasis' ?>"><?= $count_todas ?></span>
                </a>
            </div>

            <!-- PANEL DE FILTROS -->
            <div id="filtroPanel" class="card shadow-sm mb-4 <?= ($f_q || $f_tipo || $f_estado || $f_desde || $f_hasta) ? '' : 'd-none' ?>">
                <div class="card-body p-3">
                    <form method="GET" action="adquisiciones.php">
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
                                <a href="adquisiciones.php?view=<?= htmlspecialchars($vista) ?>" class="btn btn-light btn-sm w-100 fw-bold border">Limpiar</a>
                                <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold shadow-sm">Aplicar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TABLA PRINCIPAL HOMOLOGADA DE 6 COLUMNAS -->
            <div class="card shadow-sm border-light mb-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0" style="min-width: 1000px;">
                        <thead class="table-light text-uppercase small text-secondary">
                            <tr>
                                <th class="p-3 text-nowrap" style="width: 180px;">ID / Fecha / Prioridad</th>
                                <th class="p-3" style="min-width: 250px;">Trámite / Solicitante / CC</th>
                                <th class="p-3 text-nowrap" style="width: 150px;">Clasificación</th>
                                <th class="p-3 text-nowrap" style="width: 180px;">Fase / Estado Actual</th>
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
                                            <i class="bi bi-person me-1"></i><?= htmlspecialchars($row['solicitante']) ?> (<?= htmlspecialchars($row['unidad_nombre']) ?>)
                                        </div>
                                        <div class="text-uppercase text-muted fw-bold mb-2" style="font-size: 9px; letter-spacing: 0.5px;">
                                            <i class="bi bi-tag-fill me-1"></i>
                                            CC: <?= htmlspecialchars($row['cc_nombre']) ?>
                                        </div>
                                        
                                        <?php if(!empty($row['docs_adjuntos'])): 
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
                                        <span class="badge bg-light text-dark border px-2 py-1.5 fw-bold d-block mb-1" style="font-size: 11px;">
                                            <?= htmlspecialchars($row['tipo_compra_nom']) ?>
                                        </span>
                                        <?php if(!empty($row['rango_utm_nombre'])): ?>
                                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1" style="font-size: 9px;">
                                                <?= htmlspecialchars($row['rango_utm_nombre']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="p-3 text-nowrap">
                                        <span class="badge <?= color_estado($row['estado_actual']) ?> px-2.5 py-1.5 rounded-2 d-inline-block text-wrap" style="font-size: 10px; max-width: 180px;">
                                            <?= htmlspecialchars($row['estado_nombre']) ?>
                                        </span>
                                        <div class="mt-1.5">
                                            <button type="button" onclick="verTrazabilidad(<?= (int)$row['id'] ?>)" class="btn btn-link p-0 text-decoration-none text-secondary d-flex align-items-center gap-1" style="font-size: 11px;">
                                                <i class="bi bi-clock-history text-primary"></i>
                                                Ver Historial
                                            </button>
                                        </div>
                                    </td>

                                    <td class="p-3 text-end text-nowrap">
                                        <div class="font-monospace fw-bold <?= $row['monto_definitivo'] ? 'text-success' : 'text-dark' ?>" style="font-size: 13px;">
                                            <?= money($row['monto_definitivo'] ?? $row['monto_estimado']) ?>
                                        </div>
                                        <div class="text-muted" style="font-size: 9px;">
                                            <?= $row['monto_definitivo'] ? 'Gasto Definitivo' : 'Estimado' ?>
                                        </div>
                                    </td>

                                    <td class="p-3 text-center text-nowrap">
                                        <a href="adquisiciones.php?view=gestionar&id=<?= $row['id'] ?>" class="btn <?= ($vista === 'pendientes' || $vista === 'lista') ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm px-3 fw-bold shadow-sm d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-cart3"></i>
                                            <?= ($vista === 'pendientes' || $vista === 'lista') ? 'Gestionar' : 'Ver Detalle' ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="p-5 text-center text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        <p class="mb-0 fw-semibold">No se encontraron solicitudes registradas en esta bandeja de adquisiciones.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- VISTA: GESTIÓN DE EXPEDIENTE -->
        <?php if($vista === 'gestionar' && isset($exp)): ?>
            <div class="row align-items-center mb-4 g-3">
                <div class="col-12 col-md">
                    <span class="badge bg-primary text-uppercase tracking-wider mb-1.5" style="font-size: 9px; letter-spacing: 0.5px;"><?= htmlspecialchars($exp['tipo_compra_nom']) ?></span>
                    <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        Expediente: <span class="font-monospace text-primary">#<?= htmlspecialchars($exp['codigo_interno']) ?></span>
                        <button type="button" onclick="verTrazabilidad(<?= (int)$exp['id'] ?>)" class="btn btn-outline-primary btn-sm px-2.5 py-1 fw-bold shadow-sm d-inline-flex align-items-center gap-1.5" style="font-size: 11px;">
                            <i class="bi bi-clock-history"></i> Ver Historial
                        </button>
                    </h1>
                </div>
                <div class="col-12 col-md-auto text-start text-md-end">
                    <a href="adquisiciones.php" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                        <i class="bi bi-arrow-left me-1"></i> Volver a la Bandeja
                    </a>
                </div>
            </div>

            <div class="row g-4">
                
                <!-- COLUMNA IZQUIERDA: CONTEXTO Y CARPETA DIGITAL -->
                <div class="col-lg-4 space-y-4">
                    
                    <!-- RESUMEN CONTEXTO -->
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
                                        <span class="text-muted fw-bold d-block text-uppercase" style="font-size: 9px;">Unidad:</span>
                                        <span class="text-secondary fw-semibold"><?= htmlspecialchars($exp['unidad']) ?></span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted fw-bold d-block text-uppercase" style="font-size: 9px;">Solicitante:</span>
                                        <span class="text-secondary fw-semibold"><?= htmlspecialchars($exp['solicitante']) ?></span>
                                    </div>
                                </div>
                                
                                <div>
                                    <span class="text-muted fw-bold d-block text-uppercase" style="font-size: 9px;">Centro de Costo:</span>
                                    <span class="badge bg-primary-subtle text-primary fw-bold text-wrap text-start mt-1 px-2.5 py-1.5 fs-6" style="border: 1px solid rgba(13,110,253,0.1);"><?= htmlspecialchars($exp['centro_costo']) ?></span>
                                </div>

                                <div class="d-flex flex-column gap-2 pt-2 border-top">
                                    <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded-3 border">
                                        <span class="text-muted fw-bold text-uppercase" style="font-size: 9px;">Monto Estimado Inicial:</span> 
                                        <span class="fw-bold text-dark"><?= money($exp['monto_estimado']) ?></span>
                                    </div>
                                    
                                    <?php if($exp['monto_definitivo']): ?>
                                        <div class="d-flex justify-content-between align-items-center bg-success-subtle text-success-emphasis p-2 rounded-3 border border-success-subtle">
                                            <span class="fw-bold text-uppercase" style="font-size: 9px;">Monto Adjudicado Final:</span> 
                                            <span class="fw-black font-monospace"><?= money($exp['monto_definitivo']) ?></span>
                                        </div>
                                    <?php endif; ?>

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
                                    
                                    <?php if($exp['id_compra_agil']): ?>
                                        <div class="d-flex justify-content-between align-items-center bg-info-subtle text-info-emphasis p-2 rounded-3 border border-info-subtle">
                                            <span class="fw-bold text-uppercase" style="font-size: 9px;">ID Compra Ágil (MP):</span> 
                                            <span class="font-monospace fw-bold"><?= htmlspecialchars($exp['id_compra_agil']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($exp['decreto_alcaldicio_numero'])): ?>
                                        <div class="d-flex justify-content-between align-items-center bg-secondary text-white p-2 rounded-3">
                                            <span class="fw-bold text-uppercase" style="font-size: 9px;">N° Decreto Alcaldicio:</span> 
                                            <span class="font-monospace fw-bold"><?= htmlspecialchars($exp['decreto_alcaldicio_numero']) ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if(!empty($exp['orden_compra_numero']) || !empty($exp['conv_marco_oc'])): ?>
                                        <div class="d-flex justify-content-between align-items-center bg-dark text-white p-2 rounded-3">
                                            <span class="fw-bold text-uppercase" style="font-size: 9px;"><?= ($exp['tipo_compra_cod'] === 'CONVENIO_MARCO') ? 'CONV. MARCO O°C:' : 'N° Orden de Compra:' ?></span> 
                                            <span class="font-monospace fw-bold"><?= htmlspecialchars($exp['conv_marco_oc'] ?? $exp['orden_compra_numero']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="pt-2 border-t mt-2">
                                    <span class="text-muted fw-bold d-block text-uppercase mb-1" style="font-size: 9px;">Justificación / Requerimiento:</span>
                                    <div class="bg-light p-2.5 rounded border small text-secondary leading-relaxed" style="max-height: 180px; overflow-y: auto; font-size: 11px;">
                                        <?= nl2br(htmlspecialchars($exp['motivo_compra'])) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CARPETA DIGITAL -->
                    <div class="card shadow-sm border-light">
                        <div class="card-header bg-white py-3">
                            <h6 class="fw-bold mb-0 text-dark uppercase tracking-wider" style="font-size: 11px; letter-spacing: 0.5px;">Carpeta Digital (<?= count($docs) ?>)</h6>
                        </div>
                        <div class="card-body p-3">
                            <?php if(empty($docs)): ?>
                                <div class="text-center py-4 bg-light border border-dashed rounded-3">
                                    <p class="text-muted small mb-0 italic">No hay documentos en el expediente.</p>
                                </div>
                            <?php else: ?>
                                <div class="d-flex flex-column gap-2">
                                    <?php foreach($docs as $doc): ?>
                                        <a href="<?= htmlspecialchars($doc['ruta_archivo']) ?>" target="_blank" class="d-flex align-items-center justify-content-between p-2.5 bg-light border rounded-3 text-decoration-none hover-bg-gray transition">
                                            <div class="d-flex align-items-center gap-2 min-w-0 flex-grow-1">
                                                <i class="bi bi-file-earmark-text text-primary fs-5 shrink-0"></i>
                                                <div class="text-truncate">
                                                    <p class="mb-0 text-truncate small fw-bold <?= $doc['tipo_doc'] === 'OPI_ANULADA' ? 'text-danger text-decoration-line-through' : 'text-dark' ?>"><?= htmlspecialchars($doc['nombre_original']) ?></p>
                                                    <p class="mb-0 text-uppercase text-muted" style="font-size: 9px;"><?= str_replace('_', ' ', $doc['tipo_doc']) ?> - <?= date('d/m H:i', strtotime($doc['fecha_subida'])) ?></p>
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

                <!-- COLUMNA DERECHA: TABLA Y TAREA ACTUAL -->
                <div class="col-lg-8 space-y-4">
                    
                    <!-- TABLA DETALLE PRODUCTOS -->
                    <div class="card shadow-sm border-light">
                        <div class="card-header bg-white py-3">
                            <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                                <i class="bi bi-cart3 text-secondary"></i>
                                Detalle de Productos / Servicios
                            </h6>
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
                                                <span class="text-muted d-block text-uppercase fw-bold" style="font-size: 8px;">Monto Línea</span>
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

                    <?php if (!empty($es_accionable)): ?>
                        <!-- CAJA DE TAREA ACTUAL -->
                        <div class="card shadow-lg border-primary">
                            <div class="card-header bg-primary text-white py-3">
                                <span class="badge bg-white text-primary text-uppercase tracking-wider mb-1" style="font-size: 8px;">Su Tarea Actual</span>
                                <h5 class="fw-bold mb-0">Gestión del Proceso de Compra</h5>
                            </div>
                            
                            <?php $estado = $exp['estado_actual']; ?>

                            <!-- FASE 1: INGRESO AL PORTAL DE REFERENCIA -->
                            <?php if ($estado === 'RECEPCIONADO_POR_ADQUISICIONES'): ?>
                                <div class="card-body p-4">
                                    <h6 class="fw-bold text-dark mb-1">Ingreso al Portal (Mercado Público)</h6>
                                    <p class="text-secondary small mb-4">Cree el requerimiento de compra en el portal oficial de Mercado Público. Luego registre aquí el ID identificador generado.</p>
                                    
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="accion" value="ingresar_id_portal">
                                        <input type="hidden" name="expediente_id" value="<?= $exp['id'] ?>">
                                        
                                        <div class="mb-4">
                                            <?php if ($exp['tipo_compra_cod'] === 'COMPRA_AGIL'): ?>
                                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">ID Compra Ágil (Mercado Público) <span class="text-danger">*</span></label>
                                                <input type="text" name="id_compra_agil" required class="form-control">
                                            <?php elseif ($exp['tipo_compra_cod'] === 'LICITACION'): ?>
                                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">ID de Licitación (Mercado Público) <span class="text-danger">*</span></label>
                                                <input type="text" name="id_licitacion" required class="form-control">
                                            <?php else: ?>
                                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">ID de Referencia en Portal <span class="text-danger">*</span></label>
                                                <input type="text" name="id_referencia" required class="form-control">
                                            <?php endif; ?>
                                        </div>

                                        <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold shadow d-flex align-items-center justify-content-center gap-2">
                                            <span>Guardar ID y Avanzar a Búsqueda de Ofertas</span>
                                            <i class="bi bi-arrow-right-short fs-4"></i>
                                        </button>
                                    </form>
                                </div>

                            <!-- FASE 2: SUBIDA DE OFERTAS / COTIZACIONES -->
                            <?php elseif ($estado === 'EN_COTIZACION_ADQ' || $estado === 'EN_PUBLICACION_MERCADO'): ?>
                                <div class="card-body p-4">
                                    <h6 class="fw-bold text-dark mb-1"><?= $estado === 'EN_COTIZACION_ADQ' ? 'Recepción de Cotizaciones / Ofertas' : 'Cierre y Ofertas de Licitación' ?></h6>
                                    <p class="text-secondary small mb-4">Descargue las ofertas o cotizaciones recibidas en Mercado Público y suba un archivo comprimido o PDF consolidado para la evaluación del solicitante.</p>
                                    
                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="accion" value="<?= $estado === 'EN_COTIZACION_ADQ' ? 'subir_cotizaciones' : 'publicar_licitacion' ?>">
                                        <input type="hidden" name="expediente_id" value="<?= $exp['id'] ?>">
                                        
                                        <div class="mb-4">
                                            <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Archivo de Ofertas/Bases (PDF, ZIP, RAR) <span class="text-danger">*</span></label>
                                            <input type="file" name="<?= $estado === 'EN_COTIZACION_ADQ' ? 'archivo_cotizacion' : 'archivo_bases' ?>" accept=".pdf,.zip,.rar,application/pdf,application/zip,application/x-rar-compressed" required class="form-control form-control-sm">
                                        </div>
                                                <button type="submit" class="btn btn-primary w-100 py-2.5 fw-semibold shadow-sm d-flex align-items-center justify-content-center gap-2">
                                            <span>Enviar Ofertas a Evaluación Técnica</span>
                                            <i class="bi bi-send-fill fs-6"></i>
                                        </button>
                                    </form>
                                </div>

                            <!-- FASE 3: INGRESO DE ORDEN DE COMPRA Y PROVEEDOR -->
                            <?php elseif (in_array($estado, ['EN_EMISION_OC', 'EN_ADJUDICACION', 'EN_GESTION_ADQUISICIONES'])): ?>
                                <div class="card-body p-4">
                                    <h6 class="fw-bold text-dark mb-1">Emisión de Orden de Compra (OC)</h6>
                                    <p class="text-secondary small mb-4">Genere la Orden de Compra en el portal de Mercado Público. A continuación registre el código OC y el documento PDF para el expediente.</p>
                                    
                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="accion" value="emitir_oc">
                                        <input type="hidden" name="expediente_id" value="<?= $exp['id'] ?>">

                                        <div class="bg-light p-3 rounded-3 border mb-4">
                                            <p class="text-uppercase text-muted fw-bold mb-2.5" style="font-size: 9px; letter-spacing: 0.5px;">Resumen / Adjudicación</p>
                                            
                                            <?php if ($exp['proveedor_adjudicado_id']): ?>
                                                <div class="small">
                                                    <span class="text-muted fw-bold d-block text-uppercase mb-0.5" style="font-size: 8px;">Proveedor Adjudicado Confirmado</span>
                                                    <span class="fw-bold text-dark"><?= htmlspecialchars($exp['proveedor_rut']) ?> - <?= htmlspecialchars($exp['proveedor_nombre']) ?></span>
                                                </div>
                                            <?php else: ?>
                                                <div class="row g-3">
                                                    <div class="col-md-7">
                                                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Seleccionar Proveedor Adjudicado</label>
                                                        <div class="input-group">
                                                            <select name="proveedor_id" class="form-select form-select-sm text-sm" required>
                                                                <option value="">-- Seleccione --</option>
                                                                <?php foreach($proveedores as $p) echo "<option value='{$p['id']}'>{$p['rut']} - {$p['razon_social']}</option>"; ?>
                                                            </select>
                                                            <button type="button" class="btn btn-outline-primary btn-sm px-2.5" data-bs-toggle="modal" data-bs-target="#modalProv" title="Agregar Nuevo Proveedor">
                                                                <i class="bi bi-plus-lg"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Monto Final C/IVA ($)</label>
                                                        <input type="text" name="monto_definitivo" id="inpMonto" required class="form-control form-control-sm font-monospace fw-bold text-dark text-end" value="<?= number_format($exp['monto_estimado'], 0, '', '') ?>">
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <div class="mb-3">
                                             <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;"><?= ($exp['tipo_compra_cod'] === 'CONVENIO_MARCO') ? 'CONV. MARCO O°C:' : 'Número de Orden de Compra' ?> <span class="text-danger">*</span></label>
                                             <input type="text" name="<?= ($exp['tipo_compra_cod'] === 'CONVENIO_MARCO') ? 'conv_marco_oc' : 'orden_compra_numero' ?>" required class="form-control" placeholder="<?= ($exp['tipo_compra_cod'] === 'CONVENIO_MARCO') ? 'Ej: 1234-56-CM24' : '' ?>" value="<?= htmlspecialchars($exp['conv_marco_oc'] ?? $exp['orden_compra_numero'] ?? '') ?>">
                                         </div>

                                         <div class="mb-3">
                                             <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Número de Decreto Alcaldicio <span class="text-danger">*</span></label>
                                             <input type="text" name="decreto_alcaldicio_numero" required class="form-control" placeholder="Ej: DA887" value="<?= htmlspecialchars($exp['decreto_alcaldicio_numero'] ?? '') ?>">
                                         </div>

                                         <div class="mb-3">
                                             <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Subir Decreto Alcaldicio (PDF) <span class="text-danger">*</span></label>
                                             <input type="file" name="archivo_decreto" accept="application/pdf" required class="form-control form-control-sm">
                                         </div>

                                         <div class="mb-4">
                                             <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Subir Orden de Compra Firmada (PDF) <span class="text-danger">*</span></label>
                                             <input type="file" name="archivo_oc" accept="application/pdf" required class="form-control form-control-sm">
                                         </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary w-100 py-2.5 fw-semibold shadow-sm d-flex align-items-center justify-content-center gap-2">
                                            <span>Registrar OC y Esperar Aceptación</span>
                                            <i class="bi bi-cloud-arrow-up-fill fs-6"></i>
                                        </button>
                                    </form>
                                </div>

                            <!-- FASE 4: ESPERA ACEPTACIÓN DE PROVEEDOR -->
                            <?php elseif ($estado === 'ESPERANDO_ACEPTACION_OC'): ?>
                                <div class="card-body p-4">
                                    <div class="alert alert-warning border border-warning-subtle p-3 rounded-3 mb-4">
                                        <div class="d-flex align-items-start gap-2">
                                            <i class="bi bi-clock-history fs-5 shrink-0 text-warning"></i>
                                            <div>
                                                <strong class="d-block text-dark small">Esperando Respuesta del Proveedor</strong>
                                                <p class="mb-0 small text-secondary">La Orden de Compra N° <strong><?= htmlspecialchars($exp['conv_marco_oc'] ?? $exp['orden_compra_numero']) ?></strong> fue emitida en Mercado Público. Confirme si fue aceptada o rechazada.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <form method="POST">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                                <input type="hidden" name="accion" value="oc_aceptada">
                                                <input type="hidden" name="expediente_id" value="<?= $exp['id'] ?>">
                                                <button type="submit" class="btn btn-primary py-2.5 w-100 fw-semibold shadow-sm d-flex align-items-center justify-content-center gap-1.5">
                                                    <i class="bi bi-check-circle-fill"></i>
                                                    <span>OC Aceptada en Portal</span>
                                                </button>
                                            </form>
                                        </div>

                                        <div class="col-md-6">
                                            <form method="POST" onsubmit="return confirm('¿Confirma que la OC fue rechazada en portal?')">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                                <input type="hidden" name="accion" value="oc_rechazada">
                                                <input type="hidden" name="expediente_id" value="<?= $exp['id'] ?>">
                                                <input type="text" name="motivo_rechazo_proveedor" required class="form-control form-control-sm text-center mb-2" placeholder="Motivo del rechazo...">
                                                <button type="submit" class="btn btn-outline-danger py-2 w-100 fw-semibold shadow-sm d-flex align-items-center justify-content-center gap-1.5">
                                                    <i class="bi bi-x-circle"></i>
                                                    <span>Rechazar Compra</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            <!-- FASE 5: GENÉRICA -->
                            <?php else: ?>
                                <div class="card-body p-4 text-center">
                                    <p class="text-muted small mb-0">No hay tareas pendientes asignadas a su perfil para este estado (<?= htmlspecialchars($estado) ?>).</p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- SECCIÓN PELIGRO: ANULACIÓN DE COMPRA -->
                            <?php if (!empty($exp['folio_opi'])): ?>
                                <div class="card-footer bg-light border-top p-4">
                                    <h6 class="text-dark fw-bold mb-1 d-flex align-items-center gap-2">
                                        <i class="bi bi-exclamation-triangle text-danger"></i>
                                        Anulación Definitiva (Caída de Compra)
                                    </h6>
                                    <p class="text-secondary small mb-3">Si la licitación quedó desierta o el proveedor desistió definitivamente, anule el requerimiento. <strong>El número de OPI quedará quemado y marcado como anulado en auditoría.</strong></p>
                                    
                                    <form method="POST" onsubmit="return confirm('ATENCIÓN: Esto ANULARÁ definitivamente la OPI. ¿Está completamente seguro?')">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="accion" value="anular_opi_definitiva">
                                        <input type="hidden" name="expediente_id" value="<?= $exp['id'] ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold text-secondary small text-uppercase" style="font-size: 10px;">Motivo de la anulación definitiva</label>
                                            <input type="text" name="motivo_anulacion" required class="form-control form-control-sm bg-white">
                                        </div>
                                        
                                        <button type="submit" class="btn btn-outline-danger w-100 py-2 fw-semibold shadow-sm">
                                            Anular Trámite Definitivamente
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- COMPROBACIÓN DE ANTECEDENTES Y DEVOLUCIÓN -->
                        <div class="card shadow-sm border-light">
                            <div class="card-header bg-white py-3">
                                <h6 class="fw-bold mb-0 text-dark">¿Problemas con los antecedentes?</h6>
                            </div>
                            <div class="card-body p-3">
                                <p class="text-muted small mb-3">Si falta documentación o las especificaciones del ítem son erróneas, devuelva el requerimiento al usuario emisor para que lo corrija.</p>
                                <form method="POST" onsubmit="return confirm('¿Devolver al usuario creador?')">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="accion" value="devolver">
                                    <input type="hidden" name="expediente_id" value="<?= $exp['id'] ?>">
                                    <div class="input-group">
                                        <input type="text" name="motivo" class="form-control form-control-sm bg-white" required>
                                        <button type="submit" class="btn btn-outline-secondary btn-sm px-4 fw-bold">Devolver</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- BANNER DE GESTIÓN COMPLETADA -->
                        <div class="card shadow-sm border-light">
                            <div class="card-body p-4 text-center">
                                <div class="p-3 bg-success-subtle text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                                    <i class="bi bi-check-lg fs-3"></i>
                                </div>
                                <h5 class="fw-bold text-dark mb-1">Gestión Completada</h5>
                                <p class="text-secondary small mb-4">El expediente ha sido procesado por su departamento en esta fase.</p>
                                
                                <div class="bg-light rounded-3 p-3 border d-inline-block text-start mx-auto" style="min-width: 280px;">
                                    <span class="text-muted fw-bold d-block text-uppercase mb-1" style="font-size: 8px;">Estado Actual del Expediente</span>
                                    <span class="fw-bold text-dark fs-6 d-block"><?= htmlspecialchars($exp['estado_nombre'] ?? $exp['estado_actual']) ?></span>
                                    <span class="badge bg-primary-subtle text-primary-emphasis mt-2 px-2 py-1 fs-6" style="font-size: 9px; font-weight: bold;">
                                        Cargo Responsable: <?= htmlspecialchars($exp['rol_responsable'] ?? 'Otro Departamento') ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- MODAL: REGISTRAR NUEVO PROVEEDOR -->
            <div class="modal fade" id="modalProv" tabindex="-1" aria-labelledby="modalProvLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content shadow-2xl border-light">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title fw-bold text-dark" id="modalProvLabel">Registrar Nuevo Proveedor</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                            <div class="modal-body p-4">
                                <input type="hidden" name="accion" value="crear_proveedor">
                                <input type="hidden" name="return_id" value="<?= $exp['id'] ?>">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">RUT del Proveedor <span class="text-danger">*</span></label>
                                        <input name="rut" required class="form-control font-monospace">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Razón Social / Nombre <span class="text-danger">*</span></label>
                                        <input name="razon_social" required class="form-control bg-white">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Giro Comercial</label>
                                        <input name="giro" class="form-control bg-white">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Teléfono de Contacto</label>
                                        <input name="telefono" class="form-control bg-white">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Dirección Comercial</label>
                                        <input name="direccion" class="form-control bg-white">
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary btn-sm fw-bold px-4">Guardar Proveedor</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- EVENT LISTENER COMPACTO DE FORMATEO -->
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const inpMonto = document.getElementById('inpMonto');
                    if(inpMonto) {
                        const formatNumber = (num) => num.replace(/\D/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                        inpMonto.value = formatNumber(inpMonto.value);
                        inpMonto.addEventListener('input', function(e) { this.value = formatNumber(this.value); });
                    }
                });
            </script>
        <?php endif; ?>

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

    <?php include __DIR__ . '/modal_trazabilidad.php'; ?>

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
                        <td class="p-3 text-center fw-bold text-dark">${cant} <span class="text-muted d-block" style="font-size: 10px;">${escapeHTML(item.unidad_medida)}</span></td>
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