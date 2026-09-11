<?php
// components/modal_firmagob.php - Componente Modal Reutilizable de Firma Electrónica Avanzada FirmaGob
?>
<!-- MODAL FIRMAGOB CON OTP -->
<div class="modal fade" id="modalFirmaGob" tabindex="-1" aria-labelledby="modalFirmaGobLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            
            <!-- HEADER MODAL -->
            <div class="modal-header bg-gradient-to-r from-blue-700 to-indigo-800 text-white py-3 px-4" style="background: linear-gradient(135deg, #1e40af 0%, #3730a3 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-2 bg-white bg-opacity-20 rounded-circle text-white">
                        <i class="bi bi-shield-lock-fill fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="modalFirmaGobLabel">Firma Electrónica Avanzada (FirmaGob)</h5>
                        <p class="small mb-0 text-white-50" style="font-size: 11px;">Secretaría de Gobierno Digital &bull; Ley N° 19.799</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <!-- FORMULARIO DE FIRMA -->
            <form method="POST" id="formFirmaGob" onsubmit="return submitFirmaGob(event)">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="accion" id="fgAccion" value="firmar_firmagob">
                <input type="hidden" name="expediente_id" id="fgExpedienteId" value="">
                <input type="hidden" name="transicion_id" id="fgTransicionId" value="">
                <input type="hidden" name="etapa_firma" id="fgEtapaFirma" value="">

                <div class="modal-body p-4">
                    
                    <!-- ALERTA DE ERROR FIRMAGOB -->
                    <div id="fgAlertaError" class="alert alert-danger d-none align-items-center gap-2 mb-3 rounded-3 shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-5 shrink-0"></i>
                        <div class="small fw-semibold" id="fgMsgError">Error al procesar la firma.</div>
                    </div>

                    <!-- TARJETA 1: DATOS DEL FIRMANTE Y DOCUMENTO -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <span class="badge bg-primary text-uppercase mb-1.5" style="font-size: 8px;">Documento a Firmar</span>
                                <h6 class="fw-bold text-dark mb-1" id="fgDocTitulo">Orden de Pedido Interno</h6>
                                <p class="small text-muted mb-0 font-monospace" id="fgDocRef">Ref: -</p>
                                <p class="small text-primary fw-semibold mb-0" id="fgDocMonto">$ 0</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <span class="badge bg-indigo text-white text-uppercase mb-1.5" style="font-size: 8px; background-color: #4f46e5;">Titular del Certificado</span>
                                <h6 class="fw-bold text-dark mb-1" id="fgFirmanteNombre"><?= htmlspecialchars($_SESSION['user_nombre'] ?? 'Funcionario') ?></h6>
                                <p class="small text-muted font-monospace mb-0" id="fgFirmanteRut">RUN: <?= htmlspecialchars($_SESSION['user_rut'] ?? '-') ?></p>
                                <p class="small text-secondary mb-0" id="fgFirmanteCargo"><?= htmlspecialchars($_SESSION['user_cargo'] ?? $_SESSION['user_rol'] ?? '') ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- PESTAÑAS: FIRMA DIRECTA VS FALLBACK MANUAL -->
                    <ul class="nav nav-pills nav-fill mb-3 p-1 bg-slate-100 rounded-3" id="pills-tab-firma" role="tablist" style="background-color: #f1f5f9;">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active py-2 fw-semibold small rounded-2" id="pills-firmagob-tab" data-bs-toggle="pill" data-bs-target="#pills-firmagob" type="button" role="tab">
                                <i class="bi bi-fingerprint me-1"></i> Firma Digital FirmaGob (Recomendada)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-2 fw-semibold small rounded-2 text-secondary" id="pills-manual-tab" data-bs-toggle="pill" data-bs-target="#pills-manual" type="button" role="tab">
                                <i class="bi bi-upload me-1"></i> Carga Manual (DocDigital / Contingencia)
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="pills-tabContentFirma">
                        
                        <!-- PESTAÑA A: FIRMA NATIVA FIRMAGOB -->
                        <div class="tab-pane fade show active" id="pills-firmagob" role="tabpanel">
                            <div class="p-3 bg-blue-50 border border-blue-200 rounded-3 mb-3 text-blue-900" style="background-color: #eff6ff; border-color: #bfdbfe;">
                                <div class="d-flex align-items-center gap-2 mb-1.5">
                                    <i class="bi bi-phone text-primary fs-5"></i>
                                    <span class="fw-bold small">Ingrese su código de seguridad OTP</span>
                                </div>
                                <p class="small mb-0 text-secondary" style="font-size: 11.5px;">
                                    Abra su aplicación móvil autenticadora (Google Authenticator, FreeOTP o similar) e ingrese los 6 dígitos generados.
                                </p>
                            </div>

                            <!-- INPUT DE OTP -->
                            <div class="text-center py-2">
                                <label class="form-label fw-bold text-secondary text-uppercase small" style="font-size: 10px; letter-spacing: 0.5px;">Código OTP (6 Dígitos)</label>
                                <div class="d-flex justify-content-center gap-2 mb-2">
                                    <input type="text" name="otp_code" id="inpOtpCode" maxlength="6" pattern="[0-9]{6}" autocomplete="one-time-code" placeholder="000000" class="form-control text-center font-monospace fw-bold fs-3 shadow-sm" style="max-width: 220px; letter-spacing: 6px;" autofocus>
                                </div>
                                <div class="form-text text-muted small" style="font-size: 10.5px;">
                                    <i class="bi bi-info-circle me-1"></i> Por seguridad estatal, tras 5 intentos erróneos su acceso se bloqueará temporalmente.
                                </div>
                            </div>
                        </div>

                        <!-- PESTAÑA B: CONTINGENCIA MANUAL DOCDIGITAL -->
                        <div class="tab-pane fade" id="pills-manual" role="tabpanel">
                            <div class="p-3 bg-light border rounded-3 mb-3">
                                <p class="small text-muted mb-2">Si el servicio de Gobierno Digital presenta intermitencia, descargue el PDF, fírmelo en DocDigital y suba aquí el archivo resultante:</p>
                                <div class="mb-2">
                                    <label class="form-label fw-bold text-secondary small" style="font-size: 10px;">Archivo PDF Firmado</label>
                                    <input type="file" name="pdf_firmado_manual" id="inpPdfManual" accept="application/pdf" class="form-control form-control-sm">
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- SPINNER DE CARGA -->
                    <div id="fgLoading" class="d-none text-center py-3">
                        <div class="spinner-border text-primary mb-2" role="status">
                            <span class="visually-hidden">Conectando...</span>
                        </div>
                        <p class="small fw-semibold text-primary mb-0">Conectando con Servidor Central de Gobierno Digital...</p>
                        <p class="text-muted small" style="font-size: 10px;">Estampando firma electrónica avanzada. Por favor espere.</p>
                    </div>

                </div>

                <!-- FOOTER MODAL -->
                <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 fw-semibold" data-bs-dismiss="modal" id="fgBtnCancelar">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold shadow d-flex align-items-center gap-2" id="fgBtnFirmar">
                        <i class="bi bi-pen-fill"></i>
                        <span>Estampar Firma Electrónica</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
