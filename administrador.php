<?php 
// administrador.php - Vista Principal (V5.5 - Rediseño Homologado y Firma Avanzada)
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
<body class="bg-slate-50 text-slate-800 font-sans d-flex flex-column min-vh-100">

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

        <!-- VISTAS: BANDEJA DE LISTADOS HOMOLOGADA -->
        <?php if($vista !== 'revisar'): ?>
            
            <!-- CABECERA PRINCIPAL -->
            <div class="row align-items-center mb-4 g-3">
                <div class="col-12 col-md">
                    <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="bi bi-bank text-primary"></i>
                        Bandeja de Administración Municipal
                    </h1>
                    <p class="text-muted small mb-0">Gestión centralizada de autorizaciones de cotización y firmas de OPIs institucionales.</p>
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
                <a href="administrador.php?tab=cotizaciones" class="px-4 py-2.5 text-decoration-none fw-bold small border-bottom border-2 <?= $tab === 'cotizaciones' ? 'border-primary text-primary bg-white rounded-top' : 'border-transparent text-secondary hover-text-dark' ?> text-nowrap d-flex align-items-center gap-2">
                    <i class="bi bi-card-checklist text-primary"></i>
                    Autorizar Cotizaciones
                    <span class="badge rounded-pill <?= $tab === 'cotizaciones' ? 'bg-primary text-white' : 'bg-secondary-subtle text-secondary-emphasis' ?>"><?= $count_cotizacion ?></span>
                </a>
                <a href="administrador.php?tab=opis" class="px-4 py-2.5 text-decoration-none fw-bold small border-bottom border-2 <?= $tab === 'opis' ? 'border-primary text-primary bg-white rounded-top' : 'border-transparent text-secondary hover-text-dark' ?> text-nowrap d-flex align-items-center gap-2">
                    <i class="bi bi-pen-fill" style="color: #6366f1;"></i>
                    Firmar OPIs Definitivas
                    <span class="badge rounded-pill <?= $tab === 'opis' ? 'bg-primary text-white' : 'bg-secondary-subtle text-secondary-emphasis' ?>"><?= $count_opi ?></span>
                </a>
                <a href="administrador.php?tab=procesados" class="px-4 py-2.5 text-decoration-none fw-bold small border-bottom border-2 <?= $tab === 'procesados' ? 'border-primary text-primary bg-white rounded-top' : 'border-transparent text-secondary hover-text-dark' ?> text-nowrap d-flex align-items-center gap-2">
                    <i class="bi bi-check2-square text-success"></i>
                    Procesados por Mí / Historial
                    <span class="badge rounded-pill <?= $tab === 'procesados' ? 'bg-primary text-white' : 'bg-secondary-subtle text-secondary-emphasis' ?>"><?= $count_procesados ?></span>
                </a>
            </div>

            <!-- PANEL DE FILTROS -->
            <div id="filtroPanel" class="card shadow-sm mb-4 <?= ($f_q || $f_tipo || $f_desde || $f_hasta) ? '' : 'd-none' ?>">
                <div class="card-body p-3">
                    <form method="GET" action="administrador.php">
                        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-sm-6 col-lg-4">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Buscar (Código, Folio, Título o Motivo)</label>
                                <input type="text" name="f_q" value="<?= htmlspecialchars($f_q) ?>" class="form-control form-control-sm" placeholder="Ej: OPI-2026-0001...">
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Tipo de Compra</label>
                                <select name="f_tipo" class="form-select form-select-sm bg-white">
                                    <option value="">Todos los tipos</option>
                                    <?php foreach($tipos_compra_filtro as $t): ?>
                                        <option value="<?= $t['id'] ?>" <?= $f_tipo==$t['id']?'selected':'' ?>><?= htmlspecialchars($t['nombre']) ?></option>
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
                                <a href="administrador.php?tab=<?= htmlspecialchars($tab) ?>" class="btn btn-light btn-sm w-100 fw-bold border">Limpiar</a>
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
                                <th class="p-3 text-nowrap" style="width: 160px;">Clasificación / Proveedor</th>
                                <th class="p-3 text-nowrap" style="width: 180px;">Fase / Estado Actual</th>
                                <th class="p-3 text-end text-nowrap" style="width: 150px;">Monto Total</th>
                                <th class="p-3 text-center text-nowrap" style="width: 170px;">Gestión</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($solicitudes)): ?>
                                <?php foreach($solicitudes as $row): ?>
                                <tr>
                                    <td class="p-3 text-nowrap">
                                        <button type="button" onclick="abrirModalVerItems(<?= htmlspecialchars(json_encode($row['items_detalle']), ENT_QUOTES, 'UTF-8') ?>, '<?= $row['codigo_interno'] ?>')" class="btn btn-link p-0 text-start font-monospace fw-bold text-primary text-decoration-underline mb-1">
                                            <?= htmlspecialchars($row['codigo_interno']) ?>
                                        </button>
                                        <?php if(!empty($row['folio_opi'])): ?>
                                            <div class="badge bg-dark text-white font-monospace d-block mb-1" style="font-size: 10px; width: fit-content;">
                                                <?= htmlspecialchars($row['folio_opi']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="text-muted small" style="font-size: 11px;">
                                            <i class="bi bi-clock me-1"></i>
                                            <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?>
                                        </div>
                                        <span class="badge border mt-2 d-inline-block <?= $row['prioridad_css'] ?>" style="font-size: 10px;">
                                            <?= htmlspecialchars($row['prioridad_nom']) ?>
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
                                            CC: <?= htmlspecialchars($row['centro_costo']) ?>
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

                                    <td class="p-3">
                                        <span class="badge bg-light text-dark border px-2 py-1.5 fw-bold d-block mb-1.5" style="font-size: 11px; width: fit-content;">
                                            <?= htmlspecialchars($row['tipo_compra_nom']) ?>
                                        </span>
                                        <?php if($row['proveedor']): ?>
                                            <div class="text-success font-monospace fw-bold small" style="font-size: 11px;">
                                                <i class="bi bi-building me-1"></i><?= htmlspecialchars($row['proveedor']) ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small italic" style="font-size: 11px;">Sin adjudicar</span>
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
                                        <div class="font-monospace fw-bold text-dark" style="font-size: 13px;">
                                            <?= money($row['monto_definitivo'] ?? $row['monto_estimado']) ?>
                                        </div>
                                    </td>

                                    <td class="p-3 text-center text-nowrap">
                                        <?php if ($tab === 'cotizaciones'): ?>
                                            <a href="administrador.php?view=revisar&id=<?= $row['id'] ?>" class="btn btn-primary btn-sm px-3 fw-bold shadow-sm d-inline-flex align-items-center gap-1">
                                                <i class="bi bi-check2-square"></i>
                                                Revisar y Autorizar
                                            </a>
                                        <?php elseif ($tab === 'opis'): ?>
                                            <a href="administrador.php?view=revisar&id=<?= $row['id'] ?>" class="btn btn-dark btn-sm px-3 fw-bold shadow-sm d-inline-flex align-items-center gap-1" style="background-color: #4f46e5; border-color: #4f46e5;">
                                                <i class="bi bi-pen-fill"></i>
                                                Revisar y Firmar OPI
                                            </a>
                                        <?php else: ?>
                                            <a href="administrador.php?view=revisar&id=<?= $row['id'] ?>" class="btn btn-outline-primary btn-sm px-3 fw-bold shadow-sm d-inline-flex align-items-center gap-1">
                                                <i class="bi bi-eye"></i>
                                                Ver Detalle
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="p-5 text-center text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        <p class="mb-0 fw-semibold">No se encontraron solicitudes registradas en esta bandeja de Administración Municipal.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
                    <a href="administrador.php?tab=<?= $es_etapa_cotizacion ? 'cotizaciones' : 'opis' ?>" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
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

                                <div class="pt-2 border-top mt-2">
                                    <span class="text-muted fw-bold d-block text-uppercase mb-1" style="font-size: 9px;">Justificación Técnica:</span>
                                    <div class="bg-light p-2.5 rounded border small text-secondary leading-relaxed" style="max-height: 180px; overflow-y: auto; font-size: 11px;">
                                        <?= nl2br(htmlspecialchars($exp['motivo_compra'])) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ARCHIVOS DE RESPALDO Y DOCUMENTOS CLAVE -->
                    <div class="card shadow-sm border-light">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark uppercase tracking-wider" style="font-size: 11px; letter-spacing: 0.5px;">Archivos del Expediente (<?= count($docs) ?>)</h6>
                            <?php if(!empty($docs)): ?>
                                <a href="?descargar_zip=<?= $exp['id'] ?>" class="btn btn-outline-primary btn-sm py-1 px-2 fw-bold" style="font-size: 10px;">
                                    <i class="bi bi-download me-1"></i> Bajar ZIP
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-3">
                            <?php if(empty($docs)): ?>
                                <div class="text-center py-4 bg-light border border-dashed rounded-3">
                                    <p class="text-muted small mb-0 italic">No hay documentos cargados en el expediente.</p>
                                </div>
                            <?php else: ?>
                                <div class="d-flex flex-column gap-2">
                                    <?php foreach($docs as $doc): 
                                        $is_cdp = ($doc['tipo_doc'] === 'OPI_CDP_PDF' || $doc['tipo_doc'] === 'CERTIFICADO_PRESUPUESTARIO');
                                        $is_opi = ($doc['tipo_doc'] === 'OPI_FIRMADA_PDF' || $doc['tipo_doc'] === 'OPI_DEFINITIVA');
                                        $is_acta = ($doc['tipo_doc'] === 'ACTA_ADJUDICACION' || $doc['tipo_doc'] === 'CUADRO_COMPARATIVO');
                                    ?>
                                        <a href="<?= htmlspecialchars($doc['ruta_archivo']) ?>" target="_blank" class="d-flex align-items-center justify-content-between p-2.5 <?= $is_cdp ? 'bg-primary-subtle border-primary-subtle' : ($is_opi ? 'bg-indigo-subtle border-indigo-subtle' : ($is_acta ? 'bg-success-subtle border-success-subtle' : 'bg-light')) ?> border rounded-3 text-decoration-none hover-bg-gray transition">
                                            <div class="d-flex align-items-center gap-2 min-w-0 flex-grow-1">
                                                <i class="bi <?= $is_cdp ? 'bi-file-earmark-check-fill text-primary' : ($is_opi ? 'bi-pen-fill text-indigo' : ($is_acta ? 'bi-trophy-fill text-success' : 'bi-file-earmark-text text-secondary')) ?> fs-5 shrink-0"></i>
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
                                Detalle de Productos / Servicios
                            </h6>
                            <div class="text-end">
                                <span class="text-muted text-uppercase fw-bold" style="font-size: 8px;">Monto Total</span>
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
                                                    <span class="font-monospace text-dark fw-bold small" style="font-size: 12px;"><?= $it['cuenta_codigo'] ?: 'Sin imputación' ?></span>
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
                                        Acción Requerida: Firma y Emisión de OPI Definitiva
                                    </h5>
                                </div>
                                <div class="card-body p-4">

                                    <!-- PASO 1: DESCARGAR O REVISAR OPI OFICIAL -->
                                    <div class="d-flex align-items-center justify-content-between p-3 bg-light border rounded-3 mb-4">
                                        <div class="d-flex align-items-center gap-2.5">
                                            <i class="bi bi-file-earmark-pdf-fill fs-3 text-primary"></i>
                                            <div>
                                                <h6 class="fw-bold mb-0 text-dark">Documento OPI Oficial</h6>
                                                <span class="text-muted small">Descargue o visualice el documento oficial con las visaciones previas (1/3 y 2/3)</span>
                                            </div>
                                        </div>
                                        <a href="imprimir_opi.php?id=<?= $exp['id'] ?>&auto_download=1" target="_blank" class="btn btn-outline-primary btn-sm fw-semibold px-3 py-2 shadow-sm d-inline-flex align-items-center gap-1.5">
                                            <i class="bi bi-download"></i> Descargar OPI (PDF)
                                        </a>
                                    </div>

                                    <!-- PASO 2: FIRMA ELECTRÓNICA AVANZADA (FIRMAGOB) -->
                                    <?php if ($t_aprobar): ?>
                                        <div class="bg-white border rounded-3 p-3.5 mb-3">
                                            <h6 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                                <i class="bi bi-shield-lock-fill text-primary"></i>
                                                Firma Electrónica Avanzada Oficial
                                            </h6>
                                            <p class="small text-secondary mb-3" style="font-size: 11.5px;">Estampe la firma digital final sobre la OPI. El documento ya cuenta con las visaciones de Jefatura y Presupuesto, y el CDP de Finanzas.</p>
                                            
                                            <button type="button" onclick="abrirModalFirmaGob({expediente_id: <?= $exp['id'] ?>, transicion_id: <?= $t_aprobar['id'] ?>, etapa: 'ADMIN_MUNICIPAL', codigo_interno: '<?= htmlspecialchars($exp['codigo_interno']) ?>', monto: '<?= $exp['monto_definitivo'] ?: $exp['monto_estimado'] ?>', doc_titulo: 'OPI Oficial Definitiva (Firma 3/3)'})" class="btn btn-primary py-2.5 w-100 fw-bold shadow transition d-flex justify-content-center align-items-center gap-2">
                                                <i class="bi bi-pen-fill"></i>
                                                Firmar y Emitir OPI con FirmaGob (3/3)
                                            </button>
                                        </div>

                                        <!-- CONTINGENCIA: CARGA MANUAL DE OPI FIRMADA -->
                                        <div class="accordion mb-3" id="accordionContingencia">
                                            <div class="accordion-item border rounded-3">
                                                <h2 class="accordion-header">
                                                    <button class="accordion-button collapsed py-2 small fw-bold text-secondary bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseContingencia" aria-expanded="false">
                                                        <i class="bi bi-upload me-2 text-warning"></i> Contingencia: Cargar OPI firmada externamente (DocDigital / Manual)
                                                    </button>
                                                </h2>
                                                <div id="collapseContingencia" class="accordion-collapse collapse" data-bs-parent="#accordionContingencia">
                                                    <div class="accordion-body p-3 bg-white">
                                                        <form method="POST" enctype="multipart/form-data">
                                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                                            <input type="hidden" name="accion" value="cargar_opi_manual">
                                                            <input type="hidden" name="transicion_id" value="<?= $t_aprobar['id'] ?>">
                                                            <input type="hidden" name="expediente_id" value="<?= $exp['id'] ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label small fw-bold text-secondary">Seleccionar PDF firmado</label>
                                                                <input type="file" name="pdf_firmado_manual" accept="application/pdf" class="form-control form-control-sm" required>
                                                                <div class="form-text" style="font-size: 10px;">Suba el PDF firmado digitalmente con DocDigital o certificado token.</div>
                                                            </div>
                                                            
                                                            <button type="submit" onclick="return confirm('¿Confirma la emisión manual de esta OPI con el archivo subido?')" class="btn btn-warning btn-sm w-100 fw-bold">
                                                                <i class="bi bi-cloud-arrow-up-fill me-1"></i> Confirmar y Emitir OPI Manual
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
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
                                                    <button type="submit" formnovalidate onclick="return confirm('¿Confirma la acción de: <?= htmlspecialchars($t_devolver['accion_label']) ?>?')" class="btn btn-outline-secondary btn-sm w-100 fw-semibold">
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
                                                    <button type="submit" formnovalidate onclick="return confirm('¿Confirma la acción de: <?= htmlspecialchars($t_rechazar['accion_label']) ?>?')" class="btn btn-danger btn-sm w-100 fw-bold">
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

<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>