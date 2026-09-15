<?php 
// nueva_solicitud.php - Vista UI Renovada SaaS Clean Minimalist (Optimizada V5.1)
require_once __DIR__ . '/nueva_solicitud_controller.php'; 

$listado_prov_json = [];
foreach($mis_proveedores as $p) {
    $listado_prov_json[] = [
        'id' => (int)$p['id'],
        'rut' => $p['rut'],
        'razon_social' => $p['razon_social'],
        'direccion' => $p['direccion'] ?? '',
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
            'direccion' => $p['direccion'] ?? '',
            'frecuente' => false
        ];
    }
}

$user_name = $_SESSION['user_name'] ?? 'Usuario';
$user_rol = $_SESSION['user_rol'] ?? '';
$es_jefe = $_SESSION['es_jefe'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php 
    $titulo_pagina = "Nueva Solicitud OPI - Sistema OPI";
    include __DIR__ . '/head.php'; 
    ?>
</head>
<body>

    <?php include __DIR__ . '/nav.php'; ?>

    <main class="saas-container">
        
        <!-- HEADER -->
        <div class="page-header-row">
            <div class="page-title">
                <div class="breadcrumbs">
                    <a href="mis_solicitudes.php">Mis Solicitudes</a>
                    <i class="bi bi-chevron-right" style="font-size: 9px;"></i>
                    <span class="current">Nueva Solicitud OPI</span>
                </div>
                <h2>Nueva Orden de Pedido Interno</h2>
                <p>Ingrese los antecedentes, justificación técnica y desglose de ítems requeridos.</p>
            </div>

            <div>
                <a href="mis_solicitudes.php" class="btn-saas btn-saas-secondary">
                    <i class="bi bi-x-lg"></i> Cancelar
                </a>
            </div>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-4 rounded-3 shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div><?= htmlspecialchars($mensaje) ?></div>
            </div>
        <?php endif; ?>

        <?php if (!$centro_costo): ?>
            <div class="alert alert-warning text-center p-5 rounded-4 shadow-sm mb-4" role="alert">
                <i class="bi bi-exclamation-circle fs-1 text-warning mb-3 d-block"></i>
                <h4 class="alert-heading fw-bold">Sin Presupuesto Asignado</h4>
                <p class="mb-0">Contacte al área de Presupuesto para configurar el Centro de Costo de su Unidad.</p>
            </div>
        <?php else: ?>

        <!-- BANNER DE CENTRO DE COSTO ASIGNADO -->
        <div class="cc-info-card">
            <div class="cc-meta-badge">
                <div class="cc-icon-box"><i class="bi bi-bank"></i></div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Imputación Presupuestaria de Unidad</div>
                    <div style="font-size: 15px; font-weight: 800; color: var(--text-main);"><?= htmlspecialchars($centro_costo['nombre']) ?></div>
                </div>
            </div>

            <div style="background: var(--bg-body); border: 1px solid var(--border-color); padding: 8px 14px; border-radius: var(--radius-sm); font-size: 12.5px;">
                <span style="color: var(--text-muted);">Centro de Costos:</span>
                <strong style="color: var(--primary); font-weight: 700;">#<?= htmlspecialchars($centro_costo['codigo_cuenta']) ?></strong>
            </div>
        </div>

        <!-- FORMULARIO PRINCIPAL -->
        <form method="POST" action="nueva_solicitud.php" enctype="multipart/form-data" id="formCompra" onsubmit="return procesarEnvio(event)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="accion" value="crear">
            <input type="hidden" name="prioridad_id" value="1">

            <!-- PASO 1: ANTECEDENTES GENERALES -->
            <div class="saas-card">
                <div class="saas-card-header">
                    <div class="d-flex align-items-center">
                        <span class="step-pill">1</span>
                        <div>
                            <h3 style="font-size: 15px; font-weight: 700; margin: 0;">Antecedentes del Trámite</h3>
                            <p style="font-size: 12px; color: var(--text-muted); margin: 0;">Defina el título, justificación, tipo de compra y proyecto asociado.</p>
                        </div>
                    </div>
                </div>

                <div class="saas-card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label-saas">Título de la compra <span class="text-danger">*</span></label>
                            <input type="text" name="titulo_compra" id="inpTituloCompra" required class="form-control-saas" placeholder="Ej: Adquisición de Insumos de Oficina y Tóner para Atención Vecinal" value="<?= htmlspecialchars($post_titulo_compra) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label-saas">Los presentes bienes serán destinados a: <span class="text-danger">*</span></label>
                            <textarea name="motivo" id="inpMotivo" required rows="3" class="form-control-saas" placeholder="Indique el destino, uso y fundamentación técnica de los bienes o servicios solicitados..."><?= htmlspecialchars($post_motivo) ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label-saas">Modalidad / Tipo de Compra <span class="text-danger">*</span></label>
                            <select name="tipo_compra_id" id="selTipoCompra" required class="form-select-saas" onchange="evaluarFormularioReactivo()">
                                <option value="">-- Seleccione Tipo de Compra --</option>
                                <?php foreach($tipos_compra as $t): ?>
                                    <option value="<?= $t['id'] ?>" <?= $post_tipo_compra == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label-saas">Rango de Monto Estimado (Art. 10) <span class="text-danger">*</span></label>
                            <select name="rango_utm_id" id="selRangoUtm" required class="form-select-saas">
                                <option value="">-- Seleccione el Rango UTM --</option>
                                <?php foreach($rangos_utm as $r): ?>
                                    <option value="<?= $r['id'] ?>" <?= $post_rango_utm == $r['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($r['nombre']) ?> (<?= $r['min_utm'] ?> - <?= $r['max_utm'] ? $r['max_utm'].' UTM' : 'y más' ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="p-3 bg-light border rounded-3">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label-saas">Proyecto (Plan Anual de Compras) <span class="text-danger">*</span></label>
                                        <input type="text" name="plan_compras_proyecto" id="inpPlanProyecto" required class="form-control-saas" placeholder="Nombre del programa o proyecto" value="<?= htmlspecialchars($post_plan_proyecto) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label-saas">Ítem N° (Plan Anual de Compras) <span class="text-danger">*</span></label>
                                        <input type="number" name="plan_compras_item" id="inpPlanItem" required min="1" step="1" class="form-control-saas" placeholder="Ej: 1" value="<?= htmlspecialchars($post_plan_item) ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PANEL MONTO DISPONIBLE PARA COTIZACIÓN / LICITACIÓN -->
                        <div class="col-12 d-none" id="divMontoDisponible">
                            <div style="background: var(--primary-light); border: 1px solid #bfdbfe; border-radius: var(--radius-sm); padding: 18px;">
                                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                    <label class="form-label-saas" style="color: var(--primary); margin: 0;">
                                        <i class="bi bi-cash-stack me-1"></i> Monto Máximo Estimado para Cotización <span class="text-danger">*</span>
                                    </label>
                                    <span class="badge bg-white text-primary border px-2.5 py-1" id="badgeRegimenPaso1" style="font-size: 11px; font-weight: 700;">
                                        Régimen: Valores Netos
                                    </span>
                                </div>

                                <div class="row g-3 align-items-center">
                                    <div class="col-12 col-md-5">
                                        <div class="input-group">
                                            <span class="input-group-text bg-white fw-bold">$</span>
                                            <input type="text" name="monto_disponible_neto" id="inpMontoDisponible" class="form-control fw-bold text-primary fs-6" placeholder="0" oninput="handleMontoInput(this)" value="<?= htmlspecialchars($post_monto_disponible_neto ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-7">
                                        <div class="d-flex justify-content-between p-2 bg-white rounded border small">
                                            <span>Neto: <strong id="dispPreviewNeto">$ 0</strong></span>
                                            <span>IVA (19%): <strong id="dispPreviewIva">$ 0</strong></span>
                                            <span class="text-primary fw-bold">Total: <strong id="dispPreviewTotal">$ 0</strong></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PANEL PROVEEDOR REACTIVO -->
                        <div class="col-12" id="panel-proveedor" style="display: none;">
                            <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 16px;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label-saas mb-0"><i class="bi bi-person-check me-1"></i> Proveedor Asignado / Cotizado</label>
                                    <button type="button" class="btn-saas btn-saas-secondary btn-saas-sm" onclick="abrirModalProveedor()">
                                        <i class="bi bi-search"></i> <span id="btnSelectProvText">Buscar Proveedor</span>
                                    </button>
                                </div>

                                <div id="provResumenVacio" class="text-muted small">
                                    <em>No ha seleccionado un proveedor específico aún.</em>
                                </div>

                                <div id="provResumenDetalle" class="d-none bg-white p-3 border rounded-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold text-primary" id="provResumenRazonSocial"></div>
                                        <div class="text-muted small">RUT: <span id="provResumenRut" style="font-weight: 600;"></span></div>
                                        <div class="text-muted small" id="provResumenDireccion"></div>
                                    </div>
                                    <button type="button" class="btn btn-link text-danger p-0" onclick="deseleccionarProveedor()" title="Quitar"><i class="bi bi-trash"></i></button>
                                </div>

                                <input type="hidden" name="proveedor_id" id="hiddenProveedorId" value="<?= htmlspecialchars($post_proveedor_id ?? '') ?>">
                                <input type="hidden" name="nuevo_prov_rut" id="hiddenNuevoProvRut" value="">
                                <input type="hidden" name="nuevo_prov_nombre" id="hiddenNuevoProvNombre" value="">
                                <input type="hidden" name="nuevo_prov_direccion" id="hiddenNuevoProvDireccion" value="">
                            </div>
                        </div>

                        <!-- PANEL CONTRATO SUMINISTRO -->
                        <div class="col-12" id="panel-suministro" style="display: none;">
                            <label class="form-label-saas">ID o N° Decreto Contrato de Suministro <span class="text-danger">*</span></label>
                            <input type="text" name="id_contrato_suministro" id="inpSuministro" class="form-control-saas" placeholder="Ej: Decreto Alcaldicio N° 1234/2026" value="<?= htmlspecialchars($post_id_contrato_suministro ?? '') ?>">
                        </div>

                        <!-- PANEL LICITACIÓN / CRITERIOS -->
                        <div class="col-12" id="panel-licitacion" style="display: none;">
                            <div style="background: #fdfce8; border: 1px solid #fef08a; border-radius: var(--radius-sm); padding: 16px;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <label class="form-label-saas text-dark mb-0"><i class="bi bi-pie-chart me-1"></i> Criterios de Evaluación Técnica (Suma = 100%)</label>
                                        <p class="text-muted small mb-0" style="font-size: 11px;">Defina cómo se evaluarán las ofertas en la Licitación.</p>
                                    </div>
                                    <button type="button" class="btn-saas btn-saas-secondary btn-saas-sm" onclick="agregarCriterio()"><i class="bi bi-plus-lg"></i> Agregar Criterio</button>
                                </div>
                                <div class="table-responsive bg-white rounded border">
                                    <table class="table table-sm table-striped align-middle mb-0" style="font-size: 12.5px;">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 50px;" class="text-center">N°</th>
                                                <th>Descripción del Criterio</th>
                                                <th style="width: 120px;" class="text-center">Pond. (%)</th>
                                                <th style="width: 40px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbodyCriterios"></tbody>
                                        <tfoot>
                                            <tr class="table-light">
                                                <td colspan="2" class="text-end fw-bold">TOTAL PONDERACIÓN:</td>
                                                <td class="text-center fw-bold text-danger" id="sumaCriteriosLabel">0%</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <div id="errorCriteriosMsg" class="text-danger small fw-bold mt-2 d-none">⚠️ La suma de los criterios de evaluación debe ser exactamente 100%.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PASO 2: DESGLOSE DE ÍTEMS -->
            <div class="saas-card" id="cardItems">
                <div class="saas-card-header">
                    <div class="d-flex align-items-center">
                        <span class="step-pill">2</span>
                        <div>
                            <h3 style="font-size: 15px; font-weight: 700; margin: 0;">Desglose de Ítems Solicitados</h3>
                            <p style="font-size: 12px; color: var(--text-muted); margin: 0;">Indique los productos o servicios requeridos y asigne la cuenta presupuestaria.</p>
                        </div>
                    </div>

                    <!-- SELECTOR MAESTRO ÚNICO DE RÉGIMEN TRIBUTARIO -->
                    <div class="d-flex align-items-center gap-2">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Régimen Tributario">
                            <input type="radio" class="btn-check" name="tipo_impuesto" id="reg_neto" value="NETO" <?= ($post_tipo_impuesto === 'NETO' || empty($post_tipo_impuesto)) ? 'checked' : '' ?> onchange="cambiarRegimenImpuesto('NETO')">
                            <label class="btn btn-outline-primary btn-sm py-1 px-2.5 fw-semibold" for="reg_neto" style="font-size: 11.5px;">
                                <i class="bi bi-tag me-1"></i> Precios Sin IVA (Neto)
                            </label>

                            <input type="radio" class="btn-check" name="tipo_impuesto" id="reg_iva" value="IVA_INCLUIDO" <?= $post_tipo_impuesto === 'IVA_INCLUIDO' ? 'checked' : '' ?> onchange="cambiarRegimenImpuesto('IVA_INCLUIDO')">
                            <label class="btn btn-outline-primary btn-sm py-1 px-2.5 fw-semibold" for="reg_iva" style="font-size: 11.5px;">
                                <i class="bi bi-receipt me-1"></i> Precios Con IVA (Total)
                            </label>
                        </div>

                        <button type="button" class="btn-saas btn-saas-primary btn-saas-sm" onclick="agregarItemFila()">
                            <i class="bi bi-plus-lg"></i> Agregar Ítem
                        </button>
                    </div>
                </div>

                <div class="saas-card-body p-0">
                    <div class="table-responsive">
                        <table class="saas-items-table" id="tablaItems">
                            <thead>
                                <tr>
                                    <th>Descripción del Producto / Servicio</th>
                                    <th style="width: 130px;" class="th-cm d-none">ID CM</th>
                                    <th style="width: 105px;">Unidad</th>
                                    <th style="width: 80px; text-align: center;">Cant.</th>
                                    <th style="width: 125px; text-align: right;" class="col-precio" id="thColPrecio">Precio Unit. (Neto)</th>
                                    <th style="width: 180px;">Imputación Presupuestaria</th>
                                    <th style="width: 125px; text-align: right;">Total Línea</th>
                                    <th style="width: 45px; text-align: center;"></th>
                                </tr>
                            </thead>
                            <tbody id="tbodyItems"></tbody>
                        </table>
                    </div>

                    <div id="errorTabla" class="alert alert-danger m-3 d-none"></div>

                    <!-- TOTALES RESUMEN -->
                    <div class="p-4 bg-light border-top">
                        <div class="row justify-content-end">
                            <div class="col-12 col-md-5">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Subtotal Neto:</span>
                                    <strong id="lblSubtotalNeto" style="font-weight: 700;">$ 0</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">IVA Estimado (19%):</span>
                                    <strong id="lblIvaTotal" style="font-weight: 700;">$ 0</strong>
                                </div>
                                <div class="d-flex justify-content-between pt-2 border-top">
                                    <span class="fw-bold fs-6">Total Solicitud:</span>
                                    <strong class="fs-5 text-primary" id="lblGranTotal" style="font-weight: 800;">$ 0</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PASO 3: DOCUMENTOS Y ADJUNTOS -->
            <div class="saas-card">
                <div class="saas-card-header">
                    <div class="d-flex align-items-center">
                        <span class="step-pill">3</span>
                        <div>
                            <h3 style="font-size: 15px; font-weight: 700; margin: 0;">Documentos & Antecedentes Adjuntos</h3>
                            <p style="font-size: 12px; color: var(--text-muted); margin: 0;">Adjunte cotizaciones previas, términos de referencia o especificaciones técnicas.</p>
                        </div>
                    </div>
                </div>

                <div class="saas-card-body">
                    <!-- DROPZONE MULTI-ARCHIVO CON SUBIDA AJAX INMEDIATA -->
                    <div id="dropzone" class="dropzone-saas" tabindex="0" role="button">
                        <i class="bi bi-cloud-arrow-up text-primary fs-1 mb-2 d-block"></i>
                        <div class="fw-bold">Arrastre archivos aquí o haga clic para seleccionar</div>
                        <div class="text-muted small">Formatos permitidos: PDF, Word, Excel, JPG, PNG (Máx <?= LIMITE_ADJUNTO_MB ?>MB por archivo)</div>
                        <input type="file" name="archivos_adjuntos[]" id="inpAdjunto" multiple class="d-none" onchange="manejarSeleccionArchivos()">
                    </div>

                    <!-- LISTA DE ARCHIVOS CON BARRA DE PROGRESO -->
                    <div id="listaAdjuntos" class="mt-3"></div>
                </div>
            </div>

            <!-- BOTONES DE ACCIÓN FINAL -->
            <div class="d-flex justify-content-between align-items-center gap-3 pt-2">
                <a href="mis_solicitudes.php" class="btn-saas btn-saas-secondary">
                    <i class="bi bi-arrow-left"></i> Volver a Mis Solicitudes
                </a>
                <button type="submit" id="btnSubmit" class="btn-saas btn-saas-primary" style="padding: 10px 24px; font-size: 14px;">
                    <span id="btnText">Emitir Requerimiento OPI</span>
                    <div id="btnSpinner" class="spinner-border spinner-border-sm text-light d-none" role="status"></div>
                    <i id="btnIcon" class="bi bi-send-check"></i>
                </button>
            </div>
        </form>

        <?php endif; ?>
    </main>

    <!-- MODAL SELECCIÓN / CREACIÓN DE PROVEEDOR -->
    <div class="modal fade" id="modalProveedor" tabindex="-1" aria-labelledby="modalProveedorLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-3 shadow border-0">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="modalProveedorLabel">Búsqueda & Registro de Proveedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label-saas">Buscar por RUT o Razón Social</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                            <input type="text" id="filtroProvInput" class="form-control" placeholder="Escriba RUT o nombre..." oninput="filtrarProveedores(this.value)">
                        </div>
                    </div>

                    <div style="max-height: 240px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: var(--radius-sm); margin-bottom: 20px;" id="listaProveedoresContainer">
                        <!-- Render dinámico -->
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small text-muted">¿El proveedor no está registrado en el sistema?</span>
                        <button type="button" class="btn-saas btn-saas-secondary btn-saas-sm" onclick="toggleFormNuevoProv()">
                            <i class="bi bi-plus-lg"></i> Pre-registrar Nuevo Proveedor
                        </button>
                    </div>

                    <!-- FORMULARIO DE NUEVO PROVEEDOR -->
                    <div id="formNuevoProv" class="d-none mt-3 p-3 bg-light border rounded-3">
                        <h6 class="fw-bold small mb-2 text-primary"><i class="bi bi-person-plus me-1"></i> Datos del Nuevo Proveedor</h6>
                        <p class="text-muted small mb-3" style="font-size: 11px;">Ingrese los datos para pre-registro. <b>Es obligatorio</b> adjuntar la Ficha o Cotización formal en PDF.</p>
                        <div class="row g-2">
                            <div class="col-md-5">
                                <label class="form-label text-secondary small fw-bold mb-1">RUT <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <input type="text" id="nProvRut" placeholder="Ej: 76.123.456-7" class="form-control form-control-sm" oninput="handleRutInput(this)">
                                    <span class="input-group-text bg-white text-secondary py-0" id="rutStatusIcon" style="font-size: 11px; min-width: 32px; justify-content: center;">➖</span>
                                </div>
                                <div id="rutValidationMsg" class="small text-danger d-none mt-1" style="font-size: 10px;">⚠️ El RUT ingresado es inválido.</div>
                                <div id="rutDuplicateAlert" class="alert alert-warning p-2 mt-2 mb-0 small d-none" style="font-size: 11px;">
                                    <strong>⚠️ Ya registrado:</strong> <span id="duplicateProvName" class="fw-bold"></span>.
                                    <button type="button" class="btn btn-xs btn-warning py-0 px-2 fw-bold text-dark border-0 ms-2" onclick="selectDuplicateProvider()" style="font-size: 10px;">Seleccionar</button>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label text-secondary small fw-bold mb-1">Razón Social / Nombre <span class="text-danger">*</span></label>
                                <input type="text" id="nProvNombre" placeholder="Razón Social Oficial" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold mb-1">Dirección / Contacto</label>
                                <input type="text" id="nProvDir" placeholder="Dirección comercial y comuna" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary small fw-bold mb-1">Ficha / Cotización (PDF) <span class="text-danger">*</span></label>
                                <input type="file" name="ficha_proveedor" id="nProvFicha" accept="application/pdf" class="form-control form-control-sm">
                            </div>
                            <div class="col-12 text-end mt-3">
                                <button type="button" class="btn-saas btn-saas-primary btn-saas-sm" onclick="confirmarNuevoProveedor()">Usar Este Nuevo Proveedor</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn-saas btn-saas-secondary btn-saas-sm" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL SELECCIÓN DE CUENTA PRESUPUESTARIA -->
    <div class="modal fade" id="modalSeleccionarCuenta" tabindex="-1" aria-labelledby="modalSeleccionarCuentaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-3 shadow border-0">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="modalSeleccionarCuentaLabel" style="font-size: 15px;">
                        <i class="bi bi-wallet2 text-primary me-1.5"></i> Imputación Presupuestaria de Gasto
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label-saas">Filtrar Cuenta por Código o Nombre</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                            <input type="text" id="filtroCuentaInput" class="form-control" placeholder="Buscar por código (ej: 215-22) o descripción..." oninput="filtrarCuentas(this.value)">
                        </div>
                    </div>

                    <div style="max-height: 320px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 8px;" id="listaCuentasContainer">
                        <!-- Render dinámico de cuentas -->
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn-saas btn-saas-secondary btn-saas-sm" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- DATOS EN JAVASCRIPT -->
    <script>
        const proveedoresData = <?= json_encode($listado_prov_json) ?>;
        const mapaTipos = <?= json_encode($mapa_tipos) ?>;
        const mapaRequiereCot = <?= json_encode($mapa_requiere_cotizacion) ?>;
        const cuentasPresupuestarias = <?= json_encode($cuentas_disponibles) ?>;
        const itemsPrevios = <?= json_encode($items_old) ?>;
        const criteriosPrevios = <?= json_encode($criterios_old) ?>;

        let regimenActual = '<?= $post_tipo_impuesto ?>';
        let modalProvInstance = null;
        let modalCuentaInstance = null;
        let filaCuentaActiva = null; // Guarda el botón o elemento de fila que abrió el modal
        let selectedDuplicateId = null;
        let countCriterios = 0;

        // Subida de Archivos AJAX
        let archivosSubidos = [];

        document.addEventListener('DOMContentLoaded', () => {
            modalProvInstance = new bootstrap.Modal(document.getElementById('modalProveedor'));
            modalCuentaInstance = new bootstrap.Modal(document.getElementById('modalSeleccionarCuenta'));
            
            renderProveedoresLista(proveedoresData);
            renderCuentasLista(cuentasPresupuestarias);

            if (itemsPrevios && itemsPrevios.length > 0) {
                itemsPrevios.forEach(it => agregarItemFila(it));
            } else {
                agregarItemFila();
            }

            if (criteriosPrevios && criteriosPrevios.length > 0) {
                criteriosPrevios.forEach(cr => agregarCriterio(cr));
            }

            // Drag and Drop Dropzone Init
            inicializarDropzone();

            evaluarFormularioReactivo();
        });

        const clpFormatter = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', minimumFractionDigits: 0 });
        function formatCLP(v) { return clpFormatter.format(v); }

        function toggleFormNuevoProv() {
            document.getElementById('formNuevoProv').classList.toggle('d-none');
        }

        function abrirModalProveedor() {
            modalProvInstance.show();
        }

        function renderProveedoresLista(data) {
            const cont = document.getElementById('listaProveedoresContainer');
            cont.innerHTML = '';
            if (data.length === 0) {
                cont.innerHTML = '<div class="p-3 text-center text-muted small">No se encontraron proveedores.</div>';
                return;
            }
            data.forEach(p => {
                const div = document.createElement('div');
                div.className = 'd-flex justify-content-between align-items-center p-2.5 border-bottom bg-white';
                div.style.cursor = 'pointer';
                div.innerHTML = `
                    <div>
                        <div class="fw-bold text-dark small">${escapeHtml(p.razon_social)}</div>
                        <div class="text-muted" style="font-size: 11px;">RUT: <strong>${escapeHtml(p.rut)}</strong></div>
                    </div>
                    <button type="button" class="btn-saas btn-saas-secondary btn-saas-sm">Seleccionar</button>
                `;
                div.onclick = () => seleccionarProveedor(p);
                cont.appendChild(div);
            });
        }

        function filtrarProveedores(q) {
            const needle = q.toLowerCase();
            const filtered = proveedoresData.filter(p => p.razon_social.toLowerCase().includes(needle) || p.rut.toLowerCase().includes(needle));
            renderProveedoresLista(filtered);
        }

        function seleccionarProveedor(p) {
            document.getElementById('hiddenProveedorId').value = p.id;
            document.getElementById('provResumenRazonSocial').innerText = p.razon_social;
            document.getElementById('provResumenRut').innerText = p.rut;
            document.getElementById('provResumenDireccion').innerText = p.direccion || 'Proveedor del directorio municipal';
            document.getElementById('provResumenVacio').classList.add('d-none');
            document.getElementById('provResumenDetalle').classList.remove('d-none');
            document.getElementById('btnSelectProvText').innerText = 'Cambiar';

            if (p.id === 'NUEVO') {
                document.getElementById('hiddenNuevoProvRut').value = p.rut;
                document.getElementById('hiddenNuevoProvNombre').value = p.razon_social.replace(' (Nuevo Pre-registro)', '');
                document.getElementById('hiddenNuevoProvDireccion').value = p.direccion || '';
            } else {
                document.getElementById('hiddenNuevoProvRut').value = '';
                document.getElementById('hiddenNuevoProvNombre').value = '';
                document.getElementById('hiddenNuevoProvDireccion').value = '';
            }

            modalProvInstance.hide();
        }

        function deseleccionarProveedor() {
            document.getElementById('hiddenProveedorId').value = '';
            document.getElementById('hiddenNuevoProvRut').value = '';
            document.getElementById('hiddenNuevoProvNombre').value = '';
            document.getElementById('hiddenNuevoProvDireccion').value = '';
            document.getElementById('provResumenVacio').classList.remove('d-none');
            document.getElementById('provResumenDetalle').classList.add('d-none');
            document.getElementById('btnSelectProvText').innerText = 'Buscar Proveedor';
        }

        // Validación y Formateo de RUT
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
            return body + formatted + '-' + dv;
        }

        function validateRut(rut) {
            let clean = rut.replace(/[^0-9kK]/g, '');
            if (clean.length < 8) return false;
            let body = clean.slice(0, -1);
            let dv = clean.slice(-1).toUpperCase();
            let sum = 0, mul = 2;
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
                msg.classList.add('d-none');
                dupAlert.classList.add('d-none');
                selectedDuplicateId = null;
                return;
            }

            const isValid = validateRut(formatted);
            if (isValid) {
                icon.innerText = '✅';
                msg.classList.add('d-none');
                const cleanRut = formatted.replace(/[^0-9kK]/g, '').toLowerCase();
                const dup = proveedoresData.find(p => p.rut.replace(/[^0-9kK]/g, '').toLowerCase() === cleanRut);
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
                msg.classList.remove('d-none');
                dupAlert.classList.add('d-none');
                selectedDuplicateId = null;
            }
        }

        function selectDuplicateProvider() {
            if (selectedDuplicateId) {
                const p = proveedoresData.find(x => x.id == selectedDuplicateId);
                if (p) seleccionarProveedor(p);
            }
        }

        function confirmarNuevoProveedor() {
            const rut = document.getElementById('nProvRut').value.trim();
            const nom = document.getElementById('nProvNombre').value.trim();
            const dir = document.getElementById('nProvDir').value.trim();
            const ficha = document.getElementById('nProvFicha');

            if (!rut || !validateRut(rut)) {
                alert('Debe ingresar un RUT válido para el nuevo proveedor.');
                return;
            }
            if (!nom) {
                alert('Debe ingresar la Razón Social del nuevo proveedor.');
                return;
            }
            if (ficha.files.length === 0) {
                alert('Es obligatorio adjuntar la Ficha del Proveedor o Cotización en formato PDF.');
                return;
            }
            seleccionarProveedor({ id: 'NUEVO', rut: rut, razon_social: nom + ' (Nuevo Pre-registro)', direccion: dir });
        }

        // ==========================================
        // GESTIÓN DE CUENTAS PRESUPUESTARIAS (MODAL)
        // ==========================================
        function renderCuentasLista(cuentas) {
            const cont = document.getElementById('listaCuentasContainer');
            cont.innerHTML = '';
            if (!cuentas || cuentas.length === 0) {
                cont.innerHTML = '<div class="p-4 text-center text-muted small">No hay cuentas asignadas al Centro de Costo.</div>';
                return;
            }

            cuentas.forEach(c => {
                const card = document.createElement('div');
                card.className = 'cuenta-item-card d-flex align-items-center justify-content-between';
                const agBadge = c.ag_codigo ? `<span class="badge bg-secondary-subtle text-secondary me-1.5">${escapeHtml(c.ag_codigo)}</span>` : '';
                card.innerHTML = `
                    <div class="min-w-0 pe-2">
                        <div class="d-flex align-items-center gap-1.5 mb-0.5">
                            ${agBadge}
                            <strong class="text-primary" style="font-size: 13px;">${escapeHtml(c.codigo)}</strong>
                        </div>
                        <div class="text-dark small text-truncate" style="font-weight: 500;">${escapeHtml(c.nombre)}</div>
                    </div>
                    <button type="button" class="btn-saas btn-saas-secondary btn-saas-sm shrink-0">Seleccionar</button>
                `;
                card.onclick = () => aplicarCuentaAFila(c);
                cont.appendChild(card);
            });
        }

        function filtrarCuentas(q) {
            const needle = q.toLowerCase().trim();
            const filtered = cuentasPresupuestarias.filter(c => 
                c.codigo.toLowerCase().includes(needle) || 
                c.nombre.toLowerCase().includes(needle) || 
                (c.ag_codigo && c.ag_codigo.toLowerCase().includes(needle))
            );
            renderCuentasLista(filtered);
        }

        function abrirModalSeleccionarCuenta(btn) {
            filaCuentaActiva = btn.closest('tr');
            document.getElementById('filtroCuentaInput').value = '';
            renderCuentasLista(cuentasPresupuestarias);
            modalCuentaInstance.show();
        }

        function aplicarCuentaAFila(cuenta) {
            if (!filaCuentaActiva) return;

            const hiddenInput = filaCuentaActiva.querySelector('.input-cuenta-id');
            const btn = filaCuentaActiva.querySelector('.btn-select-cuenta');
            const labelSpan = filaCuentaActiva.querySelector('.cuenta-btn-label');

            if (hiddenInput) hiddenInput.value = cuenta.id;
            if (labelSpan) {
                labelSpan.innerHTML = `<i class="bi bi-wallet2 me-1 text-primary"></i> <strong class="text-primary">${escapeHtml(cuenta.codigo)}</strong> <span class="text-muted small text-truncate d-none d-xl-inline" style="max-width: 90px;">· ${escapeHtml(cuenta.nombre)}</span>`;
            }
            if (btn) {
                btn.title = `${cuenta.codigo} - ${cuenta.nombre}`;
                btn.classList.remove('btn-saas-secondary');
                btn.classList.add('btn-saas-secondary');
            }

            modalCuentaInstance.hide();
            filaCuentaActiva = null;
        }

        // EVALUACIÓN REACTIVA DE TIPO DE COMPRA
        function evaluarFormularioReactivo() {
            const tcId = document.getElementById('selTipoCompra').value;
            const tcCodigo = mapaTipos[tcId] || '';
            const reqCot = mapaRequiereCot[tcId] == 1;

            const panelProv = document.getElementById('panel-proveedor');
            const panelSum = document.getElementById('panel-suministro');
            const panelLic = document.getElementById('panel-licitacion');
            const divMontoDisp = document.getElementById('divMontoDisponible');
            const thCm = document.querySelectorAll('.th-cm');
            const tdCm = document.querySelectorAll('.td-cm');
            const colsPrecio = document.querySelectorAll('.col-precio');

            if (reqCot) {
                divMontoDisp.classList.remove('d-none');
                document.getElementById('inpMontoDisponible').required = true;
                colsPrecio.forEach(el => el.classList.add('d-none'));
            } else {
                divMontoDisp.classList.add('d-none');
                document.getElementById('inpMontoDisponible').required = false;
                colsPrecio.forEach(el => el.classList.remove('d-none'));
            }

            if (tcCodigo === 'CONTRATO_SUMINISTRO') {
                panelSum.style.display = 'block';
                document.getElementById('inpSuministro').required = true;
                panelProv.style.display = 'block';
            } else {
                panelSum.style.display = 'none';
                document.getElementById('inpSuministro').required = false;
            }

            if (['TRATO_DIRECTO', 'CONVENIO_MARCO'].includes(tcCodigo)) {
                panelProv.style.display = 'block';
            } else if (tcCodigo !== 'CONTRATO_SUMINISTRO') {
                panelProv.style.display = 'none';
            }

            if (tcCodigo === 'LICITACION') {
                panelLic.style.display = 'block';
                if (document.getElementById('tbodyCriterios').children.length === 0) {
                    agregarCriterio();
                }
            } else {
                panelLic.style.display = 'none';
            }

            if (tcCodigo === 'CONVENIO_MARCO') {
                thCm.forEach(el => el.classList.remove('d-none'));
                tdCm.forEach(el => el.classList.remove('d-none'));
            } else {
                thCm.forEach(el => el.classList.add('d-none'));
                tdCm.forEach(el => el.classList.add('d-none'));
            }

            recalcularTotales();
        }

        // CRITERIOS DE EVALUACIÓN (LICITACIÓN)
        function agregarCriterio(data = null) {
            countCriterios++;
            const tbody = document.getElementById('tbodyCriterios');
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="text-center">
                    <input type="number" name="crit_num[]" value="${countCriterios}" class="form-control form-control-sm text-center bg-light fw-bold" style="width: 45px;" readonly>
                </td>
                <td>
                    <input type="text" name="crit_desc[]" class="form-control form-control-sm" placeholder="Ej: Oferta Económica, Plazo de Entrega" value="${escapeHtml(data ? data.desc : '')}" required>
                </td>
                <td class="text-center">
                    <input type="number" name="crit_porc[]" min="1" max="100" class="form-control form-control-sm text-center crit-porc-val fw-bold text-primary" style="width: 75px; margin: 0 auto;" value="${data ? data.porc : ''}" required oninput="calcCriterios()">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-link text-danger p-0" onclick="this.closest('tr').remove(); calcCriterios();"><i class="bi bi-trash"></i></button>
                </td>
            `;
            tbody.appendChild(tr);
            calcCriterios();
        }

        function calcCriterios() {
            let total = 0;
            document.querySelectorAll('.crit-porc-val').forEach(inp => {
                total += parseFloat(inp.value) || 0;
            });
            const lbl = document.getElementById('sumaCriteriosLabel');
            lbl.innerText = total + '%';
            lbl.className = (total === 100) ? 'text-center fw-bold text-success' : 'text-center fw-bold text-danger';
            return total;
        }

        // TABLA DINÁMICA DE ÍTEMS CON DESCRIPCIÓN MAXIMIZADA Y MODAL DE CUENTAS
        function agregarItemFila(data = null) {
            const tbody = document.getElementById('tbodyItems');
            const tcId = document.getElementById('selTipoCompra').value;
            const isCm = (mapaTipos[tcId] || '') === 'CONVENIO_MARCO';
            const reqCot = mapaRequiereCot[tcId] == 1;

            let cuentaSeleccionada = null;
            if (data && data.cuenta_id) {
                cuentaSeleccionada = cuentasPresupuestarias.find(c => c.id == data.cuenta_id);
            }

            let btnLabelHtml = `<i class="bi bi-tag me-1"></i> Asignar Cuenta`;
            let btnTitle = 'Haga clic para seleccionar cuenta presupuestaria';
            let valCuentaId = '';

            if (cuentaSeleccionada) {
                valCuentaId = cuentaSeleccionada.id;
                btnLabelHtml = `<i class="bi bi-wallet2 me-1 text-primary"></i> <strong class="text-primary">${escapeHtml(cuentaSeleccionada.codigo)}</strong> <span class="text-muted small text-truncate d-none d-xl-inline" style="max-width: 90px;">· ${escapeHtml(cuentaSeleccionada.nombre)}</span>`;
                btnTitle = `${cuentaSeleccionada.codigo} - ${cuentaSeleccionada.nombre}`;
            }

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <textarea name="desc[]" required rows="1" class="form-control-saas item-desc" placeholder="Descripción detallada del producto o servicio..." style="min-height: 38px; resize: vertical;">${escapeHtml(data ? data.desc : '')}</textarea>
                </td>
                <td class="td-cm ${isCm ? '' : 'd-none'}">
                    <input type="number" name="id_producto_cm[]" class="form-control-saas input-cm" placeholder="ID CM" value="${escapeHtml(data ? data.id_cm : '')}">
                </td>
                <td>
                    <select name="uni[]" class="form-select-saas" style="min-width: 95px;">
                        <option value="UNIDAD" ${data && data.uni === 'UNIDAD' ? 'selected' : ''}>UNIDAD</option>
                        <option value="GLOBAL" ${data && data.uni === 'GLOBAL' ? 'selected' : ''}>GLOBAL</option>
                        <option value="MESES" ${data && data.uni === 'MESES' ? 'selected' : ''}>MESES</option>
                        <option value="HORA" ${data && data.uni === 'HORA' ? 'selected' : ''}>HORA</option>
                        <option value="METRO" ${data && data.uni === 'METRO' ? 'selected' : ''}>METRO</option>
                        <option value="KILO" ${data && data.uni === 'KILO' ? 'selected' : ''}>KILO</option>
                    </select>
                </td>
                <td>
                    <input type="number" name="cant[]" min="0.01" step="any" required class="form-control-saas text-center item-cant" value="${data ? data.cant : '1'}" oninput="recalcularTotales()">
                </td>
                <td class="col-precio ${reqCot ? 'd-none' : ''}">
                    <input type="number" name="prec[]" min="0" step="any" class="form-control-saas text-end item-prec" value="${data ? data.prec : '0'}" oninput="recalcularTotales()">
                </td>
                <td>
                    <input type="hidden" name="cuenta_id[]" value="${valCuentaId}" class="input-cuenta-id" required>
                    <button type="button" class="btn-saas btn-saas-secondary btn-saas-sm py-1 px-2.5 w-100 justify-content-between btn-select-cuenta" onclick="abrirModalSeleccionarCuenta(this)" title="${escapeHtml(btnTitle)}">
                        <span class="text-truncate cuenta-btn-label">${btnLabelHtml}</span>
                        <i class="bi bi-chevron-down text-muted" style="font-size: 10px;"></i>
                    </button>
                </td>
                <td class="text-end fw-bold item-total-linea">$ 0</td>
                <td class="text-center">
                    <button type="button" class="btn btn-link text-danger p-0" onclick="eliminarFila(this)"><i class="bi bi-trash"></i></button>
                </td>
            `;
            tbody.appendChild(tr);
            recalcularTotales();
        }

        function eliminarFila(btn) {
            const tbody = document.getElementById('tbodyItems');
            if (tbody.children.length > 1) {
                btn.closest('tr').remove();
                recalcularTotales();
            } else {
                alert('Debe conservar al menos un ítem.');
            }
        }

        // UNIFICACIÓN DEL SELECTOR DE RÉGIMEN NETO / IVA
        function cambiarRegimenImpuesto(reg) {
            regimenActual = reg;
            const rNeto = document.getElementById('reg_neto');
            const rIva = document.getElementById('reg_iva');
            const badgePaso1 = document.getElementById('badgeRegimenPaso1');
            const thPrecio = document.getElementById('thColPrecio');

            if (reg === 'NETO') {
                if (rNeto) rNeto.checked = true;
                if (badgePaso1) badgePaso1.innerText = 'Régimen: Valores Netos';
                if (thPrecio) thPrecio.innerText = 'Precio Unit. (Neto)';
            } else {
                if (rIva) rIva.checked = true;
                if (badgePaso1) badgePaso1.innerText = 'Régimen: Valores Con IVA';
                if (thPrecio) thPrecio.innerText = 'Precio Unit. (Con IVA)';
            }
            recalcularTotales();
        }

        function recalcularTotales() {
            let subtotalNeto = 0;
            let granTotal = 0;
            const ivaRate = 0.19;

            const select = document.getElementById('selTipoCompra');
            const reqCot = select && select.value ? (mapaRequiereCot[select.value] == 1) : false;

            if (reqCot) {
                const inpMonto = document.getElementById('inpMontoDisponible');
                const valMonto = parseFloat(inpMonto.value.replace(/\./g, '')) || 0;
                if (regimenActual === 'NETO') {
                    subtotalNeto = valMonto;
                    granTotal = Math.round(subtotalNeto * (1 + ivaRate));
                } else {
                    granTotal = valMonto;
                    subtotalNeto = Math.round(granTotal / (1 + ivaRate));
                }

                const pNeto = document.getElementById('dispPreviewNeto');
                const pIva = document.getElementById('dispPreviewIva');
                const pTotal = document.getElementById('dispPreviewTotal');
                if (pNeto) pNeto.innerText = formatCLP(subtotalNeto);
                if (pIva) pIva.innerText = formatCLP(Math.max(0, granTotal - subtotalNeto));
                if (pTotal) pTotal.innerText = formatCLP(granTotal);

                document.querySelectorAll('#tbodyItems tr').forEach(row => {
                    row.querySelector('.item-total-linea').innerText = formatCLP(0);
                });
            } else {
                const rows = document.querySelectorAll('#tbodyItems tr');
                rows.forEach(tr => {
                    const cant = parseFloat(tr.querySelector('.item-cant').value) || 0;
                    const prec = parseFloat(tr.querySelector('.item-prec').value) || 0;
                    const lineaRaw = cant * prec;

                    let lineaTotalBruta = 0;
                    if (regimenActual === 'NETO') {
                        lineaTotalBruta = Math.round(lineaRaw * (1 + ivaRate));
                        subtotalNeto += lineaRaw;
                    } else {
                        lineaTotalBruta = Math.round(lineaRaw);
                        subtotalNeto += Math.round(lineaRaw / (1 + ivaRate));
                    }

                    tr.querySelector('.item-total-linea').innerText = formatCLP(lineaTotalBruta);
                    granTotal += lineaTotalBruta;
                });
            }

            const ivaTotal = Math.max(0, granTotal - subtotalNeto);

            document.getElementById('lblSubtotalNeto').innerText = formatCLP(subtotalNeto);
            document.getElementById('lblIvaTotal').innerText = formatCLP(ivaTotal);
            document.getElementById('lblGranTotal').innerText = formatCLP(granTotal);
        }

        function handleMontoInput(inp) {
            let val = inp.value.replace(/\D/g, '');
            if (val === '') val = '0';
            const num = parseInt(val, 10);
            inp.value = num.toLocaleString('es-CL');

            recalcularTotales();
        }

        // SUBIDA DE ARCHIVOS CON PROGRESO AJAX
        function inicializarDropzone() {
            const dropzone = document.getElementById('dropzone');
            const inpAdjunto = document.getElementById('inpAdjunto');
            if (!dropzone) return;

            dropzone.addEventListener('click', (e) => {
                if (e.target !== inpAdjunto) inpAdjunto.click();
            });

            ['dragenter', 'dragover'].forEach(ev => {
                dropzone.addEventListener(ev, (e) => {
                    e.preventDefault();
                    dropzone.classList.add('dragover');
                });
            });

            ['dragleave', 'drop'].forEach(ev => {
                dropzone.addEventListener(ev, (e) => {
                    e.preventDefault();
                    dropzone.classList.remove('dragover');
                });
            });

            dropzone.addEventListener('drop', (e) => {
                procesarNuevosArchivos(e.dataTransfer.files);
            });
        }

        function manejarSeleccionArchivos() {
            const inp = document.getElementById('inpAdjunto');
            if (inp.files && inp.files.length > 0) {
                procesarNuevosArchivos(inp.files);
                inp.value = '';
            }
        }

        function procesarNuevosArchivos(files) {
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const fileId = 'adj_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6);
                const item = {
                    id: fileId,
                    file: file,
                    progress: 0,
                    status: 'uploading',
                    tempPath: '',
                    nombreOriginal: file.name,
                    xhr: null
                };
                archivosSubidos.push(item);
                renderizarItemArchivo(item);
                subirArchivoAJAX(item);
            }
            actualizarEstadoBotonSubmit();
        }

        function renderizarItemArchivo(item) {
            const lista = document.getElementById('listaAdjuntos');
            const sizeMB = (item.file.size / (1024 * 1024)).toFixed(2);
            const div = document.createElement('div');
            div.id = `item-adj-${item.id}`;
            div.className = 'file-item-box';
            div.innerHTML = `
                <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                    <div class="d-flex align-items-center gap-2 text-truncate">
                        <i class="bi bi-file-earmark-text text-primary fs-5"></i>
                        <strong class="text-truncate small" style="max-width: 320px;">${escapeHtml(item.file.name)}</strong>
                        <span class="text-muted small">(${sizeMB} MB)</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span id="status-${item.id}" class="small text-muted fw-bold">0%</span>
                        <button type="button" class="btn btn-link text-danger p-0" onclick="removerAdjuntoAJAX('${item.id}')"><i class="bi bi-x-circle"></i></button>
                    </div>
                </div>
                <div class="progress" style="height: 5px;">
                    <div id="bar-${item.id}" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%"></div>
                </div>
                <div id="err-${item.id}" class="small text-danger fw-bold d-none mt-1" style="font-size: 11px;"></div>
                <input type="hidden" name="archivos_temp_rutas[]" id="input-ruta-${item.id}" value="" disabled>
                <input type="hidden" name="archivos_temp_nombres[]" id="input-nom-${item.id}" value="" disabled>
            `;
            lista.appendChild(div);
        }

        function subirArchivoAJAX(item) {
            const formData = new FormData();
            formData.append('archivo', item.file);
            formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');

            const xhr = new XMLHttpRequest();
            item.xhr = xhr;

            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    const pct = Math.round((e.loaded / e.total) * 100);
                    item.progress = pct;
                    const bar = document.getElementById(`bar-${item.id}`);
                    const statusTxt = document.getElementById(`status-${item.id}`);
                    if (bar) bar.style.width = pct + '%';
                    if (statusTxt) statusTxt.innerText = pct + '%';
                    actualizarEstadoBotonSubmit();
                }
            };

            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            item.progress = 100;
                            item.status = 'completed';
                            item.tempPath = res.ruta_temp;
                            item.nombreOriginal = res.nombre_original;

                            const bar = document.getElementById(`bar-${item.id}`);
                            if (bar) {
                                bar.style.width = '100%';
                                bar.classList.remove('progress-bar-striped', 'progress-bar-animated');
                                bar.classList.add('bg-success');
                            }
                            const statusTxt = document.getElementById(`status-${item.id}`);
                            if (statusTxt) {
                                statusTxt.className = 'small text-success fw-bold';
                                statusTxt.innerText = 'Listo';
                            }
                            const inputRuta = document.getElementById(`input-ruta-${item.id}`);
                            const inputNom = document.getElementById(`input-nom-${item.id}`);
                            if (inputRuta && inputNom) {
                                inputRuta.value = res.ruta_temp;
                                inputRuta.disabled = false;
                                inputNom.value = res.nombre_original;
                                inputNom.disabled = false;
                            }
                        } else {
                            marcarErrorArchivo(item, res.error || 'Error al procesar archivo.');
                        }
                    } catch(e) {
                        marcarErrorArchivo(item, 'Respuesta inválida del servidor.');
                    }
                } else {
                    marcarErrorArchivo(item, 'Error del servidor al subir.');
                }
                actualizarEstadoBotonSubmit();
            };

            xhr.onerror = function() {
                marcarErrorArchivo(item, 'Error de conexión de red.');
                actualizarEstadoBotonSubmit();
            };

            xhr.open('POST', 'subir_adjunto_ajax.php', true);
            xhr.send(formData);
        }

        function marcarErrorArchivo(item, errorMsg) {
            item.status = 'error';
            const bar = document.getElementById(`bar-${item.id}`);
            if (bar) {
                bar.style.width = '100%';
                bar.classList.add('bg-danger');
            }
            const statusTxt = document.getElementById(`status-${item.id}`);
            if (statusTxt) {
                statusTxt.className = 'small text-danger fw-bold';
                statusTxt.innerText = 'Error';
            }
            const errDiv = document.getElementById(`err-${item.id}`);
            if (errDiv) {
                errDiv.innerText = '⚠️ ' + errorMsg;
                errDiv.classList.remove('d-none');
            }
        }

        function removerAdjuntoAJAX(fileId) {
            const idx = archivosSubidos.findIndex(a => a.id === fileId);
            if (idx !== -1) {
                if (archivosSubidos[idx].xhr && archivosSubidos[idx].status === 'uploading') {
                    archivosSubidos[idx].xhr.abort();
                }
                archivosSubidos.splice(idx, 1);
            }
            const el = document.getElementById(`item-adj-${fileId}`);
            if (el) el.remove();
            actualizarEstadoBotonSubmit();
        }

        function actualizarEstadoBotonSubmit() {
            const btn = document.getElementById('btnSubmit');
            const text = document.getElementById('btnText');
            const spinner = document.getElementById('btnSpinner');
            const icon = document.getElementById('btnIcon');

            if (!btn) return;

            const cargando = archivosSubidos.filter(a => a.status === 'uploading');
            const conError = archivosSubidos.filter(a => a.status === 'error');
            const listos = archivosSubidos.filter(a => a.status === 'completed');

            if (cargando.length > 0) {
                btn.disabled = true;
                spinner.classList.remove('d-none');
                icon.classList.add('d-none');
                text.innerText = `Subiendo archivos (${listos.length}/${archivosSubidos.length})...`;
            } else if (conError.length > 0) {
                btn.disabled = true;
                spinner.classList.add('d-none');
                icon.classList.remove('d-none');
                text.innerText = '⚠️ Elimine los archivos con error';
            } else {
                btn.disabled = false;
                spinner.classList.add('d-none');
                icon.classList.remove('d-none');
                text.innerText = 'Emitir Requerimiento OPI';
            }
        }

        // VALIDACIÓN PREVIA AL ENVÍO
        let guardandoFormulario = false;
        function procesarEnvio(e) {
            if (guardandoFormulario) {
                e.preventDefault();
                return false;
            }

            const divError = document.getElementById('errorTabla');
            divError.classList.add('d-none');

            const tcId = document.getElementById('selTipoCompra').value;
            const tcCodigo = mapaTipos[tcId] || '';
            const reqCot = mapaRequiereCot[tcId] == 1;

            if (reqCot) {
                const inpMonto = document.getElementById('inpMontoDisponible');
                const valMonto = parseFloat(inpMonto.value.replace(/\./g, '')) || 0;
                if (valMonto <= 0) {
                    divError.innerText = 'Debe ingresar un monto disponible válido para la cotización.';
                    divError.classList.remove('d-none');
                    inpMonto.focus();
                    e.preventDefault();
                    return false;
                }
            }

            let errorItem = false;
            let errorCuenta = false;
            document.querySelectorAll('#tbodyItems tr').forEach(row => {
                const desc = row.querySelector('.item-desc');
                const cant = row.querySelector('.item-cant');
                const prec = row.querySelector('.item-prec');
                const cta = row.querySelector('.input-cuenta-id');
                const btnCta = row.querySelector('.btn-select-cuenta');
                const inpCm = row.querySelector('.input-cm');

                if (!desc.value.trim()) errorItem = true;
                if (parseFloat(cant.value) <= 0 || !cant.value) errorItem = true;
                if (!reqCot && (parseFloat(prec.value) <= 0 || !prec.value)) errorItem = true;
                
                if (!cta.value) {
                    errorCuenta = true;
                    if (btnCta) btnCta.classList.add('border-danger');
                } else {
                    if (btnCta) btnCta.classList.remove('border-danger');
                }

                if (tcCodigo === 'CONVENIO_MARCO') {
                    const vCm = inpCm.value.trim();
                    if (!vCm || !/^\d+$/.test(vCm)) {
                        errorItem = true;
                        inpCm.classList.add('is-invalid');
                    }
                }
            });

            if (errorCuenta) {
                divError.innerText = 'Debe seleccionar una cuenta presupuestaria para cada ítem.';
                divError.classList.remove('d-none');
                e.preventDefault();
                return false;
            }

            if (errorItem) {
                divError.innerText = 'Por favor complete todos los datos obligatorios de los ítems (en Convenio Marco el ID CM debe ser puramente numérico).';
                divError.classList.remove('d-none');
                e.preventDefault();
                return false;
            }

            if (tcCodigo === 'LICITACION') {
                const suma = calcCriterios();
                if (suma !== 100) {
                    document.getElementById('errorCriteriosMsg').classList.remove('d-none');
                    alert('Los criterios de evaluación técnica deben sumar exactamente 100%. Suma actual: ' + suma + '%');
                    e.preventDefault();
                    return false;
                }
            }

            if (archivosSubidos.some(a => a.status === 'uploading')) {
                alert('Aún hay archivos subiéndose. Espere a que se completen.');
                e.preventDefault();
                return false;
            }

            guardandoFormulario = true;
            const btn = document.getElementById('btnSubmit');
            const text = document.getElementById('btnText');
            const spinner = document.getElementById('btnSpinner');
            const icon = document.getElementById('btnIcon');

            btn.disabled = true;
            text.innerText = 'Ingresando trámite...';
            spinner.classList.remove('d-none');
            icon.classList.add('d-none');

            return true;
        }

        function escapeHtml(text) {
            if (!text) return '';
            return String(text).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[m]);
        }
    </script>
</body>
</html>