let modalFirmaGobInstance = null;

function abrirModalFirmaGob(data) {
    document.getElementById('fgExpedienteId').value = data.expediente_id || '';
    document.getElementById('fgTransicionId').value = data.transicion_id || '';
    document.getElementById('fgEtapaFirma').value = data.etapa || 'JEFATURA';
    
    document.getElementById('fgDocTitulo').innerText = data.doc_titulo || 'Orden de Pedido Interno';
    document.getElementById('fgDocRef').innerText = 'Ref: ' + (data.codigo_interno || data.folio_opi || '-');
    document.getElementById('fgDocMonto').innerText = data.monto ? '$ ' + Number(data.monto).toLocaleString('es-CL') : '';

    if (data.firmante_nombre) document.getElementById('fgFirmanteNombre').innerText = data.firmante_nombre;
    if (data.firmante_rut) document.getElementById('fgFirmanteRut').innerText = 'RUN: ' + data.firmante_rut;
    if (data.firmante_cargo) document.getElementById('fgFirmanteCargo').innerText = data.firmante_cargo;

    // Resetear formulario
    document.getElementById('inpOtpCode').value = '';
    document.getElementById('fgAlertaError').classList.add('d-none');
    document.getElementById('fgLoading').classList.add('d-none');
    document.getElementById('fgBtnFirmar').disabled = false;
    document.getElementById('fgBtnCancelar').disabled = false;

    if (!modalFirmaGobInstance) {
        modalFirmaGobInstance = new bootstrap.Modal(document.getElementById('modalFirmaGob'));
    }
    modalFirmaGobInstance.show();

    setTimeout(() => {
        const inp = document.getElementById('inpOtpCode');
        if (inp) inp.focus();
    }, 400);
}

function submitFirmaGob(e) {
    const activeTab = document.querySelector('#pills-tab-firma .nav-link.active').id;
    
    if (activeTab === 'pills-firmagob-tab') {
        const otp = document.getElementById('inpOtpCode').value.trim();
        if (otp.length !== 6 || !/^\d+$/.test(otp)) {
            e.preventDefault();
            const errDiv = document.getElementById('fgAlertaError');
            document.getElementById('fgMsgError').innerText = 'Debe ingresar un código OTP válido de 6 dígitos numéricos.';
            errDiv.classList.remove('d-none');
            return false;
        }
    } else {
        const file = document.getElementById('inpPdfManual').files[0];
        if (!file) {
            e.preventDefault();
            const errDiv = document.getElementById('fgAlertaError');
            document.getElementById('fgMsgError').innerText = 'Debe seleccionar el archivo PDF firmado.';
            errDiv.classList.remove('d-none');
            return false;
        }
        document.getElementById('fgAccion').value = 'subir_pdf_manual';
    }

    // Mostrar spinner
    document.getElementById('fgAlertaError').classList.add('d-none');
    document.getElementById('fgLoading').classList.remove('d-none');
    document.getElementById('fgBtnFirmar').disabled = true;
    document.getElementById('fgBtnCancelar').disabled = true;

    return true;
}
</script>
