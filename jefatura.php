<?php
// jefatura.php - Visación Técnica de Jefatura (Vista UI V4.6)
require_once __DIR__ . '/jefatura_controller.php';
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bandeja de Entrada Jefatura - Gestión OPI</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-slate-50 text-slate-800 pb-20 font-sans">

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

        <!-- VISTA: LISTA DE SOLICITUDES PENDIENTES -->
        <?php if($vista === 'lista'): ?>
            <div class="row align-items-center mb-4 g-3">
                <div class="col-12 col-md">
                    <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="bi bi-shield-check text-primary"></i>
                        Visación de Jefatura
                    </h1>
                    <p class="text-muted small mb-0">
                        <?php if(isset($_SESSION['es_subrogante']) && $_SESSION['es_subrogante']): ?>
                            <span class="badge bg-warning text-dark me-2">SUBROGANTE</span>
                        <?php endif; ?>
                        Revise y autorice las solicitudes de su unidad.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm border-light">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0 text-dark">Solicitudes Pendientes de Visación</h6>
                </div>
                <div class="table-responsive rounded-bottom">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small">
                            <tr>
                                <th class="p-3">Código / Tipo</th>
                                <th class="p-3">Solicitante</th>
                                <th class="p-3" style="width: 130px;">Prioridad</th>
                                <th class="p-3 text-end" style="width: 180px;">Monto Estimado</th>
                                <th class="p-3 text-center" style="width: 120px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if(empty($pendientes)): ?>
                                <tr>
                                    <td colspan="5" class="p-4 text-center text-muted italic">No tiene solicitudes pendientes de visación.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($pendientes as $p): ?>
                                <tr>
                                    <td class="p-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-bold text-dark font-monospace"><?= $p['codigo_interno'] ?></span>
                                            <button type="button" onclick="verTrazabilidad(<?= (int)$p['id'] ?>)" class="btn btn-link p-0 border-0 d-flex align-items-center" title="Ver Historial">
                                                <i class="bi bi-clock-history text-primary" style="font-size: 13px;"></i>
                                            </button>
                                        </div>
                                        <div class="text-muted small text-uppercase tracking-wider" style="font-size: 10px;"><?= htmlspecialchars($p['tipo_compra_nom'] ?? '') ?></div>
                                    </td>
                                    <td class="p-3">
                                        <div class="fw-semibold text-secondary small"><?= htmlspecialchars($p['solicitante']) ?></div>
                                        <div class="text-muted" style="font-size: 10px;"><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></div>
                                    </td>
                                    <td class="p-3">
                                        <span class="badge rounded-pill text-uppercase <?= str_contains($p['clase_css'], 'red') ? 'bg-danger' : (str_contains($p['clase_css'], 'amber') ? 'bg-warning text-dark' : 'bg-primary') ?>" style="font-size: 9px; letter-spacing: 0.5px;">
                                            <?= htmlspecialchars($p['prioridad_nom']) ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-end fw-bold text-dark font-monospace">
                                        <?= money($p['monto_estimado']) ?>
                                    </td>
                                    <td class="p-3 text-center">
                                        <a href="?view=revisar&id=<?= $p['id'] ?>" class="btn btn-primary btn-sm fw-bold px-3 shadow-sm">Revisar</a>
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
                                    <span class="text-muted fw-bold d-block text-uppercase" style="font-size: 9px;">Centro de Costo / Unidad:</span>
                                    <span class="badge bg-primary-subtle text-primary fw-bold text-wrap text-start mt-1 px-2.5 py-1.5 fs-6" style="border: 1px solid rgba(13,110,253,0.1);"><?= htmlspecialchars($exp['centro_costo']) ?></span>
                                </div>
                                
                                <div>
                                    <span class="text-muted fw-bold d-block text-uppercase" style="font-size: 9px;">Tipo de Compra:</span>
                                    <span class="fw-semibold text-secondary"><?= htmlspecialchars($exp['tipo_compra_nom']) ?></span>
                                </div>
                                
                                <?php if($exp['id_contrato_suministro']): ?>
                                    <div>
                                        <span class="text-muted fw-bold d-block text-uppercase" style="font-size: 9px;">ID Suministro:</span>
                                        <span class="text-teal fw-bold font-monospace bg-teal-subtle px-2 py-0.5 rounded"><?= htmlspecialchars($exp['id_contrato_suministro']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if($exp['proveedor_adjudicado_id']): ?>
                                    <div>
                                        <span class="text-muted fw-bold d-block text-uppercase" style="font-size: 9px;">Proveedor Sugerido:</span>
                                        <span class="text-indigo fw-bold bg-indigo-subtle px-2 py-1 rounded d-inline-block mt-1"><?= htmlspecialchars($exp['proveedor_rut'].' - '.$exp['proveedor_nombre']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="border-top pt-2 mt-2">
                                    <span class="text-muted fw-bold d-block text-uppercase mb-1" style="font-size: 9px;">Justificación Técnica:</span>
                                    <div class="bg-light p-2.5 rounded border small text-secondary leading-relaxed" style="max-height: 200px; overflow-y: auto; font-size: 11px;">
                                        <?= nl2br(htmlspecialchars($exp['motivo_compra'])) ?>
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

                    <!-- TARJETA DE RESOLUCIÓN -->
                    <div class="card shadow-lg border-primary">
                        <div class="card-header bg-primary text-white py-3">
                            <h5 class="fw-bold mb-0">Resolución de Jefatura</h5>
                        </div>
                        <div class="card-body p-4">
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
                        </div>
                    </div>

                </div>
            </div>

        <?php endif; ?>

    </div>
</body>
</html>