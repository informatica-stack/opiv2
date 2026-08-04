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

        <!-- VISTAS: BANDEJA DE LISTADOS -->
        <?php if($vista === 'pendientes' || $vista === 'procesados'): ?>
            <div class="row align-items-center mb-4 g-3">
                <div class="col-12 col-md">
                    <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-check text-primary"></i>
                        Firma de CDP
                    </h1>
                    <p class="text-muted small mb-0">Dirección de Administración y Finanzas (DAF). Adjuntar certificados SMC.</p>
                </div>
            </div>

            <!-- TABS DE NAVEGACIÓN -->
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link <?= $vista === 'pendientes' ? 'active fw-bold' : '' ?>" href="?view=pendientes">Pendientes de Certificado</a>
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
                    <h6 class="fw-bold mb-0 text-dark"><?= $vista === 'pendientes' ? 'Requerimientos en espera de CDP' : 'Requerimientos Procesados' ?></h6>
                </div>
                <div class="table-responsive rounded-bottom">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small" style="font-size: 10px;">
                            <tr>
                                <th class="p-3">Requerimiento</th>
                                <th class="p-3">Fase de Origen</th>
                                <th class="p-3">Unidad Solicitante</th>
                                <th class="p-3 text-end" style="width: 180px;">Monto</th>
                                <th class="p-3 text-center" style="width: 120px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <?php 
                            $lista = $vista === 'pendientes' ? $pendientes : $procesados;
                            if(empty($lista)): ?>
                                <tr>
                                    <td colspan="5" class="p-4 text-center text-muted italic">Bandeja al día. No hay expedientes pendientes.</td>
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
                                        <span class="badge bg-purple px-2.5 py-1.5 rounded-pill text-white" style="font-size: 8px; letter-spacing: 0.5px;">CDP SOLICITADO</span>
                                    </td>
                                    <td class="p-3">
                                        <div class="fw-semibold text-secondary small"><?= htmlspecialchars($p['unidad']) ?></div>
                                        <div class="text-muted" style="font-size: 10px;"><?= htmlspecialchars($p['solicitante']) ?></div>
                                    </td>
                                    <td class="p-3 text-end fw-bold font-monospace text-dark">
                                        <?= money($p['monto_definitivo'] ?? $p['monto_estimado']) ?>
                                    </td>
                                    <td class="p-3 text-center">
                                        <a href="?view=revisar&id=<?= $p['id'] ?>" class="btn <?= $vista === 'pendientes' ? 'btn-primary' : 'btn-outline-secondary' ?> btn-sm fw-bold shadow-sm">
                                            <?= $vista === 'pendientes' ? 'Gestionar' : 'Ver Detalle' ?>
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

                    <!-- ACCIONES DE RESOLUCIÓN -->
                    <div class="card shadow-lg border-light">
                        <div class="card-body p-4">
                            
                            <?php if($es_accionable): ?>
                                <h5 class="fw-bold text-dark mb-1">Firma de CDP</h5>
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
                                    <div class="card border-primary bg-primary-subtle shadow-sm mb-4">
                                        <div class="card-body p-3">
                                            <div class="mb-3">
                                                <h6 class="text-primary-emphasis fw-bold mb-2 d-flex align-items-center gap-1.5" style="font-size: 11px;">
                                                    <i class="bi bi-download text-primary"></i>
                                                    Paso 1: Descargar Documentos Adjuntos del Analista
                                                </h6>
                                                <div class="d-flex flex-column gap-2 bg-white p-2.5 rounded border border-primary-subtle">
                                                    <?php if($doc_borrador): ?>
                                                        <a href="<?= htmlspecialchars($doc_borrador['ruta_archivo']) ?>" download target="_blank" class="btn btn-outline-primary btn-sm text-start fw-bold d-flex align-items-center justify-content-between px-3 py-2">
                                                            <span><i class="bi bi-file-earmark-pdf-fill me-1.5"></i> Descargar Borrador de CDP (Sin Firmar)</span>
                                                            <i class="bi bi-cloud-arrow-down-fill fs-5"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <div class="alert alert-warning py-1.5 px-2.5 mb-0 small"><i class="bi bi-exclamation-triangle me-1"></i> No se encontró Borrador de CDP adjunto.</div>
                                                    <?php endif; ?>

                                                    <?php if($doc_situacion): ?>
                                                        <a href="<?= htmlspecialchars($doc_situacion['ruta_archivo']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm text-start fw-bold d-flex align-items-center justify-content-between px-3 py-2">
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

                                            <div class="border-top border-primary-subtle pt-3">
                                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Paso 2: Adjuntar Certificado de Disponibilidad SMC Firmado (Obligatorio)</label>
                                                <input type="file" name="archivo_cdp" accept="application/pdf" required class="form-control form-control bg-white">
                                                <p class="text-muted mt-1 mb-0" style="font-size: 9px;">Solo se permiten archivos en formato PDF.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- BOTÓN DE APROBACIÓN PRINCIPAL (SUBIR CDP) -->
                                    <?php 
                                    $t_aprobar = null;
                                    foreach ($transiciones as $t) {
                                        if ($t['accion_codigo'] === 'APROBAR') $t_aprobar = $t;
                                    }
                                    if ($t_aprobar):
                                    ?>
                                        <button type="submit" name="transicion_id" value="<?= $t_aprobar['id'] ?>" onclick="return confirm('¿Confirma que ha subido el CDP correcto y firmado?')" class="btn btn-success btn-lg w-100 py-3 mb-4 shadow d-flex align-items-center justify-content-center gap-2 fw-bold text-white">
                                            <i class="bi bi-check-circle-fill"></i>
                                            <?= htmlspecialchars($t_aprobar['accion_label']) ?>
                                        </button>
                                    <?php endif; ?>

                                    <!-- REPAROS Y OBS -->
                                    <div class="border-top pt-4">
                                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Reparos y Observaciones (Obligatorio para Devolver/Rechazar)</label>
                                        <textarea name="motivo_rechazo" rows="3" class="form-control text-sm mb-4 bg-light"></textarea>
                                        
                                        <div class="row g-2">
                                            <!-- Botones de Devolución -->
                                            <?php foreach ($transiciones as $t): 
                                                if ($t['accion_codigo'] === 'DEVOLVER'):
                                            ?>
                                                <div class="col-sm-6">
                                                    <button type="submit" name="transicion_id" value="<?= $t['id'] ?>" onclick="return confirm('¿Confirma devolver la solicitud?')" class="btn btn-warning w-100 py-2.5 fw-bold text-dark shadow-sm d-flex align-items-center justify-content-center gap-2">
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
                                                    <button type="submit" name="transicion_id" value="<?= $t['id'] ?>" onclick="return confirm('¿Confirma rechazar definitivamente la solicitud?')" class="btn btn-outline-danger w-100 py-2.5 fw-bold d-flex align-items-center justify-content-center gap-2">
                                                        <i class="bi bi-x-circle-fill"></i>
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

    </div>
</body>
</html>
