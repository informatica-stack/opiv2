<?php
// editar_solicitud.php - Interfaz de corrección (V5.0 - UI sincronizada con Plan Compras)
require_once __DIR__ . '/editar_solicitud_controller.php';

$listado_prov_json = [];
foreach($mis_proveedores as $p) {
    $listado_prov_json[] = [
        'id' => (int)$p['id'],
        'rut' => $p['rut'],
        'razon_social' => $p['razon_social'],
        'frecuente' => true
    ];
}
foreach($otros_proveedores as $p) {
    $ya_esta = false;
    foreach($listado_prov_json as $lp) {
        if ($lp['id'] == $p['id']) {
            $ya_esta = true;
            break;
        }
    }
    if (!$ya_esta) {
        $listado_prov_json[] = [
            'id' => (int)$p['id'],
            'rut' => $p['rut'],
            'razon_social' => $p['razon_social'],
            'frecuente' => false
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php 
    $titulo_pagina = "Corregir Solicitud";
    include __DIR__ . '/head.php'; 
    ?>
</head>
<body class="bg-slate-50 text-slate-800 font-sans pb-20">

    <?php include __DIR__ . '/nav.php'; ?>

    <div class="container mt-4 px-3 px-md-4">
        
        <!-- CABECERA PRINCIPAL -->
        <div class="row align-items-center mb-4 g-3">
            <div class="col-12 col-md">
                <span class="badge bg-warning text-dark px-2.5 py-1.5 fw-bold uppercase tracking-wider mb-2" style="font-size: 10px;">Modo Corrección</span>
                <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                    <i class="bi bi-pencil-square text-warning"></i>
                    Corregir Solicitud: <span class="font-monospace text-primary">#<?= htmlspecialchars($exp['codigo_interno']) ?></span>
                </h1>
                <p class="text-muted small mb-0">Por favor realice las correcciones indicadas por la jefatura.</p>
            </div>
            <div class="col-12 col-md-auto text-start text-md-end">
                <a href="mis_solicitudes.php" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                    Cancelar
                </a>
            </div>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div><?= htmlspecialchars($mensaje) ?></div>
            </div>
        <?php endif; ?>

        <!-- MOTIVO DE DEVOLUCIÓN -->
        <?php if($exp['estado_actual'] === 'EN_CORRECCION'): ?>
        <div class="card border-warning bg-warning-subtle shadow-sm mb-4">
            <div class="card-header bg-white border-warning text-warning-emphasis fw-bold py-2.5">
                <i class="bi bi-exclamation-circle-fill me-1 text-warning"></i> Motivo de Devolución / Observaciones
            </div>
            <div class="card-body p-3">
                <ul class="list-group list-group-flush bg-transparent">
                    <?php foreach($historial as $h): ?>
                        <?php if($h['accion'] == 'DEVOLVER'): ?>
                        <li class="list-group-item bg-transparent d-flex align-items-start gap-2 border-0 px-0 py-1.5">
                            <span class="font-monospace text-muted small mt-0.5" style="font-size: 11px;"><?= date('d/m H:i', strtotime($h['fecha_accion'])) ?></span>
                            <div class="text-dark bg-white border rounded px-3 py-2 flex-grow-1 shadow-sm"><?= htmlspecialchars($h['comentario']) ?></div>
                        </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- WIDGET DE INFORMACIÓN PRESUPUESTARIA -->
        <div class="card border-0 bg-primary-subtle shadow-sm mb-4">
            <div class="card-body p-3 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-primary text-white rounded-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-bank fs-4"></i>
                    </div>
                    <div>
                        <p class="text-uppercase text-secondary fw-bold small mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Imputación Presupuestaria de Unidad</p>
                        <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($exp['cc_nombre']) ?></h6>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" id="formCorreccion" action="editar_solicitud.php?id=<?= $id ?>" enctype="multipart/form-data" onsubmit="return validarFormulario()">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="accion" value="actualizar">
            <input type="hidden" name="id" value="<?= $id ?>">

            <!-- PASO 1: DATOS GENERALES -->
            <div class="card shadow-sm border-light mb-4">
                <div class="card-header bg-white py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-pill px-2.5 py-2 fs-6">1</span>
                        <div>
                            <h5 class="fw-bold mb-0">Datos Generales</h5>
                            <p class="text-muted small mb-0">Defina la justificación, tipo de compra y rango de monto de la solicitud.</p>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold text-secondary small">Título de la Compra <span class="text-danger">*</span></label>
                            <input type="text" name="titulo_compra" required class="form-control fw-semibold" value="<?= htmlspecialchars($post_titulo_compra) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold text-secondary small">Justificación Técnica / Observaciones <span class="text-danger">*</span></label>
                            <textarea name="motivo" required rows="3" class="form-control"><?= htmlspecialchars($post_motivo) ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary small">Tipo de Compra</label>
                            <select name="tipo_compra_id" id="selTipoCompra" required class="form-select fw-bold text-primary" onchange="evaluarFormularioReactivo()">
                                <?php foreach($tipos_compra as $t): ?>
                                    <option value="<?= $t['id'] ?>" <?= $t['id']==$post_tipo_compra?'selected':'' ?>><?= $t['nombre'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary small">Rango de Monto (Art. 10)</label>
                            <select name="rango_utm_id" required class="form-select">
                                <option value="">-- Seleccione el Rango UTM --</option>
                                <?php foreach($rangos_utm as $r): ?>
                                    <option value="<?= $r['id'] ?>" <?= $post_rango_utm == $r['id'] ? 'selected' : '' ?>>
                                        <?= $r['nombre'] ?> (<?= $r['min_utm'] ?> - <?= $r['max_utm'] ? $r['max_utm'].' UTM' : 'y más' ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <div class="row g-3 bg-light p-3 border rounded-3 m-0">
                                <div class="col-md-6 mt-0">
                                    <label class="form-label fw-bold text-secondary small">Proyecto (Plan de Compras) <span class="text-danger">*</span></label>
                                    <input type="text" name="plan_compras_proyecto" required class="form-control bg-white" value="<?= htmlspecialchars($post_plan_proyecto) ?>">
                                </div>
                                <div class="col-md-6 mt-0">
                                    <label class="form-label fw-bold text-secondary small">Ítem N° (Plan de Compras) <span class="text-danger">*</span></label>
                                    <input type="number" name="plan_compras_item" required min="1" step="1" class="form-control bg-white" value="<?= htmlspecialchars($post_plan_item) ?>">
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="prioridad_id" value="1">

                        <div class="col-md-6 d-none" id="divMontoDisponible">
                            <label class="form-label fw-bold text-primary small">Monto Disponible Neto de la Cotización <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-primary text-white fw-bold">$</span>
                                <input type="number" name="monto_disponible_neto" id="inpMontoDisponible" min="1" step="1" class="form-control fw-bold text-primary bg-white" oninput="calc()" value="<?= htmlspecialchars($post_monto_disponible_neto ?? '') ?>">
                            </div>
                            <div class="form-text text-muted small" style="font-size: 9px;">El total estimado sumará el 19% de IVA de forma automática.</div>
                        </div>

                        <div class="col-md-6">
                            <div class="bg-light border p-3 rounded-3 h-100">
                                <label class="form-label fw-bold text-secondary small">Documentos Adjuntos</label>
                                
                                <?php if(!empty($documentos_existentes)): ?>
                                    <div class="mb-3">
                                        <p class="text-uppercase text-muted fw-bold mb-1.5" style="font-size: 9px;">Archivos Anteriores Subidos:</p>
                                        <div class="d-flex flex-column gap-1">
                                            <?php foreach($documentos_existentes as $doc): ?>
                                                <a href="<?= htmlspecialchars($doc['ruta_archivo']) ?>" target="_blank" class="text-decoration-none small text-primary fw-bold d-inline-flex align-items-center gap-1">
                                                    <i class="bi bi-file-earmark-pdf"></i>
                                                    <?= htmlspecialchars($doc['nombre_original']) ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <p class="text-uppercase text-muted fw-bold mb-1" style="font-size: 9px;">Subir Archivo de Reemplazo (Opcional):</p>
                                <input type="file" name="archivo_adjunto" class="form-control form-control-sm"/>
                            </div>
                        </div>
                    </div>

                    <!-- PANELES DINÁMICOS REACTIVOS CON TRANSICIÓN -->
                    <!-- PANELES DINÁMICOS REACTIVOS CON TRANSICIÓN -->
                    <div id="panel-proveedor" class="panel-slide bg-light border rounded-3 p-4 mt-3">
                        <h6 class="text-primary-emphasis fw-bold mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-person-fill-check fs-5"></i>
                            Asignación Directa de Proveedor
                        </h6>
                        
                        <!-- Tarjeta de Resumen del Proveedor Seleccionado -->
                        <div class="card border border-light-subtle shadow-sm bg-white rounded-3 p-3">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div id="provResumenVacio" class="d-flex align-items-center gap-2 text-secondary">
                                    <i class="bi bi-exclamation-triangle fs-4 text-warning"></i>
                                    <div>
                                        <div class="fw-bold text-dark small">Sin Proveedor Asignado</div>
                                        <div class="text-muted" style="font-size: 11px;">Por favor, seleccione o pre-registre el proveedor para esta compra.</div>
                                    </div>
                                </div>
                                <div id="provResumenDetalle" class="d-none d-flex align-items-center gap-3">
                                    <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                        <i class="bi bi-building fs-5"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-primary small" id="provResumenRazonSocial"></div>
                                        <div class="text-secondary font-monospace" style="font-size: 11px;">RUT: <span id="provResumenRut"></span></div>
                                        <div class="text-muted" style="font-size: 11px;" id="provResumenDireccion"></div>
                                    </div>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-primary btn-sm fw-bold px-3 py-1.5 shadow-sm d-flex align-items-center gap-1.5" onclick="abrirModalProveedor()">
                                        <i class="bi bi-search"></i>
                                        <span id="btnSelectProvText">Seleccionar Proveedor</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Selección de Proveedor -->
                        <div class="modal fade" id="modalSeleccionarProveedor" tabindex="-1" aria-labelledby="modalSeleccionarProveedorLabel" aria-hidden="true" data-bs-backdrop="static">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content rounded-3 shadow">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="modalSeleccionarProveedorLabel">
                                            <i class="bi bi-search text-primary"></i>
                                            Búsqueda y Registro de Proveedor
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        
                                        <!-- Buscador Autocomplete -->
                                        <div class="mb-4">
                                            <label class="form-label fw-bold text-secondary small uppercase" style="font-size: 10px; letter-spacing: 0.5px;">🔎 Buscar Proveedor (RUT o Razón Social)</label>
                                            <div class="custom-select-container" id="containerProveedor">
                                                <input type="text" id="buscadorProv" class="form-control bg-white custom-select-input fw-bold" placeholder="🔎 Escriba RUT o Razón Social para buscar..." autocomplete="off" onfocus="showProvDropdown()" oninput="filterProvDropdown()">
                                                <input type="hidden" name="proveedor_id" id="selProveedor" value="<?= htmlspecialchars($post_proveedor_id) ?>">
                                                <div class="custom-select-dropdown" id="dropdownProv">
                                                    <!-- Opciones dinámicas renderizadas por JS -->
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Panel Nuevo Proveedor (Inline en el Modal) -->
                                        <div id="panelNuevoProv" class="panel-slide bg-light border border-info rounded-3 p-3 mt-3 shadow-sm d-none">
                                            <h6 class="text-info-emphasis fw-bold mb-1">Registro de Nuevo Proveedor en Adquisiciones</h6>
                                            <p class="text-muted mb-3" style="font-size: 11px;">Ingrese los datos para pre-registro. <b>Es obligatorio</b> subir la Ficha o Cotización formal en PDF.</p>
                                            
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold text-secondary small">RUT Sugerido <span class="text-danger">*</span></label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" name="nuevo_prov_rut" id="inpNuevoProvRut" class="form-control inp-nuevo-prov bg-white font-monospace" oninput="handleRutInput(this)">
                                                        <span class="input-group-text bg-white text-secondary py-0" id="rutStatusIcon" style="font-size: 11px; min-width: 32px; text-align: center; justify-content: center;">➖</span>
                                                    </div>
                                                    <div id="rutValidationMsg" class="small text-danger d-none mt-1" style="font-size: 10px;">⚠️ El formato o dígito verificador del RUT es inválido.</div>
                                                    <div id="rutDuplicateAlert" class="alert alert-warning p-2 mt-2 mb-0 small d-none" style="font-size: 11px; border-left: 4px solid #ffc107;">
                                                        <strong>⚠️ Ya registrado:</strong> <span id="duplicateProvName" class="fw-bold"></span>. 
                                                        <button type="button" class="btn btn-xs btn-warning py-0 px-2 fw-bold text-dark border-0 ms-2" onclick="selectDuplicateProvider()" style="font-size: 10px; background: #e0a800;">Seleccionar</button>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold text-secondary small">Razón Social / Nombre Sugerido <span class="text-danger">*</span></label>
                                                    <input type="text" name="nuevo_prov_nombre" id="inpNuevoProvNombre" class="form-control form-control-sm inp-nuevo-prov bg-white">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold text-secondary small">Dirección y Comuna Sugerida</label>
                                                    <input type="text" name="nuevo_prov_direccion" id="inpNuevoProvDireccion" class="form-control form-control-sm bg-white">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold text-secondary small">Ficha del Proveedor (PDF) <span class="text-danger">*</span></label>
                                                    <input type="file" name="ficha_proveedor" id="inpFichaProveedor" accept="application/pdf" class="form-control form-control-sm bg-white">
                                                </div>
                                            </div>
                                        </div>
                                        
                                    </div>
                                    <div class="modal-footer bg-light">
                                        <button type="button" class="btn btn-outline-secondary btn-sm px-4 fw-semibold" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="button" class="btn btn-primary btn-sm px-4 fw-bold shadow" onclick="confirmarSeleccionProveedor()">Confirmar Selección</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="panel-suministro" class="panel-slide bg-light border rounded-3 p-4 mt-3">
                        <h6 class="text-teal-emphasis fw-bold mb-2 d-flex align-items-center gap-2">
                            <i class="bi bi-file-earmark-check fs-5"></i>
                            Datos de Contrato de Suministro
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary small">ID / N° de Contrato <span class="text-danger">*</span></label>
                                <input type="text" name="id_contrato_suministro" id="inp_id_contrato_suministro" class="form-control bg-white" value="<?= htmlspecialchars($post_id_contrato_suministro) ?>">
                            </div>
                        </div>
                    </div>

                    <div id="panel-criterios" class="panel-slide bg-light border rounded-3 p-4 mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="text-purple-emphasis fw-bold mb-1 d-flex align-items-center gap-2">
                                    <i class="bi bi-calculator fs-5"></i>
                                    Criterios de Evaluación
                                </h6>
                                <p class="text-muted small mb-0">Corrija o añada criterios (La suma debe ser 100%).</p>
                            </div>
                            <button type="button" onclick="agregarCriterio()" class="btn btn-secondary btn-sm fw-bold shadow-sm">+ Añadir</button>
                        </div>
                        
                        <div class="bg-white rounded-3 border overflow-hidden">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="p-2 text-center" style="width: 60px;">N°</th>
                                        <th class="p-2">Descripción del Criterio</th>
                                        <th class="p-2 text-center" style="width: 120px;">Pond. %</th>
                                        <th class="p-2" style="width: 40px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyCriterios" class="divide-y divide-purple-50">
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="2" class="p-2 text-end fw-bold">SUMA TOTAL:</td>
                                        <td class="p-2 text-center fw-bold" id="tdTotalCriterio">0%</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div id="errorCriterios" class="text-danger small fw-bold mt-2 d-none">⚠️ La suma de los criterios debe ser exactamente 100%.</div>
                    </div>
                </div>
            </div>

            <!-- PASO 2: PRODUCTOS/SERVICIOS -->
            <div class="card shadow-sm border-light mb-4">
                <div class="card-header bg-white py-3 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-pill px-2.5 py-2 fs-6">2</span>
                        <div>
                            <h5 class="fw-bold mb-0">Detalle de Productos/Servicios</h5>
                            <p class="text-muted small mb-0">Ingrese los ítems de la solicitud de compra.</p>
                        </div>
                    </div>
                    
                    <input type="hidden" name="tipo_impuesto" value="NETO">
                </div>

                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted small fw-semibold">Detalle de Solicitud:</span>
                        <button type="button" onclick="agregarFila()" class="btn btn-primary btn-sm fw-bold shadow-sm d-flex align-items-center gap-1.5">
                            <i class="bi bi-plus-lg"></i>
                            Agregar Ítem
                        </button>
                    </div>

                    <div class="table-responsive rounded-3 border">
                        <table class="table table-hover align-middle mb-0" style="min-width: 900px;">
                            <thead class="table-light text-uppercase small">
                                <tr>
                                    <th class="p-3 w-25">Cuenta de Gasto</th>
                                    <th class="p-3">Descripción del Producto / Servicio</th>
                                    <th class="p-3 col-cm d-none text-primary" style="width: 130px;">ID CM</th>
                                    <th class="p-3" style="width: 120px;">Unidad</th>
                                    <th class="p-3 text-center" style="width: 90px;">Cant.</th>
                                    <th class="p-3 text-end col-precio" style="width: 135px;">Precio Unit.</th>
                                    <th class="p-3 text-end" style="width: 140px;">Total</th>
                                    <th class="p-3" style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="tbodyItems"></tbody>
                            
                            <tfoot class="table-light text-secondary">
                                <tr>
                                    <td colspan="5" class="p-3 text-end fw-semibold foot-colspan">Monto Subtotal:</td>
                                    <td class="p-3 text-end fw-bold" id="tdNeto">$ 0</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="p-3 text-end fw-semibold foot-colspan">Impuestos (IVA 19%):</td>
                                    <td class="p-3 text-end fw-bold" id="tdIva">$ 0</td>
                                    <td></td>
                                </tr>
                                <tr class="table-active">
                                    <td colspan="5" class="p-3 text-end fw-bold text-dark foot-colspan">TOTAL A PAGAR:</td>
                                    <td class="p-3 text-end fw-black text-primary fs-5" id="tdTotal">$ 0</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div id="errorTabla" class="alert alert-danger mt-3 d-none"></div>
                </div>
            </div>

            <!-- BOTÓN DE ENVÍO -->
            <div class="d-flex justify-content-end pb-5">
                <button type="submit" id="btnSubmitForm" class="btn btn-primary px-5 py-3 rounded-3 fw-bold shadow d-flex align-items-center justify-content-center gap-2">
                    <span class="spinner-border spinner-border-sm d-none" id="btnSpinner" role="status" aria-hidden="true"></span>
                    <span class="btn-text">Guardar Correcciones y Reenviar</span>
                    <i class="bi bi-send-fill fs-6" id="btnIcon"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
        const listadoProveedores = <?= json_encode($listado_prov_json) ?>;
        const mapaTiposCompra = <?= json_encode($mapa_tipos) ?>;
        const mapaRequiereCotizacion = <?= json_encode($mapa_requiere_cotizacion) ?>;
        
        // --- TRANSICIÓN DE PANELES ANIMADOS ---
        function toggleSlidePanel(id, visible) {
            const panel = document.getElementById(id);
            if (!panel) return;
            if (visible) {
                panel.classList.remove('d-none');
                panel.offsetHeight; 
                panel.classList.add('show');
            } else {
                panel.classList.remove('show');
                setTimeout(() => {
                    if (!panel.classList.contains('show')) {
                        panel.classList.add('d-none');
                    }
                }, 400);
            }
        }

        function evaluarFormularioReactivo() {
            const select = document.getElementById('selTipoCompra');
            
            const panelProv = document.getElementById('panel-proveedor');
            const panelCrit = document.getElementById('panel-criterios');
            const panelSuministro = document.getElementById('panel-suministro');
            
            const inpSum = document.getElementById('inp_id_contrato_suministro');
            const colsCm = document.querySelectorAll('.col-cm');
            const inputsCm = document.querySelectorAll('.input-cm');
            const footColspans = document.querySelectorAll('.foot-colspan');

            const divMonto = document.getElementById('divMontoDisponible');
            const inpMonto = document.getElementById('inpMontoDisponible');
            const requiereCot = select.value ? (mapaRequiereCotizacion[select.value] || false) : false;
            const colsPrecio = document.querySelectorAll('.col-precio');

            if(!select.value) {
                toggleSlidePanel('panel-proveedor', false);
                toggleSlidePanel('panel-criterios', false);
                toggleSlidePanel('panel-suministro', false);
                inpSum.required = false;
                divMonto.classList.add('d-none');
                inpMonto.required = false;
                inpMonto.value = '';
                return;
            }
            const codigoTC = mapaTiposCompra[select.value];

            if (requiereCot) {
                divMonto.classList.remove('d-none');
                inpMonto.required = true;
                colsPrecio.forEach(el => el.classList.add('d-none'));
            } else {
                divMonto.classList.add('d-none');
                inpMonto.required = false;
                colsPrecio.forEach(el => el.classList.remove('d-none'));
            }
            
            // Lógica Panel Proveedor
            const tiposDirectos = ['TRATO_DIRECTO', 'CONVENIO_MARCO', 'CONTRATO_SUMINISTRO', 'SISTEMA_DIRECTO', 'MENOR_3UTM'];
            const esDirecto = tiposDirectos.includes(codigoTC);
            toggleSlidePanel('panel-proveedor', esDirecto);
            if (!esDirecto) {
                document.getElementById('selProveedor').value = '';
                evaluarProveedorNuevo();
                document.getElementById('provResumenVacio').classList.remove('d-none');
                document.getElementById('provResumenDetalle').classList.add('d-none');
                document.getElementById('btnSelectProvText').innerText = 'Seleccionar Proveedor';
            }

            // Lógica Criterios
            const esLicitacion = (codigoTC === 'LICITACION');
            toggleSlidePanel('panel-criterios', esLicitacion);
            if (esLicitacion) {
                if(document.getElementById('tbodyCriterios').children.length === 0) agregarCriterio();
            }

            // Lógica Suministro
            const esSuministro = (codigoTC === 'CONTRATO_SUMINISTRO');
            toggleSlidePanel('panel-suministro', esSuministro);
            inpSum.required = esSuministro;

            // Lógica Columnas Convenio Marco en la Tabla
            if (codigoTC === 'CONVENIO_MARCO') {
                colsCm.forEach(el => el.classList.remove('d-none'));
                inputsCm.forEach(inp => inp.required = true);
                footColspans.forEach(td => td.colSpan = 6);
            } else {
                colsCm.forEach(el => el.classList.add('d-none'));
                inputsCm.forEach(inp => { inp.required = false; inp.value = ''; });
                footColspans.forEach(td => td.colSpan = 5);
            }
        }

        // --- LÓGICA DE PROVEEDORES AUTOCOMPLETE & RUT EN MODAL ---
        let currentFocusedIndex = -1;
        let filteredItems = [];
        let selectedDuplicateId = null;
        let modalProveedorInstance = null;

        function abrirModalProveedor() {
            const currentVal = document.getElementById('selProveedor').value;
            if (currentVal && currentVal !== 'NUEVO') {
                const p = listadoProveedores.find(x => x.id == currentVal);
                if (p) {
                    document.getElementById('buscadorProv').value = `${p.rut} - ${p.razon_social}`;
                }
            } else if (currentVal === 'NUEVO') {
                document.getElementById('buscadorProv').value = 'SOLICITAR NUEVO PROVEEDOR';
            } else {
                document.getElementById('buscadorProv').value = '';
            }
            
            evaluarProveedorNuevo();
            
            if (!modalProveedorInstance) {
                modalProveedorInstance = new bootstrap.Modal(document.getElementById('modalSeleccionarProveedor'));
            }
            modalProveedorInstance.show();
        }

        function confirmarSeleccionProveedor() {
            const val = document.getElementById('selProveedor').value;
            
            if (!val) {
                alert("⚠️ Por favor, busque y seleccione un proveedor, o elija pre-registrar uno nuevo.");
                return;
            }
            
            if (val === 'NUEVO') {
                const inpRut = document.getElementById('inpNuevoProvRut');
                const inpNombre = document.getElementById('inpNuevoProvNombre');
                const inpFicha = document.getElementById('inpFichaProveedor');
                
                inpRut.classList.remove('is-invalid');
                inpNombre.classList.remove('is-invalid');
                inpFicha.classList.remove('is-invalid');
                
                let errProv = false;
                if (!inpRut.value.trim() || !validateRut(inpRut.value)) {
                    inpRut.classList.add('is-invalid');
                    errProv = true;
                }
                if (!inpNombre.value.trim()) {
                    inpNombre.classList.add('is-invalid');
                    errProv = true;
                }
                if (inpFicha.files.length === 0) {
                    inpFicha.classList.add('is-invalid');
                    errProv = true;
                }
                
                if (errProv) {
                    alert("⚠️ Faltan datos del nuevo proveedor o el RUT es inválido. Por favor, corríjalos antes de confirmar.");
                    return;
                }
            }
            
            // Actualizar tarjeta de resumen
            const resumenVacio = document.getElementById('provResumenVacio');
            const resumenDetalle = document.getElementById('provResumenDetalle');
            const btnSelectProvText = document.getElementById('btnSelectProvText');
            
            if (val === 'NUEVO') {
                const rutVal = document.getElementById('inpNuevoProvRut').value;
                const nombreVal = document.getElementById('inpNuevoProvNombre').value;
                const direccionVal = document.getElementById('inpNuevoProvDireccion').value;
                
                document.getElementById('provResumenRazonSocial').innerText = nombreVal + ' (Sugerido)';
                document.getElementById('provResumenRut').innerText = rutVal;
                document.getElementById('provResumenDireccion').innerText = direccionVal ? direccionVal : 'Sin dirección provista';
                
                resumenVacio.classList.add('d-none');
                resumenDetalle.classList.remove('d-none');
                btnSelectProvText.innerText = 'Cambiar Proveedor';
            } else {
                const p = listadoProveedores.find(x => x.id == val);
                if (p) {
                    document.getElementById('provResumenRazonSocial').innerText = p.razon_social;
                    document.getElementById('provResumenRut').innerText = p.rut;
                    document.getElementById('provResumenDireccion').innerText = 'Proveedor registrado en el Directorio Municipal';
                    
                    resumenVacio.classList.add('d-none');
                    resumenDetalle.classList.remove('d-none');
                    btnSelectProvText.innerText = 'Cambiar Proveedor';
                }
            }
            
            if (modalProveedorInstance) {
                modalProveedorInstance.hide();
            }
        }

        function getFilteredList(query) {
            const q = query.toLowerCase().trim();
            if (!q) return listadoProveedores;
            return listadoProveedores.filter(p => 
                p.rut.toLowerCase().includes(q) || 
                p.razon_social.toLowerCase().includes(q)
            );
        }

        function renderProvDropdown(items) {
            const dropdown = document.getElementById('dropdownProv');
            dropdown.innerHTML = '';
            
            // Opción nuevo
            const optNew = document.createElement('div');
            optNew.className = 'custom-select-option text-primary-emphasis fw-bold border-bottom';
            optNew.innerHTML = '➕ SOLICITAR NUEVO PROVEEDOR (Requiere Ficha)';
            optNew.onclick = () => selectProvider('NUEVO', 'SOLICITAR NUEVO PROVEEDOR', '');
            dropdown.appendChild(optNew);
            
            // Frecuentes
            const frecuentes = items.filter(p => p.frecuente);
            if (frecuentes.length > 0) {
                const header = document.createElement('div');
                header.className = 'custom-select-group-header';
                header.innerText = '⭐ Proveedores Frecuentes';
                dropdown.appendChild(header);
                
                frecuentes.forEach(p => {
                    const opt = document.createElement('div');
                    opt.className = 'custom-select-option';
                    opt.innerHTML = `<strong>${escapeHTML(p.rut)}</strong> - ${escapeHTML(p.razon_social)}`;
                    opt.onclick = () => selectProvider(p.id, p.razon_social, p.rut);
                    dropdown.appendChild(opt);
                });
            }
            
            // Directorio Municipal
            const otros = items.filter(p => !p.frecuente);
            if (otros.length > 0) {
                const header = document.createElement('div');
                header.className = 'custom-select-group-header';
                header.innerText = '🏢 Directorio Municipal Completo';
                dropdown.appendChild(header);
                
                otros.forEach(p => {
                    const opt = document.createElement('div');
                    opt.className = 'custom-select-option';
                    opt.innerHTML = `<strong>${escapeHTML(p.rut)}</strong> - ${escapeHTML(p.razon_social)}`;
                    opt.onclick = () => selectProvider(p.id, p.razon_social, p.rut);
                    dropdown.appendChild(opt);
                });
            }
            
            if (items.length === 0) {
                const noRes = document.createElement('div');
                noRes.className = 'custom-select-option text-muted text-center';
                noRes.innerText = 'No se encontraron proveedores';
                dropdown.appendChild(noRes);
            }
        }

        function showProvDropdown() {
            const dropdown = document.getElementById('dropdownProv');
            dropdown.classList.add('show');
            currentFocusedIndex = -1;
            
            const selectVal = document.getElementById('selProveedor').value;
            const buscador = document.getElementById('buscadorProv');
            if (selectVal === 'NUEVO') {
                buscador.value = '';
            }
            
            if (selectVal && selectVal !== 'NUEVO') {
                renderProvDropdown(listadoProveedores);
            } else {
                filterProvDropdown();
            }
        }

        function filterProvDropdown() {
            const query = document.getElementById('buscadorProv').value;
            const selectVal = document.getElementById('selProveedor').value;
            
            if (query.trim() === '') {
                document.getElementById('selProveedor').value = '';
                evaluarProveedorNuevo();
            }
            
            if (selectVal && selectVal !== 'NUEVO') {
                const p = listadoProveedores.find(x => x.id == selectVal);
                if (p && `${p.rut} - ${p.razon_social}` === query) {
                    renderProvDropdown(listadoProveedores);
                    return;
                }
            }
            filteredItems = getFilteredList(query);
            renderProvDropdown(filteredItems);
        }

        function selectProvider(id, name, rut) {
            document.getElementById('selProveedor').value = id;
            document.getElementById('buscadorProv').value = id ? (id === 'NUEVO' ? name : `${rut} - ${name}`) : '';
            document.getElementById('dropdownProv').classList.remove('show');
            evaluarProveedorNuevo();
        }

        function evaluarProveedorNuevo() {
            const val = document.getElementById('selProveedor').value;
            const panel = document.getElementById('panelNuevoProv');
            const reqInputs = panel.querySelectorAll('.inp-nuevo-prov, #inpFichaProveedor');
            
            if (val === 'NUEVO') {
                toggleSlidePanel('panelNuevoProv', true);
                reqInputs.forEach(i => i.setAttribute('required', 'required'));
            } else {
                toggleSlidePanel('panelNuevoProv', false);
                reqInputs.forEach(i => {
                    i.removeAttribute('required');
                    i.value = '';
                });
                const icon = document.getElementById('rutStatusIcon');
                if (icon) {
                    icon.innerText = '➖';
                    icon.className = 'input-group-text bg-light text-secondary py-0';
                }
                const msg = document.getElementById('rutValidationMsg');
                if (msg) msg.classList.add('d-none');
                const dupAlert = document.getElementById('rutDuplicateAlert');
                if (dupAlert) dupAlert.classList.add('d-none');
                selectedDuplicateId = null;
            }
        }

        // Formateador y validador de RUT
        function formatRut(rut) {
            let value = rut.replace(/[^0-9kK]/g, '');
            if (value.length <= 1) return value;
            let body = value.slice(0, -1);
            let dv = value.slice(-1).toUpperCase();
            
            let formatted = '';
            while (body.length > 3) {
                formatted = '.' + body.slice(-3) + formatted;
                body = body.slice(0, -3);
            }
            formatted = body + formatted + '-' + dv;
            return formatted;
        }

        function validateRut(rut) {
            let clean = rut.replace(/[^0-9kK]/g, '');
            if (clean.length < 8) return false;
            let body = clean.slice(0, -1);
            let dv = clean.slice(-1).toUpperCase();
            
            let sum = 0;
            let mul = 2;
            for (let i = body.length - 1; i >= 0; i--) {
                sum += mul * parseInt(body.charAt(i));
                mul = mul === 7 ? 2 : mul + 1;
            }
            let res = 11 - (sum % 11);
            let expectedDv = res === 11 ? '0' : (res === 10 ? 'K' : res.toString());
            return dv === expectedDv;
        }

        function handleRutInput(input) {
            let raw = input.value;
            let formatted = formatRut(raw);
            input.value = formatted;
            
            const icon = document.getElementById('rutStatusIcon');
            const msg = document.getElementById('rutValidationMsg');
            const dupAlert = document.getElementById('rutDuplicateAlert');
            
            if (!formatted) {
                icon.innerText = '➖';
                icon.className = 'input-group-text bg-light text-secondary py-0';
                msg.classList.add('d-none');
                dupAlert.classList.add('d-none');
                selectedDuplicateId = null;
                return;
            }
            
            const isValid = validateRut(formatted);
            if (isValid) {
                icon.innerText = '✅';
                icon.className = 'input-group-text bg-success-subtle text-success py-0';
                msg.classList.add('d-none');
                
                // Comprobar duplicado
                const cleanRut = formatted.replace(/[^0-9kK]/g, '').toLowerCase();
                const dup = listadoProveedores.find(p => p.rut.replace(/[^0-9kK]/g, '').toLowerCase() === cleanRut);
                if (dup) {
                    document.getElementById('duplicateProvName').innerText = dup.razon_social;
                    dupAlert.classList.remove('d-none');
                    selectedDuplicateId = dup.id;
                } else {
                    dupAlert.classList.add('d-none');
                    selectedDuplicateId = null;
                }
            } else {
                icon.innerText = '❌';
                icon.className = 'input-group-text bg-danger-subtle text-danger py-0';
                msg.classList.remove('d-none');
                dupAlert.classList.add('d-none');
                selectedDuplicateId = null;
            }
        }

        function selectDuplicateProvider() {
            if (selectedDuplicateId) {
                const p = listadoProveedores.find(x => x.id == selectedDuplicateId);
                if (p) {
                    selectProvider(p.id, p.razon_social, p.rut);
                    // Reset campos
                    document.getElementById('inpNuevoProvRut').value = '';
                    document.getElementById('inpNuevoProvNombre').value = '';
                    document.getElementById('inpNuevoProvDireccion').value = '';
                    document.getElementById('inpFichaProveedor').value = '';
                    document.getElementById('rutStatusIcon').innerText = '➖';
                    document.getElementById('rutStatusIcon').className = 'input-group-text bg-light text-secondary py-0';
                    document.getElementById('rutDuplicateAlert').classList.add('d-none');
                }
            }
        }

        // Cerrar dropdown al hacer click afuera
        document.addEventListener('click', function(e) {
            const container = document.getElementById('containerProveedor');
            if (container && !container.contains(e.target)) {
                document.getElementById('dropdownProv').classList.remove('show');
                
                const val = document.getElementById('selProveedor').value;
                const text = document.getElementById('buscadorProv').value;
                if (!val) {
                    document.getElementById('buscadorProv').value = '';
                } else if (val === 'NUEVO') {
                    document.getElementById('buscadorProv').value = 'SOLICITAR NUEVO PROVEEDOR';
                } else {
                    const p = listadoProveedores.find(x => x.id == val);
                    if (p && `${p.rut} - ${p.razon_social}` !== text) {
                        document.getElementById('buscadorProv').value = `${p.rut} - ${p.razon_social}`;
                    }
                }
            }
        });

        // Navegación por teclado en el dropdown
        document.addEventListener('DOMContentLoaded', () => {
            const buscador = document.getElementById('buscadorProv');
            if (buscador) {
                buscador.addEventListener('keydown', function(e) {
                    const dropdown = document.getElementById('dropdownProv');
                    if (!dropdown.classList.contains('show')) {
                        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                            dropdown.classList.add('show');
                        }
                        return;
                    }
                    
                    const options = dropdown.querySelectorAll('.custom-select-option:not(.custom-select-group-header)');
                    if (options.length === 0) return;
                    
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        currentFocusedIndex = (currentFocusedIndex + 1) % options.length;
                        updateOptionFocus(options);
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        currentFocusedIndex = (currentFocusedIndex - 1 + options.length) % options.length;
                        updateOptionFocus(options);
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        if (currentFocusedIndex >= 0 && currentFocusedIndex < options.length) {
                            options[currentFocusedIndex].click();
                        }
                    } else if (e.key === 'Escape') {
                        dropdown.classList.remove('show');
                    }
                });
            }
        });

        function updateOptionFocus(options) {
            options.forEach((opt, idx) => {
                if (idx === currentFocusedIndex) {
                    opt.classList.add('active');
                    opt.scrollIntoView({ block: 'nearest' });
                } else {
                    opt.classList.remove('active');
                }
            });
        }

        // --- CRITERIOS ---
        let countCriterios = 0;
        const criteriosAnteriores = <?= json_encode($criterios_old) ?>;

        function agregarCriterio(data = null) {
            countCriterios++;
            let valDesc = data ? escapeHTML(data.desc) : '';
            let valPorc = data ? data.porc : '';

            const tr = `
                <tr class="align-middle">
                    <td class="p-2 text-center"><input type="number" name="crit_num[]" value="${countCriterios}" class="form-control form-control-sm text-center bg-light fw-semibold" style="width: 50px;" readonly></td>
                    <td class="p-2"><input type="text" name="crit_desc[]" class="form-control form-control-sm bg-white" value="${valDesc}" required></td>
                    <td class="p-2"><input type="number" name="crit_porc[]" min="1" max="100" class="form-control form-control-sm text-center input-porc fw-bold text-purple" value="${valPorc}" required oninput="calcCriterios()" style="width: 80px; margin: 0 auto;"></td>
                    <td class="p-2 text-center"><button type="button" onclick="this.closest('tr').remove(); calcCriterios();" class="btn btn-outline-danger btn-sm border-0"><i class="bi bi-trash"></i></button></td>
                </tr>
            `;
            document.getElementById('tbodyCriterios').insertAdjacentHTML('beforeend', tr);
            calcCriterios();
        }

        function calcCriterios() {
            let total = 0;
            document.querySelectorAll('.input-porc').forEach(inp => { total += parseFloat(inp.value) || 0; });
            const td = document.getElementById('tdTotalCriterio');
            td.innerText = total + "%";
            td.className = (total === 100) ? 'p-2 text-center fw-bold text-success' : 'p-2 text-center fw-bold text-danger';
            return total;
        }

        // --- ITEMS ---
        const cuentasDisponibles = <?= json_encode($cuentas_disponibles) ?>;
        const itemsAnteriores = <?= json_encode($items_old) ?>;
        const formatter = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', minimumFractionDigits: 0 });

        function escapeHTML(str) { return str.replace(/[&<>'"]/g, tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag])); }

        function crearFilaHTML(data = null) {
            let valDesc = data ? escapeHTML(data.desc) : '';
            let valIdCm = data ? escapeHTML(data.id_cm) : '';
            let valCant = data ? parseFloat(data.cant) : 1;
            let valPrec = data ? parseFloat(data.prec) : 0; 
            let valCuenta = data ? data.cuenta_id : '';
            let valUni = data ? data.uni : 'UNIDAD';

            let options = '<option value="">Sel. Cuenta...</option>';
            cuentasDisponibles.forEach(c => {
                let ag = c.ag_codigo ? `[${c.ag_codigo}] ` : '';
                let sel = (c.id == valCuenta) ? 'selected' : '';
                options += `<option value="${c.id}" ${sel}>${c.codigo} ${ag}- ${c.nombre}</option>`;
            });

            const arrUnidades = ['UNIDAD', 'GLOBAL', 'CAJA', 'LITROS', 'MESES'];
            let uniOptions = '';
            arrUnidades.forEach(u => {
                let sel = (u === valUni) ? 'selected' : '';
                uniOptions += `<option value="${u}" ${sel}>${u}</option>`;
            });

            return `
                <tr class="align-middle">
                    <td class="p-2"><select name="cuenta_id[]" class="form-select form-select-sm font-monospace select-cuenta" style="max-width: 250px;">${options}</select></td>
                    <td class="p-2"><textarea name="desc[]" rows="1" class="form-control form-control-sm bg-transparent" style="min-height: 38px; resize: none;">${valDesc}</textarea></td>
                    
                    <td class="p-2 col-cm d-none"><input type="text" name="id_producto_cm[]" value="${valIdCm}" class="form-control form-control-sm font-monospace text-center input-cm text-primary bg-primary-subtle"></td>

                    <td class="p-2"><select name="uni[]" class="form-select form-select-sm">${uniOptions}</select></td>
                    <td class="p-2"><input type="number" name="cant[]" value="${valCant}" min="0.01" step="0.01" class="form-control form-control-sm text-center fw-bold input-cant bg-transparent" oninput="calc()" style="width: 80px; margin: 0 auto;"></td>
                    <td class="p-2 col-precio"><input type="number" name="prec[]" value="${valPrec}" min="0" class="form-control form-control-sm text-end input-prec bg-transparent" oninput="calc()" style="width: 120px; margin-left: auto;"></td>
                    <td class="p-2 text-end fw-bold text-secondary span-total text-nowrap">$ 0</td>
                    <td class="p-2 text-center"><button type="button" onclick="del(this)" class="btn btn-outline-danger btn-sm border-0"><i class="bi bi-trash"></i></button></td>
                </tr>
            `;
        }

        function agregarFila(data = null) {
            document.getElementById('tbodyItems').insertAdjacentHTML('beforeend', crearFilaHTML(data));
            document.getElementById('errorTabla').classList.add('d-none');
            evaluarFormularioReactivo();
            calc();
        }

        function del(btn) {
            if (document.getElementById('tbodyItems').rows.length > 1) {
                btn.closest('tr').remove(); calc();
            } else {
                alert("La solicitud debe tener al menos un ítem.");
            }
        }

        function calc() {
            let totalNeto = 0, totalIva = 0, totalBruto = 0;
            const ivaRate = 0.19;

            const select = document.getElementById('selTipoCompra');
            const requiereCot = select && select.value ? (mapaRequiereCotizacion[select.value] || false) : false;

            if (requiereCot) {
                const inpMonto = document.getElementById('inpMontoDisponible');
                totalNeto = parseFloat(inpMonto.value) || 0;
                totalBruto = totalNeto * (1 + ivaRate);
                
                document.querySelectorAll('#tbodyItems tr').forEach(row => {
                    row.querySelector('.span-total').innerText = formatter.format(0);
                });
            } else {
                document.querySelectorAll('#tbodyItems tr').forEach(row => {
                    let cant = parseFloat(row.querySelector('.input-cant').value) || 0;
                    let prec = parseFloat(row.querySelector('.input-prec').value) || 0;
                    let lineaNeto = cant * prec; 
                    let lineaBruto = lineaNeto * (1 + ivaRate);

                    row.querySelector('.span-total').innerText = formatter.format(lineaBruto);
                    totalNeto += lineaNeto;
                    totalBruto += lineaBruto;
                });
            }

            totalIva = totalBruto - totalNeto;
            document.getElementById('tdNeto').innerText = formatter.format(totalNeto);
            document.getElementById('tdIva').innerText = formatter.format(totalIva);
            document.getElementById('tdTotal').innerText = formatter.format(totalBruto);
        }

        function validarFormulario() {
            let errorItems = false;
            const select = document.getElementById('selTipoCompra');
            const codigoTC = select.value ? mapaTiposCompra[select.value] : '';
            const requiereCot = select.value ? (mapaRequiereCotizacion[select.value] || false) : false;
            const divError = document.getElementById('errorTabla');
            
            divError.classList.add('d-none');

            // Si requiere cotización, validar que el monto disponible neto esté ingresado
            if (requiereCot) {
                const inpMonto = document.getElementById('inpMontoDisponible');
                inpMonto.classList.remove('is-invalid');
                if (parseFloat(inpMonto.value) <= 0 || !inpMonto.value) {
                    inpMonto.classList.add('is-invalid');
                    divError.innerHTML = "<strong>⚠️ Faltan datos requeridos:</strong> Debe ingresar un Monto Disponible Neto válido para la cotización.";
                    divError.classList.remove('d-none');
                    inpMonto.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return false;
                }
            }

            document.querySelectorAll('#tbodyItems tr').forEach(row => {
                const cant = row.querySelector('.input-cant');
                const prec = row.querySelector('.input-prec');
                const cuenta = row.querySelector('.select-cuenta');
                const inpCm = row.querySelector('.input-cm');
                
                row.querySelectorAll('.is-invalid').forEach(e => e.classList.remove('is-invalid'));
                
                if (parseFloat(cant.value) <= 0 || !cant.value) { cant.classList.add('is-invalid'); errorItems = true; }
                if (!requiereCot) {
                    if (parseFloat(prec.value) <= 0 || !prec.value) { prec.classList.add('is-invalid'); errorItems = true; }
                }
                if (cuenta.value === "") { cuenta.classList.add('is-invalid'); errorItems = true; }
                if (codigoTC === 'CONVENIO_MARCO' && inpCm.value.trim() === "") {
                    inpCm.classList.add('is-invalid'); errorItems = true;
                }
            });

            if (errorItems) {
                divError.innerHTML = "<strong>⚠️ Faltan datos obligatorios:</strong> Por favor complete todos los campos obligatorios del detalle de productos.";
                divError.classList.remove('d-none');
                divError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
            
            if (codigoTC === 'LICITACION') {
                const sumaCriterios = calcCriterios();
                if (sumaCriterios !== 100) {
                    document.getElementById('errorCriterios').classList.remove('d-none');
                    document.getElementById('panel-criterios').scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return false;
                }
            }
            // Validar proveedor nuevo
            const selProvVal = document.getElementById('selProveedor').value;
            if (selProvVal === 'NUEVO') {
                const inpRut = document.getElementById('inpNuevoProvRut');
                const inpNombre = document.getElementById('inpNuevoProvNombre');
                const inpFicha = document.getElementById('inpFichaProveedor');
                
                inpRut.classList.remove('is-invalid');
                inpNombre.classList.remove('is-invalid');
                inpFicha.classList.remove('is-invalid');
                
                let errProv = false;
                if (!inpRut.value.trim() || !validateRut(inpRut.value)) {
                    inpRut.classList.add('is-invalid');
                    errProv = true;
                }
                if (!inpNombre.value.trim()) {
                    inpNombre.classList.add('is-invalid');
                    errProv = true;
                }
                if (inpFicha.files.length === 0) {
                    inpFicha.classList.add('is-invalid');
                    errProv = true;
                }
                
                if (errProv) {
                    divError.innerHTML = "<strong>⚠️ Faltan datos del Proveedor Nuevo:</strong> Por favor ingrese un RUT válido, Razón Social y suba el archivo PDF de la Ficha del Proveedor.";
                    divError.classList.remove('d-none');
                    divError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    document.getElementById('panelNuevoProv').scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return false;
                }
            }

            const btn = document.getElementById('btnSubmitForm');
            if (btn) {
                const text = btn.querySelector('.btn-text');
                const spinner = document.getElementById('btnSpinner');
                const icon = document.getElementById('btnIcon');
                setTimeout(() => {
                    btn.disabled = true;
                    btn.classList.add('disabled');
                    if (text) text.innerText = 'Guardando correcciones...';
                    if (spinner) spinner.classList.remove('d-none');
                    if (icon) icon.classList.add('d-none');
                }, 5);
            }

            return true;
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (itemsAnteriores.length > 0) {
                itemsAnteriores.forEach(item => agregarFila(item)); calc();
            } else {
                agregarFila(); 
            }

            if (criteriosAnteriores.length > 0) {
                criteriosAnteriores.forEach(crit => agregarCriterio(crit)); calcCriterios();
            }

            // Inicializar tarjeta de resumen de proveedor
            const preSelVal = document.getElementById('selProveedor').value;
            if (preSelVal && preSelVal !== 'NUEVO') {
                const p = listadoProveedores.find(x => x.id == preSelVal);
                if (p) {
                    document.getElementById('provResumenRazonSocial').innerText = p.razon_social;
                    document.getElementById('provResumenRut').innerText = p.rut;
                    document.getElementById('provResumenDireccion').innerText = 'Proveedor registrado en el Directorio Municipal';
                    
                    document.getElementById('provResumenVacio').classList.add('d-none');
                    document.getElementById('provResumenDetalle').classList.remove('d-none');
                    document.getElementById('btnSelectProvText').innerText = 'Cambiar Proveedor';
                }
            } else if (preSelVal === 'NUEVO') {
                const rutVal = document.getElementById('inpNuevoProvRut').value;
                const nombreVal = document.getElementById('inpNuevoProvNombre').value;
                const direccionVal = document.getElementById('inpNuevoProvDireccion').value;
                
                document.getElementById('provResumenRazonSocial').innerText = nombreVal + ' (Sugerido)';
                document.getElementById('provResumenRut').innerText = rutVal;
                document.getElementById('provResumenDireccion').innerText = direccionVal ? direccionVal : 'Sin dirección provista';
                
                document.getElementById('provResumenVacio').classList.add('d-none');
                document.getElementById('provResumenDetalle').classList.remove('d-none');
                document.getElementById('btnSelectProvText').innerText = 'Cambiar Proveedor';
            }

            evaluarFormularioReactivo();
            evaluarProveedorNuevo();
        });
    </script>
</body>
</html>