<?php
// configuracion_sistema.php - Vista de Configuración del Sistema y Gestión de Rangos UTM
require_once __DIR__ . '/configuracion_sistema_controller.php';
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <?php 
    $titulo_pagina = "Configuración del Sistema";
    include __DIR__ . '/head.php'; 
    ?>
</head>
<body class="bg-slate-50 text-slate-800 font-sans d-flex flex-column min-vh-100">

    <?php include __DIR__ . '/nav.php'; ?>

    <div class="container mt-4 px-3 px-md-4 mb-5">
        
        <!-- CABECERA PRINCIPAL -->
        <div class="row align-items-center mb-4 g-3">
            <div class="col-12 col-md">
                <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                    <i class="bi bi-sliders text-primary fs-3"></i>
                    Configuración Global del Sistema
                </h1>
                <p class="text-muted small mb-0">Gestión de parámetros operativos, límites de adjuntos, valor UTM y tramos de compra (Art. 10).</p>
            </div>
            <div class="col-12 col-md-auto text-start text-md-end">
                <a href="index.php" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i> Volver al Inicio
                </a>
            </div>
        </div>

        <!-- MENSAJES DE ALERTA -->
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?= $tipo_mensaje === 'error' ? 'danger' : 'success' ?> d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
                <i class="bi bi-<?= $tipo_mensaje === 'error' ? 'exclamation-triangle-fill' : 'check-circle-fill' ?> fs-5"></i>
                <div class="fw-semibold"><?= htmlspecialchars($mensaje) ?></div>
            </div>
        <?php endif; ?>

        <!-- FORMULARIO PRINCIPAL DE PARÁMETROS GLOBALES -->
        <form method="POST" action="configuracion_sistema.php">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="accion" value="guardar_parametros">

            <div class="row g-4">

                <!-- TARJETA 1: PARÁMETROS DE ARCHIVOS ADJUNTOS -->
                <div class="col-12 col-lg-6">
                    <div class="card shadow-sm border-light h-100">
                        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                            <div class="p-2 bg-primary-subtle text-primary rounded-3">
                                <i class="bi bi-paperclip fs-5"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">Archivos Adjuntos</h5>
                                <p class="text-muted small mb-0">Establezca los límites de subida de documentación.</p>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-4">
                                <label class="form-label fw-bold text-secondary small">Tamaño Máximo por Archivo (MB) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="limite_peso_adjunto_mb" min="1" max="200" required class="form-control fw-bold text-primary" value="<?= htmlspecialchars($configs['limite_peso_adjunto_mb']) ?>">
                                    <span class="input-group-text bg-light fw-bold">MB</span>
                                </div>
                                <div class="form-text text-muted small mt-1">Límite máximo permitido para cada documento cargado en las solicitudes (por defecto: 10 MB).</div>
                            </div>

                            <div class="mb-0">
                                <label class="form-label fw-bold text-secondary small">Extensiones Permitidas <span class="text-danger">*</span></label>
                                <textarea name="extensiones_permitidas" rows="3" required class="form-control font-monospace small"><?= htmlspecialchars($configs['extensiones_permitidas']) ?></textarea>
                                <div class="form-text text-muted small mt-1">Formatos separados por comas (Ejemplo: pdf, zip, rar, doc, docx, xls, xlsx, jpg, jpeg, png).</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TARJETA 2: VALORES ECONÓMICOS Y SINCRONIZACIÓN UTM -->
                <div class="col-12 col-lg-6">
                    <div class="card shadow-sm border-light h-100">
                        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <div class="p-2 bg-success-subtle text-success rounded-3">
                                    <i class="bi bi-cash-stack fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0 text-dark">Valor UTM y Sincronización</h5>
                                    <p class="text-muted small mb-0">Valor vigente para compras públicas y régimen de mantenimiento.</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-bold text-secondary small mb-0">Valor UTM del Mes (CLP) <span class="text-danger">*</span></label>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 11px;">
                                        <i class="bi bi-calendar-check me-1"></i>Mes: <?= htmlspecialchars($configs['valor_utm_mes'] ?? date('Y-m')) ?>
                                    </span>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-success text-white fw-bold">$</span>
                                    <input type="number" name="valor_utm" min="1" step="0.01" required class="form-control fw-bold text-success fs-5" value="<?= htmlspecialchars($configs['valor_utm']) ?>">
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                                    <span class="small text-muted" style="font-size: 11px;">
                                        <i class="bi bi-cloud-check text-primary me-1"></i>Fuente: <strong>Mindicador.cl</strong> 
                                        <?= !empty($configs['valor_utm_actualizado_el']) ? '· Act: ' . htmlspecialchars($configs['valor_utm_actualizado_el']) : '' ?>
                                    </span>
                                    <button type="submit" form="formSincronizarUtm" class="btn btn-outline-success btn-sm py-1 px-2.5 d-inline-flex align-items-center gap-1 shadow-sm" style="font-size: 11.5px;">
                                        <i class="bi bi-arrow-repeat"></i> Sincronizar Ahora
                                    </button>
                                </div>
                            </div>

                            <hr class="my-3">

                            <div class="mb-0">
                                <label class="form-label fw-bold text-secondary small">Modo Mantenimiento del Sistema</label>
                                <div class="p-3 bg-light rounded-3 border">
                                    <div class="form-check form-switch mb-1">
                                        <input class="form-check-input" type="checkbox" role="switch" id="swMantenimiento" name="modo_mantenimiento" value="1" <?= ($configs['modo_mantenimiento'] === '1') ? 'checked' : '' ?>>
                                        <label class="form-check-input-label fw-bold text-dark" for="swMantenimiento">
                                            Activar Modo Mantenimiento
                                        </label>
                                    </div>
                                    <p class="text-muted small mb-0" style="font-size: 11px;">Al activar esta opción, solo los administradores (SYSADMIN) podrán acceder. Los demás funcionarios serán dirigidos a la pantalla de mantenimiento.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TARJETA 3: INTEGRACIÓN FIRMAGOB -->
                <div class="col-12">
                    <div class="card shadow-sm border-light">
                        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <div class="p-2 bg-indigo-subtle text-indigo rounded-3" style="background-color: #e0e7ff; color: #4338ca;">
                                    <i class="bi bi-shield-lock-fill fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0 text-dark">Integración FirmaGob (Secretaría de Gobierno Digital)</h5>
                                    <p class="text-muted small mb-0">Seleccione el modo de operación de la firma electrónica avanzada (Atendida con OTP o Desatendida automática).</p>
                                </div>
                            </div>
                            <span class="badge <?= ($configs['firmagob_modo'] === 'DESATENDIDA') ? 'bg-success' : 'bg-primary' ?> px-3 py-2 fw-bold">
                                Modo Actual: <?= htmlspecialchars($configs['firmagob_modo']) ?>
                            </span>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-4">
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-bold text-secondary small">Modo de Firma Digital <span class="text-danger">*</span></label>
                                    <div class="p-3 bg-light rounded-3 border">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="firmagob_modo" id="modoAtendida" value="ATENDIDA" <?= ($configs['firmagob_modo'] !== 'DESATENDIDA') ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-bold text-dark" for="modoAtendida">
                                                Firma Atendida (Requiere Código OTP)
                                            </label>
                                            <div class="text-muted small">El funcionario debe ingresar el código de 6 dígitos desde Google Authenticator o FreeOTP en cada firma.</div>
                                        </div>
                                        <hr class="my-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="firmagob_modo" id="modoDesatendida" value="DESATENDIDA" <?= ($configs['firmagob_modo'] === 'DESATENDIDA') ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-bold text-success" for="modoDesatendida">
                                                Firma Desatendida (Automática / 1-Clic Sin OTP)
                                            </label>
                                            <div class="text-muted small">Firma directa e instantánea utilizando el certificado desatendido autorizado en la RA de FirmaGob.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-secondary small">Nombre de la Entidad (JWT Entity)</label>
                                        <input type="text" name="firmagob_entity" class="form-control" value="<?= htmlspecialchars($configs['firmagob_entity'] ?? 'Ilustre Municipalidad de Lebu') ?>" required>
                                        <div class="form-text small">Debe coincidir exactamente con el nombre institucional registrado en la RA de FirmaGob.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-secondary small">Propósito para Firma Atendida (JWT Purpose)</label>
                                        <input type="text" name="firmagob_purpose" class="form-control" value="<?= htmlspecialchars($configs['firmagob_purpose'] ?? 'Propósito General') ?>">
                                        <div class="form-text small">Para modo desatendido se asigna automáticamente "Desatendido".</div>
                                    </div>

                                    <div>
                                        <label class="form-label fw-bold text-secondary small">Ambiente de Operación</label>
                                        <select name="firmagob_ambiente" class="form-select">
                                            <option value="PRODUCCION" <?= ($configs['firmagob_ambiente'] !== 'CERTIFICACION') ? 'selected' : '' ?>>Producción (api.firma.digital.gob.cl)</option>
                                            <option value="CERTIFICACION" <?= ($configs['firmagob_ambiente'] === 'CERTIFICACION') ? 'selected' : '' ?>>Certificación / Sandbox (api.firma.cert.digital.gob.cl)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BOTÓN GUARDAR PARÁMETROS -->
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary px-5 py-2.5 rounded-3 fw-bold shadow d-inline-flex align-items-center gap-2">
                        <i class="bi bi-floppy-fill fs-5"></i>
                        <span>Guardar Parámetros Globales</span>
                    </button>
                </div>

            </div>
        </form>

        <!-- FORMULARIO INDEPENDIENTE PARA SINCRONIZAR UTM DESDE MINDICADOR.CL -->
        <form method="POST" action="configuracion_sistema.php" id="formSincronizarUtm" class="d-none">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="accion" value="sincronizar_utm">
        </form>

        <hr class="my-5">

        <!-- ================================================================= -->
        <!-- TARJETA 4: GESTIÓN DE RANGOS DE MONTO ESTIMADO (ART. 10 LEY 19.886) -->
        <!-- ================================================================= -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-light">
                    <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="p-2 bg-warning-subtle text-warning-emphasis rounded-3">
                                <i class="bi bi-diagram-3-fill fs-5"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">Rangos de Monto Estimado (Art. 10 de Compras Públicas)</h5>
                                <p class="text-muted small mb-0">Configure los tramos de compra en UTM, sus límites y exigencias normativas de cotizaciones.</p>
                            </div>
                        </div>
                        <div>
                            <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1.5 fw-bold" onclick="abrirModalNuevoRango()">
                                <i class="bi bi-plus-circle"></i> Nuevo Rango
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-secondary small text-uppercase font-monospace">
                                    <tr>
                                        <th class="ps-4">ID</th>
                                        <th>Nombre del Rango</th>
                                        <th>Límite Mínimo</th>
                                        <th>Límite Máximo</th>
                                        <th>Equivalente Estimado (CLP)</th>
                                        <th>Regla de Cotizaciones</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-end pe-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($listado_rangos_utm)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted small">No se encontraron rangos configurados.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php 
                                        $utm_val = floatval($configs['valor_utm']);
                                        foreach($listado_rangos_utm as $r): 
                                            $min_clp = number_format(round(floatval($r['min_utm']) * $utm_val), 0, ',', '.');
                                            $max_clp = ($r['max_utm'] !== null) ? number_format(round(floatval($r['max_utm']) * $utm_val), 0, ',', '.') : 'y más';
                                        ?>
                                            <tr class="<?= ($r['activo'] != 1) ? 'table-light opacity-50' : '' ?>">
                                                <td class="ps-4 fw-bold text-muted small">#<?= (int)$r['id'] ?></td>
                                                <td>
                                                    <strong class="text-dark"><?= htmlspecialchars($r['nombre']) ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                                                        <?= number_format(floatval($r['min_utm']), 2, ',', '.') ?> UTM
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                                                        <?= ($r['max_utm'] !== null) ? number_format(floatval($r['max_utm']), 2, ',', '.') . ' UTM' : 'Sin límite' ?>
                                                    </span>
                                                </td>
                                                <td class="small font-monospace text-primary fw-semibold">
                                                    $<?= $min_clp ?> - <?= ($r['max_utm'] !== null) ? '$' . $max_clp : 'y más' ?> CLP
                                                </td>
                                                <td class="small text-muted">
                                                    <?= htmlspecialchars($r['regla_cotizaciones'] ?? 'Sin regla específica') ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if($r['activo'] == 1): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Activo</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">Inactivo</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <div class="d-inline-flex gap-1">
                                                        <button type="button" class="btn btn-outline-primary btn-sm px-2.5 py-1" onclick='abrirModalEditarRango(<?= json_encode($r) ?>)' title="Editar Rango">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </button>
                                                        <form method="POST" action="configuracion_sistema.php" class="d-inline" onsubmit="return confirm('¿Seguro que desea cambiar el estado de este rango?');">
                                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                                            <input type="hidden" name="accion" value="toggle_rango">
                                                            <input type="hidden" name="rango_id" value="<?= (int)$r['id'] ?>">
                                                            <button type="submit" class="btn btn-outline-<?= ($r['activo'] == 1) ? 'danger' : 'success' ?> btn-sm px-2 py-1" title="<?= ($r['activo'] == 1) ? 'Desactivar' : 'Activar' ?>">
                                                                <i class="bi bi-<?= ($r['activo'] == 1) ? 'eye-slash' : 'check-lg' ?>"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TARJETA 5: ACCESO DIRECTO AL DISEÑADOR DE PLANTILLA OPI -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm border-primary-subtle bg-primary-subtle bg-opacity-10">
                    <div class="card-body p-3 p-md-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-3 bg-primary text-white rounded-3 shadow-sm">
                                <i class="bi bi-file-earmark-pdf-fill fs-3"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1 text-dark">Diseñador y Calibrador de Plantilla OPI (PDF)</h5>
                                <p class="text-muted small mb-0">Personalice los títulos oficiales, textos de cláusulas, pie legal y calibre la posición de las líneas base de firma electrónica con vista previa en vivo.</p>
                            </div>
                        </div>
                        <div>
                            <a href="diseno_opi.php" class="btn btn-primary px-4 py-2 fw-bold shadow-sm d-inline-flex align-items-center gap-2 text-nowrap">
                                <i class="bi bi-pencil-square fs-5"></i>
                                <span>Abrir Diseñador de OPI</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- MODAL DE CREACIÓN / EDICIÓN DE RANGO UTM -->
    <div class="modal fade" id="modalRangoUtm" tabindex="-1" aria-labelledby="modalRangoUtmLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow border-0">
                <form method="POST" action="configuracion_sistema.php">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="accion" value="guardar_rango">
                    <input type="hidden" name="rango_id" id="inpModRangoId" value="0">

                    <div class="modal-header border-bottom py-3">
                        <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="modalRangoUtmLabel">
                            <i class="bi bi-tag-fill text-primary"></i>
                            <span id="txtTituloModalRango">Nuevo Rango de Compra</span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small">Nombre del Rango <span class="text-danger">*</span></label>
                            <input type="text" name="rango_nombre" id="inpModRangoNombre" required class="form-control fw-bold" placeholder="Ej: Menor, Bajo, Intermedio, Licitación Menor...">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold text-secondary small">Mínimo (UTM) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="rango_min_utm" id="inpModRangoMin" required class="form-control font-monospace" placeholder="0.00">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold text-secondary small">Máximo (UTM)</label>
                                <input type="number" step="0.01" min="0" name="rango_max_utm" id="inpModRangoMax" class="form-control font-monospace" placeholder="Vacío = Sin límite">
                            </div>
                            <div class="col-12 mt-1">
                                <span class="text-muted small" style="font-size: 11px;">
                                    <i class="bi bi-info-circle me-1"></i>Para tramos abiertos superiores (ej: "Sin límite" o "Mayor a 5.000 UTM"), deje el Máximo vacío.
                                </span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small">Regla / Exigencia de Cotizaciones</label>
                            <input type="text" name="rango_regla" id="inpModRangoRegla" class="form-control" placeholder="Ej: Mínimo 3 Cotizaciones, Sin mínimos, etc.">
                        </div>

                        <div class="p-3 bg-light rounded-3 border">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="swModRangoActivo" name="rango_activo" value="1" checked>
                                <label class="form-check-label fw-bold text-dark small" for="swModRangoActivo">
                                    Rango Habilitado / Activo
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top py-2.5">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">Guardar Rango</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <?php include __DIR__ . '/footer.php'; ?>

    <script>
        let modalRangoInstance = null;

        document.addEventListener('DOMContentLoaded', () => {
            const modalEl = document.getElementById('modalRangoUtm');
            if (modalEl) {
                modalRangoInstance = new bootstrap.Modal(modalEl);
            }
        });

        function abrirModalNuevoRango() {
            document.getElementById('txtTituloModalRango').innerText = 'Nuevo Rango de Compra';
            document.getElementById('inpModRangoId').value = '0';
            document.getElementById('inpModRangoNombre').value = '';
            document.getElementById('inpModRangoMin').value = '0.00';
            document.getElementById('inpModRangoMax').value = '';
            document.getElementById('inpModRangoRegla').value = '';
            document.getElementById('swModRangoActivo').checked = true;

            if (modalRangoInstance) modalRangoInstance.show();
        }

        function abrirModalEditarRango(data) {
            document.getElementById('txtTituloModalRango').innerText = 'Editar Rango de Compra #' + data.id;
            document.getElementById('inpModRangoId').value = data.id;
            document.getElementById('inpModRangoNombre').value = data.nombre || '';
            document.getElementById('inpModRangoMin').value = parseFloat(data.min_utm || 0);
            document.getElementById('inpModRangoMax').value = (data.max_utm !== null && data.max_utm !== undefined) ? parseFloat(data.max_utm) : '';
            document.getElementById('inpModRangoRegla').value = data.regla_cotizaciones || '';
            document.getElementById('swModRangoActivo').checked = (data.activo == 1);

            if (modalRangoInstance) modalRangoInstance.show();
        }
    </script>
</body>
</html>
