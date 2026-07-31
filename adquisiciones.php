<?php 
// adquisiciones.php - Vista Separada (V5.3 - Paso Previo Recepción y UI Actualizada)
require_once __DIR__ . '/adquisiciones_controller.php'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bandeja de Adquisiciones - Sistema de Órdenes de Pedido Interno</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
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

        <!-- VISTA: TABLA PRINCIPAL DE ADQUISICIONES -->
        <?php if($vista === 'lista'): ?>
            <div class="row align-items-center mb-4 g-3">
                <div class="col-12 col-md">
                    <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="bi bi-cart3 text-primary"></i>
                        Bandeja de Adquisiciones
                    </h1>
                    <p class="text-muted small mb-0">Todas las tareas asignadas a su departamento por el flujo de compras municipal.</p>
                </div>
            </div>

            <div class="card shadow-sm border-light">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0 text-dark">Tareas Pendientes de Gestión</h6>
                </div>
                <div class="table-responsive rounded-bottom">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small">
                            <tr>
                                <th class="p-3">Trámite / Tipo</th>
                                <th class="p-3">Fase Actual (Su Tarea)</th>
                                <th class="p-3">Solicitante</th>
                                <th class="p-3" style="width: 120px;">Prioridad</th>
                                <th class="p-3 text-center" style="width: 120px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <?php if(empty($pendientes)): ?>
                                <tr>
                                    <td colspan="5" class="p-4 text-center text-muted italic">Bandeja limpia. No hay tareas pendientes de adquisiciones.</td>
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
                                        <div class="text-muted small text-uppercase tracking-wider" style="font-size: 10px;"><?= htmlspecialchars($p['tipo_compra']) ?></div>
                                    </td>
                                    <td class="p-3">
                                        <?php if($p['estado_actual'] === 'ESPERANDO_ACEPTACION_OC'): ?>
                                            <span class="badge bg-warning text-dark px-2.5 py-1.5 rounded-pill uppercase border border-warning" style="font-size: 8px;">ESPERANDO ACEPTACIÓN OC</span>
                                        <?php elseif($p['estado_actual'] === 'RECEPCIONADO_POR_ADQUISICIONES'): ?>
                                            <span class="badge bg-purple text-white px-2.5 py-1.5 rounded-pill uppercase border border-purple" style="font-size: 8px;">INGRESAR A PORTAL</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary text-white px-2.5 py-1.5 rounded-pill uppercase border border-primary" style="font-size: 8px;"><?= mb_strtoupper($p['estado_nombre']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3">
                                        <div class="fw-semibold text-secondary small"><?= htmlspecialchars($p['unidad']) ?></div>
                                        <div class="text-muted" style="font-size: 10px;"><?= htmlspecialchars($p['solicitante']) ?></div>
                                    </td>
                                    <td class="p-3">
                                        <span class="badge rounded-pill text-uppercase <?= str_contains($p['clase_css'], 'red') ? 'bg-danger' : (str_contains($p['clase_css'], 'amber') ? 'bg-warning text-dark' : 'bg-primary') ?>" style="font-size: 9px; letter-spacing: 0.5px;">
                                            <?= htmlspecialchars($p['prioridad']) ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <a href="?view=gestionar&id=<?= $p['id'] ?>" class="btn btn-primary btn-sm fw-bold px-3 shadow-sm">
                                            Gestionar
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
                                    
                                    <?php if($exp['orden_compra_numero']): ?>
                                        <div class="d-flex justify-content-between align-items-center bg-dark text-white p-2 rounded-3">
                                            <span class="fw-bold text-uppercase" style="font-size: 9px;">N° Orden de Compra:</span> 
                                            <span class="font-monospace fw-bold"><?= htmlspecialchars($exp['orden_compra_numero']) ?></span>
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
                                    
                                    <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold shadow d-flex align-items-center justify-content-center gap-2">
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
                                                        <button type="button" class="btn btn-success btn-sm px-2.5" data-bs-toggle="modal" data-bs-target="#modalProv" title="Agregar Nuevo Proveedor">
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
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Número de Orden de Compra <span class="text-danger">*</span></label>
                                        <input type="text" name="orden_compra_numero" required class="form-control">
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Subir Orden de Compra Firmada (PDF) <span class="text-danger">*</span></label>
                                        <input type="file" name="archivo_oc" accept="application/pdf" required class="form-control form-control-sm">
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold shadow d-flex align-items-center justify-content-center gap-2">
                                        <span>Registrar OC y Esperar Aceptación</span>
                                        <i class="bi bi-cloud-arrow-up-fill fs-6"></i>
                                    </button>
                                </form>
                            </div>

                        <!-- FASE 4: ESPERA ACEPTACIÓN DE PROVEEDOR -->
                        <?php elseif ($estado === 'ESPERANDO_ACEPTACION_OC'): ?>
                            <div class="card-body p-4">
                                <h6 class="fw-bold text-dark mb-1">Confirmación de Aceptación del Proveedor</h6>
                                <p class="text-secondary small mb-4">Verifique el estado de la Orden de Compra en el portal de Mercado Público. Si fue aceptada por el proveedor o rechazada, márquelo aquí.</p>
                                
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <form method="POST" onsubmit="return confirm('¿Confirma que el proveedor ACEPTÓ la OC?')">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                            <input type="hidden" name="accion" value="oc_aceptada">
                                            <input type="hidden" name="expediente_id" value="<?= $exp['id'] ?>">
                                            <button type="submit" class="btn btn-success btn-lg w-100 py-3 fw-bold shadow-sm d-flex flex-column align-items-center gap-1">
                                                <i class="bi bi-check-circle-fill fs-4"></i>
                                                <span>Aceptar Compra</span>
                                            </button>
                                        </form>
                                    </div>

                                    <div class="col-sm-6">
                                        <form method="POST" onsubmit="return confirm('¿Rechazó la orden? Se devolverá el trámite a Evaluación. La OPI seguirá vigente.')">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                            <input type="hidden" name="accion" value="oc_rechazada">
                                            <input type="hidden" name="expediente_id" value="<?= $exp['id'] ?>">
                                            <input type="text" name="motivo_rechazo_proveedor" required class="form-control form-control-sm text-center border-danger placeholder-danger-emphasis text-danger mb-2">
                                            <button type="submit" class="btn btn-danger btn-lg w-100 py-3 fw-bold shadow-sm d-flex flex-column align-items-center gap-1">
                                                <i class="bi bi-x-circle-fill fs-4"></i>
                                                <span>Rechazar Compra</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        <!-- FASE 5: GENÉRICA -->
                        <?php else: ?>
                            <div class="card-body p-4">
                                <h6 class="fw-bold text-dark mb-1">Gestión de Trámite</h6>
                                <p class="text-secondary small mb-4">Confirme la gestión de esta fase para avanzar el expediente.</p>
                                
                                <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="accion" value="accion_generica">
                                    <input type="hidden" name="expediente_id" value="<?= $exp['id'] ?>">
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Comentario de la Acción</label>
                                        <textarea name="comentario_flujo" class="form-control" rows="2" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold shadow">Completar Fase y Avanzar</button>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                        <!-- SECCIÓN PELIGRO: ANULACIÓN DE COMPRA -->
                        <?php if (!empty($exp['folio_opi'])): ?>
                            <div class="card-footer bg-danger-subtle border-top border-danger-subtle p-4">
                                <h6 class="text-danger-emphasis fw-bold mb-2 d-flex align-items-center gap-2">
                                    <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                                    Zona de Peligro: Anulación Definitiva (Caída de Compra)
                                </h6>
                                <p class="text-danger-emphasis small mb-4">Si la licitación quedó desierta o el proveedor desistió definitivamente y no habrá reintento, anule el requerimiento. <strong>El número de OPI quedará quemado y marcado como anulado en auditoría.</strong></p>
                                
                                <form method="POST" onsubmit="return confirm('ATENCIÓN: Esto ANULARÁ definitivamente la OPI. ¿Está completamente seguro?')">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="accion" value="anular_opi_definitiva">
                                    <input type="hidden" name="expediente_id" value="<?= $exp['id'] ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-danger small text-uppercase" style="font-size: 10px;">Motivo exacto de la anulación definitiva</label>
                                        <input type="text" name="motivo_anulacion" required class="form-control form-control-sm border-danger bg-white">
                                    </div>
                                    
                                    <button type="submit" class="btn btn-danger w-100 py-2 fw-bold shadow-sm">
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
    </div>
</body>
</html>