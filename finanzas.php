<?php 
// finanzas.php - Vista del Módulo de Finanzas (V1.0)
require_once __DIR__ . '/finanzas_controller.php'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php 
    $titulo_pagina = "Firma de CDP - Finanzas";
    include __DIR__ . '/head.php'; 
    ?>
</head>
<body class="bg-slate-50 text-slate-800 font-sans pb-20">

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

        <!-- VISTAS: BANDEJA DE LISTADOS HOMOLOGADA -->
        <?php if($vista !== 'revisar'): ?>
            
            <!-- CABECERA PRINCIPAL -->
            <div class="row align-items-center mb-4 g-3">
                <div class="col-12 col-md">
                    <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-check text-primary"></i>
                        Firma de CDP - Finanzas
                    </h1>
                    <p class="text-muted small mb-0">Dirección de Administración y Finanzas (DAF). Adjuntar certificados SMC.</p>
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
                <a href="finanzas.php?view=pendientes" class="px-4 py-2.5 text-decoration-none fw-bold small border-bottom border-2 <?= $vista === 'pendientes' ? 'border-primary text-primary bg-white rounded-top' : 'border-transparent text-secondary hover-text-dark' ?> text-nowrap d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history"></i>
                    Pendientes de Certificado
                    <span class="badge rounded-pill <?= $vista === 'pendientes' ? 'bg-primary text-white' : 'bg-secondary-subtle text-secondary-emphasis' ?>"><?= $count_pendientes ?></span>
                </a>
                <a href="finanzas.php?view=procesados" class="px-4 py-2.5 text-decoration-none fw-bold small border-bottom border-2 <?= $vista === 'procesados' ? 'border-primary text-primary bg-white rounded-top' : 'border-transparent text-secondary hover-text-dark' ?> text-nowrap d-flex align-items-center gap-2">
                    <i class="bi bi-check2-square"></i>
                    Procesados por Mí
                    <span class="badge rounded-pill <?= $vista === 'procesados' ? 'bg-primary text-white' : 'bg-secondary-subtle text-secondary-emphasis' ?>"><?= $count_procesados ?></span>
                </a>
                <a href="finanzas.php?view=todas" class="px-4 py-2.5 text-decoration-none fw-bold small border-bottom border-2 <?= $vista === 'todas' ? 'border-primary text-primary bg-white rounded-top' : 'border-transparent text-secondary hover-text-dark' ?> text-nowrap d-flex align-items-center gap-2">
                    <i class="bi bi-diagram-3"></i>
                    Todas las Solicitudes
                    <span class="badge rounded-pill <?= $vista === 'todas' ? 'bg-primary text-white' : 'bg-secondary-subtle text-secondary-emphasis' ?>"><?= $count_todas ?></span>
                </a>
            </div>

            <!-- PANEL DE FILTROS -->
            <div id="filtroPanel" class="card shadow-sm mb-4 <?= ($f_q || $f_tipo || $f_estado || $f_desde || $f_hasta) ? '' : 'd-none' ?>">
                <div class="card-body p-3">
                    <form method="GET" action="finanzas.php">
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
                                <a href="finanzas.php?view=<?= htmlspecialchars($vista) ?>" class="btn btn-light btn-sm w-100 fw-bold border">Limpiar</a>
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
                                        <a href="finanzas.php?view=revisar&id=<?= $row['id'] ?>" class="btn <?= $vista === 'pendientes' ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm px-3 fw-bold shadow-sm d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-file-earmark-check"></i>
                                            <?= $vista === 'pendientes' ? 'Gestionar' : 'Ver Detalle' ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="p-5 text-center text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        <p class="mb-0 fw-semibold">No se encontraron solicitudes registradas en esta bandeja de finanzas.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- VISTA: REVISIÓN DE EXPEDIENTE -->
        <?php if($vista === 'revisar' && isset($expediente)): ?>
            <div class="row align-items-center mb-4 g-3">
                <div class="col-12 col-md">
                    <span class="badge bg-primary text-uppercase tracking-wider mb-1.5" style="font-size: 9px; letter-spacing: 0.5px;">Área Finanzas: Carga de CDP SMC</span>
                    <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        Expediente: <span class="font-monospace text-primary">#<?= htmlspecialchars($expediente['codigo_interno']) ?></span>
                        <button type="button" onclick="verTrazabilidad(<?= (int)$expediente['id'] ?>)" class="btn btn-outline-primary btn-sm px-2.5 py-1 fw-bold shadow-sm d-inline-flex align-items-center gap-1.5" style="font-size: 11px;">
                            <i class="bi bi-clock-history"></i> Ver Historial
                        </button>
                    </h1>
                </div>
                <div class="col-12 col-md-auto text-start text-md-end">
                    <a href="finanzas.php" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                        <i class="bi bi-arrow-left me-1"></i> Volver a la Bandeja
                    </a>
                </div>
            </div>

            <div class="row g-4">
                
                <!-- COLUMNA IZQUIERDA: RESUMEN Y ARCHIVOS -->
                <div class="col-lg-4 space-y-4">
                    
                    <div class="card shadow-sm border-light">
                        <div class="card-header bg-white py-3">
                            <h6 class="fw-bold mb-0 text-dark uppercase tracking-wider" style="font-size: 11px; letter-spacing: 0.5px;">Contexto de la Solicitud</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-flex flex-column gap-3 small">
                                <?php if($expediente['titulo_compra']): ?>
                                    <div>
                                        <span class="text-muted fw-bold d-block text-uppercase" style="font-size: 9px;">Título Compra:</span>
                                        <span class="fw-bold text-dark fs-6"><?= htmlspecialchars($expediente['titulo_compra']) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="row g-3">
                                    <div class="col-6">
                                        <span class="text-muted fw-bold d-block text-uppercase" style="font-size: 9px;">Unidad:</span>
                                        <span class="text-secondary fw-semibold"><?= htmlspecialchars($expediente['unidad']) ?></span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted fw-bold d-block text-uppercase" style="font-size: 9px;">Tipo de Compra:</span>
                                        <span class="text-secondary fw-semibold"><?= htmlspecialchars($expediente['tipo_compra_nom']) ?></span>
                                    </div>
                                </div>

                                <div>
                                    <span class="text-muted fw-bold d-block text-uppercase" style="font-size: 9px;">Centro de Costo:</span>
                                    <span class="badge bg-indigo-subtle text-indigo fw-bold text-wrap text-start mt-1 px-2.5 py-1.5 fs-6">[ID: <?= $expediente['centro_costo_id'] ?>] <?= htmlspecialchars($expediente['centro_costo']) ?></span>
                                </div>

                                <div class="pt-2 border-t mt-2">
                                    <span class="text-muted fw-bold d-block text-uppercase mb-1" style="font-size: 9px;">Justificación del Gasto:</span>
                                    <div class="bg-light p-2.5 rounded border small text-secondary leading-relaxed" style="max-height: 180px; overflow-y: auto; font-size: 11px;">
                                        <?= nl2br(htmlspecialchars($expediente['motivo_compra'])) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ARCHIVOS ADJUNTOS -->
                    <div class="card shadow-sm border-light">
                        <div class="card-header bg-white py-3">
                            <h6 class="fw-bold mb-0 text-dark uppercase tracking-wider" style="font-size: 11px; letter-spacing: 0.5px;">Archivos Adjuntos (<?= count($docs) ?>)</h6>
                        </div>
                        <div class="card-body p-3">
                            <?php if(empty($docs)): ?>
                                <div class="text-center py-4 bg-light border border-dashed rounded-3">
                                    <p class="text-muted small mb-0 italic">No hay archivos adjuntos cargados.</p>
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

                <!-- COLUMNA DERECHA: TABLA DE CUENTAS E IMPUTACIÓN -->
                <div class="col-lg-8 space-y-4">
                    
                    <div class="card shadow-sm border-light overflow-hidden">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                                <i class="bi bi-diagram-3 text-secondary"></i>
                                Imputación Contable y Montos
                            </h6>
                            <div class="text-end">
                                <span class="text-muted text-uppercase fw-bold" style="font-size: 9px;">Total Requerido</span>
                                <div class="h5 fw-black text-primary font-monospace mb-0"><?= money($expediente['monto_estimado']) ?></div>
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
                                                    <?php if($it['ag_codigo']): ?>
                                                        <span class="badge bg-secondary-subtle text-secondary-emphasis" style="font-size: 8px;">AG: <?= $it['ag_codigo'] ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-muted text-uppercase mt-0.5" style="font-size: 10px; font-weight: 600;" title="<?= $it['cuenta_nombre'] ?>">
                                                    <?= htmlspecialchars($it['cuenta_nombre']) ?>
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
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- ACCIONES DE RESOLUCIÓN SOBRIAS -->
                    <div class="card shadow-sm border-light">
                        <div class="card-header bg-white border-bottom py-3">
                            <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-check text-primary"></i>
                                Firma de CDP - Finanzas
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            
                            <?php if($es_accionable): ?>
                                <p class="text-secondary small mb-4">Genere la plantilla de apoyo si es necesario, firme el certificado emitido por SMC y adjúntelo.</p>
                                
                                <?php 
                                $transiciones = obtener_transiciones_disponibles($pdo, $expediente['id']); 
                                ?>
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="expediente_id" value="<?= $expediente['id'] ?>">
                                    
                                    <?php
                                    $doc_borrador = null;
                                    $doc_situacion = null;
                                    foreach ($docs as $d) {
                                        if ($d['tipo_doc'] === 'CDP_BORRADOR') $doc_borrador = $d;
                                        if ($d['tipo_doc'] === 'SITUACION_PRESUPUESTARIA') $doc_situacion = $d;
                                    }
                                    ?>
                                    <div class="card border border-light-subtle bg-light shadow-sm mb-4">
                                        <div class="card-body p-3">
                                            <div class="mb-3">
                                                <h6 class="text-dark fw-bold mb-2 d-flex align-items-center gap-1.5" style="font-size: 11px;">
                                                    <i class="bi bi-download text-primary"></i>
                                                    Paso 1: Descargar Documentos Adjuntos del Analista
                                                </h6>
                                                <div class="d-flex flex-column gap-2 bg-white p-2.5 rounded border">
                                                    <?php if($doc_borrador): ?>
                                                        <a href="<?= htmlspecialchars($doc_borrador['ruta_archivo']) ?>" download target="_blank" class="btn btn-outline-primary btn-sm text-start fw-semibold d-flex align-items-center justify-content-between px-3 py-2">
                                                            <span><i class="bi bi-file-earmark-pdf-fill me-1.5"></i> Descargar Borrador de CDP (Sin Firmar)</span>
                                                            <i class="bi bi-cloud-arrow-down-fill fs-5"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <div class="alert alert-warning py-1.5 px-2.5 mb-0 small"><i class="bi bi-exclamation-triangle me-1"></i> No se encontró Borrador de CDP adjunto.</div>
                                                    <?php endif; ?>

                                                    <?php if($doc_situacion): ?>
                                                        <a href="<?= htmlspecialchars($doc_situacion['ruta_archivo']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm text-start fw-semibold d-flex align-items-center justify-content-between px-3 py-2">
                                                            <span><i class="bi bi-file-earmark-text-fill me-1.5"></i> Ver Situación Presupuestaria de Gastos</span>
                                                            <i class="bi bi-eye-fill fs-5"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <div class="alert alert-warning py-1.5 px-2.5 mb-0 small"><i class="bi bi-exclamation-triangle me-1"></i> No se encontró Situación Presupuestaria de Gastos.</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="text-end mb-3">
                                                <a href="cdp.php?id=<?= $expediente['id'] ?>" target="_blank" class="text-xs fw-semibold text-primary text-decoration-none" style="font-size: 11px;">
                                                    <i class="bi bi-printer-fill"></i> Ver Plantilla CDP Dinámica (Nombre/Cargo Logueado)
                                                </a>
                                            </div>

                                            <div class="border-top pt-3">
                                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Paso 2: Adjuntar Certificado de Disponibilidad SMC Firmado (Obligatorio)</label>
                                                <input type="file" name="archivo_cdp" accept="application/pdf" required class="form-control form-control-sm bg-white">
                                                <p class="text-muted mt-1 mb-0" style="font-size: 9px;">Solo se permiten archivos en formato PDF.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- BOTÓN DE APROBACIÓN PRINCIPAL SOBRIO -->
                                    <?php 
                                    $t_aprobar = null;
                                    foreach ($transiciones as $t) {
                                        if ($t['accion_codigo'] === 'APROBAR') $t_aprobar = $t;
                                    }
                                    if ($t_aprobar):
                                    ?>
                                        <button type="submit" name="transicion_id" value="<?= $t_aprobar['id'] ?>" onclick="return confirm('¿Confirma que ha subido el CDP correcto y firmado?')" class="btn btn-primary py-2.5 w-100 mb-4 shadow-sm d-flex align-items-center justify-content-center gap-2 fw-semibold">
                                            <i class="bi bi-check-circle-fill"></i>
                                            <?= htmlspecialchars($t_aprobar['accion_label']) ?>
                                        </button>
                                    <?php endif; ?>

                                    <!-- REPAROS Y OBS -->
                                    <div class="border-top pt-3.5">
                                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Reparos y Observaciones (Obligatorio para Devolver/Rechazar)</label>
                                        <textarea name="motivo_rechazo" rows="3" class="form-control text-sm mb-3 bg-light"></textarea>
                                        
                                        <div class="row g-2">
                                            <!-- Botones de Devolución -->
                                            <?php foreach ($transiciones as $t): 
                                                if ($t['accion_codigo'] === 'DEVOLVER'):
                                            ?>
                                                <div class="col-sm-6">
                                                    <button type="submit" name="transicion_id" value="<?= $t['id'] ?>" onclick="return confirm('¿Confirma devolver la solicitud?')" class="btn btn-outline-secondary w-100 py-2 fw-semibold shadow-sm d-flex align-items-center justify-content-center gap-1.5">
                                                        <i class="bi bi-arrow-counterclockwise"></i>
                                                        <?= htmlspecialchars($t['accion_label']) ?>
                                                    </button>
                                                </div>
                                            <?php endif; endforeach; ?>

                                            <!-- Botones de Rechazo -->
                                            <?php foreach ($transiciones as $t): 
                                                if ($t['accion_codigo'] === 'RECHAZAR'):
                                            ?>
                                                <div class="col-sm-6">
                                                    <button type="submit" name="transicion_id" value="<?= $t['id'] ?>" onclick="return confirm('¿Confirma rechazar definitivamente la solicitud?')" class="btn btn-outline-danger w-100 py-2 fw-semibold shadow-sm d-flex align-items-center justify-content-center gap-1.5">
                                                        <i class="bi bi-x-circle"></i>
                                                        <?= htmlspecialchars($t['accion_label']) ?>
                                                    </button>
                                                </div>
                                            <?php endif; endforeach; ?>
                                        </div>
                                    </div>
                                </form>
                            
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <div class="p-3 bg-success-subtle text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                                        <i class="bi bi-check-lg fs-3"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark mb-1">CDP Tramitado</h5>
                                    <p class="text-secondary small mb-4">El certificado fue emitido y cargado de forma exitosa.</p>
                                    
                                    <div class="bg-light rounded-3 p-3 border d-inline-block text-start mx-auto" style="min-width: 280px;">
                                        <span class="text-muted fw-bold d-block text-uppercase mb-1" style="font-size: 8px;">Estado Actual del Trámite</span>
                                        <span class="fw-bold text-dark fs-6 d-block"><?= htmlspecialchars($expediente['estado_nombre']) ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>

                </div>
            </div>

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
    </div>
</body>
</html>
