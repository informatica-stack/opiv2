<?php 
// control_presupuestario.php - Vista UI (V6.1 - Contexto Completo y Resalte de Ítems)
require_once __DIR__ . '/control_presupuestario_controller.php'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php 
    $titulo_pagina = "Control Presupuestario";
    include __DIR__ . '/head.php'; 
    ?>
</head>
<body class="bg-slate-50 text-slate-800 font-sans pb-20">

    <?php include __DIR__ . '/nav.php'; ?>

    <!-- Inclusión de Bootstrap 5 JS Bundle después de nav.php -->
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

        <!-- VISTA: HISTORIAL Y LISTADOS -->
        <?php if($vista === 'pendientes' || $vista === 'procesados'): ?>
            <div class="row align-items-center mb-4 g-3">
                <div class="col-12 col-md">
                    <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="bi bi-wallet2 text-primary"></i>
                        VB Presupuestario
                    </h1>
                    <p class="text-muted small mb-0">Gestión de CDP y visación financiera de requerimientos de compra.</p>
                </div>
            </div>

            <!-- TABS DE NAVEGACIÓN -->
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link <?= $vista === 'pendientes' ? 'active fw-bold' : '' ?>" href="?view=pendientes">Pendientes de Visación</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $vista === 'procesados' ? 'active fw-bold' : '' ?>" href="?view=procesados">Historial Procesados</a>
                </li>
            </ul>

            <!-- BUSCADOR -->
            <form method="GET" class="row g-2 mb-4 align-items-center">
                <input type="hidden" name="view" value="<?= $vista ?>">
                <div class="col-12 col-sm-8 col-md-5">
                    <input type="text" name="q" value="<?= htmlspecialchars($q_buscar) ?>" class="form-control form-control-sm bg-white shadow-sm">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-dark btn-sm px-4 fw-bold shadow-sm">Buscar</button>
                </div>
                <?php if($q_buscar): ?>
                    <div class="col-auto">
                        <a href="?view=<?= $vista ?>" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">Limpiar</a>
                    </div>
                <?php endif; ?>
            </form>

            <!-- TABLA DE EXPEDIENTES -->
            <div class="card shadow-sm border-light">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0 text-dark"><?= $vista === 'pendientes' ? 'Requerimientos Pendientes de Aprobación' : 'Requerimientos Procesados' ?></h6>
                </div>
                <div class="table-responsive rounded-bottom">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small" style="font-size: 10px;">
                            <tr>
                                <th class="p-3">Requerimiento</th>
                                <th class="p-3">Fase Presupuestaria</th>
                                <th class="p-3">Solicitante</th>
                                <th class="p-3 text-end" style="width: 180px;">Monto</th>
                                <th class="p-3 text-center" style="width: 120px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <?php 
                            $lista = $vista === 'pendientes' ? $pendientes : $procesados;
                            if(empty($lista)): ?>
                                <tr>
                                    <td colspan="5" class="p-4 text-center text-muted italic">Bandeja al día. No hay tareas registradas.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($lista as $p): ?>
                                <tr>
                                    <td class="p-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-bold text-dark font-monospace"><?= $p['codigo_interno'] ?></span>
                                            <button type="button" onclick="verTrazabilidad(<?= (int)$p['id'] ?>)" class="btn btn-link p-0 border-0 d-flex align-items-center" title="Ver Historial">
                                                <i class="bi bi-clock-history text-primary" style="font-size: 13px;"></i>
                                            </button>
                                        </div>
                                        <div class="text-muted small text-truncate d-block" style="max-width: 250px; font-size: 10px;" title="<?= htmlspecialchars($p['titulo_compra']) ?>">
                                            <?= htmlspecialchars($p['titulo_compra'] ?? $p['tipo_compra_nom']) ?>
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <?php if($p['estado_actual'] === 'EN_VALIDACION_PRESUPUESTARIA'): ?>
                                            <span class="badge bg-primary px-2.5 py-1.5 rounded-pill uppercase" style="font-size: 8px; letter-spacing: 0.5px;">1. VISACIÓN INICIAL (CDP)</span>
                                        <?php elseif($p['estado_actual'] === 'EN_VALIDACION_PRESUPUESTARIA_FINAL'): ?>
                                            <span class="badge bg-purple px-2.5 py-1.5 rounded-pill uppercase text-white" style="font-size: 8px; letter-spacing: 0.5px;">2. VISACIÓN GASTO DEFINITIVA</span>
                                        <?php elseif(in_array($p['estado_actual'], ['ESPERANDO_CDP_FINANZAS', 'ESPERANDO_CDP_FINANZAS_FINAL'])): ?>
                                            <span class="badge bg-warning text-dark px-2.5 py-1.5 rounded-pill uppercase fw-bold" style="font-size: 8px; letter-spacing: 0.5px;"><i class="bi bi-clock-history me-1"></i> ESPERA DE CDP (FINANZAS)</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary px-2.5 py-1.5 rounded-pill uppercase text-white" style="font-size: 8px; letter-spacing: 0.5px;"><?= htmlspecialchars($p['estado_nombre']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3">
                                        <div class="fw-semibold text-secondary small"><?= htmlspecialchars($p['unidad']) ?></div>
                                        <div class="text-muted" style="font-size: 10px;"><?= htmlspecialchars($p['solicitante']) ?></div>
                                    </td>
                                    <td class="p-3 text-end fw-bold font-monospace <?= $p['monto_definitivo'] ? 'text-success' : 'text-dark' ?>">
                                        <?= money($p['monto_definitivo'] ?? $p['monto_estimado']) ?>
                                    </td>
                                    <td class="p-3 text-center">
                                        <a href="?view=revisar&id=<?= $p['id'] ?>" class="btn <?= $vista === 'pendientes' ? 'btn-primary' : 'btn-outline-secondary' ?> btn-sm fw-bold shadow-sm">
                                            <?= $vista === 'pendientes' ? 'Analizar' : 'Ver Detalle' ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
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
                    <span class="badge bg-primary text-uppercase tracking-wider mb-1.5" style="font-size: 9px; letter-spacing: 0.5px;">
                        <?= $es_fase_inicial ? 'Fase: Emisión de Certificado de Disponibilidad (CDP)' : 'Fase: Visación de Gasto Definitiva' ?>
                    </span>
                    <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        Expediente: <span class="font-monospace text-primary">#<?= htmlspecialchars($expediente['codigo_interno']) ?></span>
                        <button type="button" onclick="verTrazabilidad(<?= (int)$expediente['id'] ?>)" class="btn btn-outline-primary btn-sm px-2.5 py-1 fw-bold shadow-sm d-inline-flex align-items-center gap-1.5" style="font-size: 11px;">
                            <i class="bi bi-clock-history"></i> Ver Historial
                        </button>
                    </h1>
                </div>
                <div class="col-12 col-md-auto text-start text-md-end">
                    <a href="control_presupuestario.php" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
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
                                    <span class="badge bg-indigo-subtle text-indigo fw-bold text-wrap text-start mt-1 px-2.5 py-1.5 fs-6" style="border: 1px solid rgba(102,16,242,0.1);">[ID: <?= $expediente['centro_costo_id'] ?>] <?= htmlspecialchars($expediente['centro_costo']) ?></span>
                                </div>

                                <div class="d-flex flex-column gap-2 pt-2 border-top">
                                    <?php if(!empty($expediente['folio_opi'])): ?>
                                        <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded-3 border">
                                            <span class="text-muted fw-bold text-uppercase" style="font-size: 9px;">Folio OPI:</span> 
                                            <span class="font-monospace fw-bold text-dark"><?= htmlspecialchars($expediente['folio_opi']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($expediente['id_compra_agil'])): ?>
                                        <div class="d-flex justify-content-between align-items-center bg-teal-subtle text-teal-emphasis p-2 rounded-3 border border-teal-subtle">
                                            <span class="fw-bold text-uppercase" style="font-size: 9px;">ID Compra Ágil:</span> 
                                            <span class="font-monospace fw-bold"><?= htmlspecialchars($expediente['id_compra_agil']) ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if(!empty($expediente['id_licitacion'])): ?>
                                        <div class="d-flex justify-content-between align-items-center bg-purple-subtle text-purple-emphasis p-2 rounded-3 border border-purple-subtle">
                                            <span class="fw-bold text-uppercase" style="font-size: 9px;">ID Licitación:</span> 
                                            <span class="font-monospace fw-bold"><?= htmlspecialchars($expediente['id_licitacion']) ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if(!empty($expediente['id_contrato_suministro'])): ?>
                                        <div class="d-flex justify-content-between align-items-center bg-warning-subtle text-warning-emphasis p-2 rounded-3 border border-warning-subtle">
                                            <span class="fw-bold text-uppercase" style="font-size: 9px;">ID Suministro:</span> 
                                            <span class="font-monospace fw-bold"><?= htmlspecialchars($expediente['id_contrato_suministro']) ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if(!empty($expediente['orden_compra_numero'])): ?>
                                        <div class="d-flex justify-content-between align-items-center bg-primary-subtle text-primary-emphasis p-2 rounded-3 border border-primary-subtle">
                                            <span class="fw-bold text-uppercase" style="font-size: 9px;">N° Orden Compra:</span> 
                                            <span class="font-monospace fw-bold"><?= htmlspecialchars($expediente['orden_compra_numero']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if(!$es_fase_inicial && $expediente['proveedor_adjudicado_id']): ?>
                                    <div class="bg-success-subtle border border-success-subtle p-3 rounded-3 mt-1 text-success-emphasis">
                                        <span class="fw-bold d-block text-uppercase mb-1" style="font-size: 9px;">Proveedor Adjudicado</span>
                                        <p class="font-bold mb-1 leading-tight fs-6"><?= htmlspecialchars($expediente['proveedor_nombre']) ?></p>
                                        <p class="font-monospace small mb-0 text-muted">RUT: <?= htmlspecialchars($expediente['proveedor_rut']) ?></p>
                                    </div>
                                <?php endif; ?>

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
                        <div class="card-header bg-white py-3 d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3">
                            <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                                <i class="bi bi-diagram-3 text-secondary"></i>
                                Imputación Contable y Saldos (Micro)
                            </h6>
                            <div class="text-start text-sm-end">
                                <?php if($es_fase_inicial): ?>
                                    <span class="text-muted text-uppercase fw-bold" style="font-size: 9px;">Presupuesto Estimado</span>
                                    <div class="h5 fw-black text-primary font-monospace mb-0"><?= money($expediente['monto_estimado']) ?></div>
                                <?php else: ?>
                                    <span class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 9px;">Cálculo de Adjudicación Final</span>
                                    <div class="d-flex gap-3 align-items-center justify-content-start justify-content-sm-end text-sm">
                                        <div class="text-start text-sm-end">
                                            <span class="text-muted font-bold d-block text-uppercase" style="font-size: 8px;">Estimado</span>
                                            <span class="text-secondary small line-through"><?= money($expediente['monto_estimado']) ?></span>
                                        </div>
                                        <div class="text-start text-sm-end">
                                            <span class="text-success font-bold d-block text-uppercase" style="font-size: 8px;">Gasto Definitivo</span>
                                            <span class="text-success fw-black fs-5 font-monospace"><?= money($expediente['monto_definitivo']) ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card-body bg-slate-50 p-3">
                            <div class="d-flex flex-column gap-3">
                                <?php 
                                foreach($items as $it): 
                                    $costo_linea = $it['cantidad'] * $it['precio_unitario'];
                                    
                                    // Cálculo de ajuste para mostrar costo en fase final
                                    if (!$es_fase_inicial && $expediente['monto_definitivo'] > 0) {
                                        $total_original = $expediente['monto_estimado'];
                                        $total_nuevo = $expediente['monto_definitivo'];
                                        $diferencia = $total_nuevo - $total_original;
                                        $proporcion = ($total_original > 0) ? ($costo_linea / $total_original) : 0;
                                        $ajuste_requerido = $diferencia * $proporcion;
                                        $costo_mostrar = $costo_linea + $ajuste_requerido;
                                    } else {
                                        $costo_mostrar = $costo_linea;
                                    }
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
                                                <span class="fw-bold font-monospace <?= !$es_fase_inicial ? 'text-success' : 'text-dark' ?>" style="font-size: 14px;">
                                                    <?= money($costo_mostrar) ?>
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

                                        <!-- Disponible y Convenio Marco -->
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-2 border-top border-light-subtle small">
                                            <?php if($expediente['tipo_compra_cod'] === 'CONVENIO_MARCO'): ?>
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

                    <!-- CRITERIOS DE EVALUACIÓN PUBLICADOS -->
                    <?php if(!empty($criterios)): ?>
                    <div class="card border-info bg-info-subtle shadow-sm">
                        <div class="card-header bg-white border-info text-info-emphasis py-3">
                            <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">
                                <i class="bi bi-calculator-fill text-info"></i>
                                Criterios de Evaluación Publicados
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="table-responsive rounded-3 border border-info-subtle">
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

                    <!-- ACCIONES DE RESOLUCIÓN -->
                    <div class="card shadow-lg border-light">
                        <div class="card-body p-4">
                            
                            <?php if($es_accionable): ?>
                                <h5 class="fw-bold text-dark mb-1">Resolución Financiera</h5>
                                <p class="text-secondary small mb-4">Verifique la disponibilidad y emita su visación para el expediente.</p>
                                


                                <?php 
                                $transiciones = obtener_transiciones_disponibles($pdo, $expediente['id']); 
                                $es_reserva_inicial_compra_agil = ($expediente['estado_actual'] === 'EN_VALIDACION_PRESUPUESTARIA' && $expediente['tipo_compra_cod'] === 'COMPRA_AGIL');
                                ?>
                                <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="expediente_id" value="<?= $expediente['id'] ?>">
                                    

                                         <?php if(!$es_reserva_inicial_compra_agil): ?>
                                             <!-- PANEL EMISIÓN Y GESTIÓN DE CDP -->
                                             <div class="card border-primary bg-primary-subtle shadow-sm mb-4">
                                                 <div class="card-header bg-primary text-white py-2 fw-bold text-sm d-flex justify-content-between align-items-center" style="background-color: #3b82f6 !important;">
                                                     <span class="text-white"><i class="bi bi-file-earmark-check-fill me-1"></i> Certificado de Disponibilidad Presupuestaria (CDP)</span>
                                                     <?php if($expediente['estado_actual'] !== 'EN_VALIDACION_PRESUPUESTARIA_FINAL'): ?>
                                                         <a href="cdp.php?id=<?= $expediente['id'] ?>" target="_blank" class="btn btn-light btn-xs fw-bold px-2 py-0.5" style="font-size: 10px; color: #1e3a8a;">
                                                             <i class="bi bi-filetype-pdf"></i> Confeccionar CDP
                                                         </a>
                                                     <?php endif; ?>
                                                 </div>
                                                 <div class="card-body p-3">
                                                     
                                                     <!-- SOLICITAR FIRMA A FINANZAS -->
                                                     <div class="p-3 bg-white border border-primary-subtle rounded-3">
                                                         <h6 class="text-primary fw-bold mb-2 uppercase tracking-wider" style="font-size: 10px;">Solicitud de Firma a Finanzas</h6>
                                                         
                                                         <div class="mb-2">
                                                             <label class="form-label text-secondary fw-bold mb-1" style="font-size: 8.5px; text-transform: uppercase;">1. Adjuntar Borrador de CDP (PDF)</label>
                                                             <input type="file" name="archivo_cdp_borrador" id="archivo_cdp_borrador" accept="application/pdf" class="form-control form-control-sm bg-light">
                                                         </div>
                                                         
                                                         <div class="mb-3">
                                                             <label class="form-label text-secondary fw-bold mb-1" style="font-size: 8.5px; text-transform: uppercase;">2. Adjuntar Situación Presupuestaria de Gastos (PDF)</label>
                                                             <input type="file" name="archivo_situacion_gastos" id="archivo_situacion_gastos" accept="application/pdf" class="form-control form-control-sm bg-light">
                                                         </div>
                                                     </div>

                                                 </div>
                                             </div>
                                         <?php endif; ?>

                                         <!-- BADGE CDP FIRMADO (SI EXISTE) -->
                                         <?php if($doc_cdp_firmado): ?>
                                             <div class="alert alert-success d-flex align-items-center justify-content-between gap-3 mb-4 shadow-sm">
                                                 <div class="d-flex align-items-center gap-2">
                                                     <i class="bi bi-patch-check-fill fs-4 text-success shrink-0"></i>
                                                     <div>
                                                         <strong class="d-block text-dark small">CDP Firmado por Finanzas Adjunto</strong>
                                                         <span class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($doc_cdp_firmado['nombre_original']) ?></span>
                                                     </div>
                                                 </div>
                                                 <a href="<?= htmlspecialchars($doc_cdp_firmado['ruta_archivo']) ?>" target="_blank" class="btn btn-sm btn-outline-success fw-bold text-nowrap" style="font-size: 11px;">
                                                     <i class="bi bi-file-earmark-pdf"></i> Ver CDP Firmado
                                                 </a>
                                             </div>
                                         <?php endif; ?>

                                         <!-- BOTÓN DE APROBACIÓN PRINCIPAL -->
                                         <?php 
                                         $has_approvals = false;
                                         foreach ($transiciones as $t): 
                                             if ($t['accion_codigo'] === 'APROBAR'):
                                                 $has_approvals = true;
                                         ?>
                                             <button type="submit" name="transicion_id" value="<?= $t['id'] ?>" onclick="return <?= $es_fase_final ? 'validarSolicitudCDP(event)' : "confirm('¿Confirma la acción de: " . htmlspecialchars($t['accion_label']) . "?')" ?>" class="btn btn-indigo btn-lg w-100 py-3 mb-4 shadow d-flex align-items-center justify-content-center gap-2 fw-bold text-white">
                                                 <i class="bi bi-check-circle-fill"></i>
                                                 <?= htmlspecialchars($t['accion_label']) ?>
                                             </button>
                                         <?php 
                                             endif;
                                         endforeach; 
                                         ?>


                                    <!-- REPAROS Y OBS -->
                                    <div class="border-top pt-4">
                                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Reparos y Observaciones (Obligatorio para Devolver/Rechazar)</label>
                                        <textarea name="motivo_rechazo" rows="3" class="form-control text-sm mb-4 bg-light"></textarea>
                                        
                                        <div class="row g-2">
                                            <!-- Botones de Devolución -->
                                            <?php 
                                            $has_return = false;
                                            foreach ($transiciones as $t): 
                                                if ($t['accion_codigo'] === 'DEVOLVER'):
                                                    $has_return = true;
                                            ?>
                                                <div class="col-sm-6">
                                                    <button type="submit" name="transicion_id" value="<?= $t['id'] ?>" onclick="return confirm('¿Confirma la acción de: <?= htmlspecialchars($t['accion_label']) ?>?')" class="btn btn-warning w-100 py-2.5 fw-bold text-dark shadow-sm d-flex align-items-center justify-content-center gap-2">
                                                        <i class="bi bi-arrow-counterclockwise"></i>
                                                        <?= htmlspecialchars($t['accion_label']) ?>
                                                    </button>
                                                </div>
                                            <?php 
                                                endif;
                                            endforeach; ?>

                                            <!-- Botones de Rechazo -->
                                            <?php 
                                            $has_reject = false;
                                            foreach ($transiciones as $t): 
                                                if ($t['accion_codigo'] === 'RECHAZAR'):
                                                    $has_reject = true;
                                            ?>
                                                <div class="col-sm-6">
                                                    <button type="submit" name="transicion_id" value="<?= $t['id'] ?>" onclick="return confirm('¿Confirma la acción de: <?= htmlspecialchars($t['accion_label']) ?>?')" class="btn btn-outline-danger w-100 py-2.5 fw-bold d-flex align-items-center justify-content-center gap-2">
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
                                <div class="text-center py-4">
                                    <div class="p-3 bg-success-subtle text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                                        <i class="bi bi-check-lg fs-3"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark mb-1">Visación Completada</h5>
                                    <p class="text-secondary small mb-4">El expediente ha sido procesado por su departamento en esta fase.</p>
                                    
                                    <div class="bg-light rounded-3 p-3 border d-inline-block text-start mx-auto" style="min-width: 280px;">
                                        <span class="text-muted fw-bold d-block text-uppercase mb-1" style="font-size: 8px;">Estado del Expediente</span>
                                        <span class="fw-bold text-dark fs-6 d-block"><?= htmlspecialchars($expediente['estado_nombre']) ?></span>
                                        <span class="badge bg-primary-subtle text-primary-emphasis mt-2 px-2 py-1 fs-6" style="font-size: 9px; font-weight: bold;">
                                            Cargo: <?= htmlspecialchars($expediente['rol_responsable']) ?>
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
    
    <script>
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
</body>
</html>