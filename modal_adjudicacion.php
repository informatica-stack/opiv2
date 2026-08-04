<?php
$listado_prov_modal_json = [];
if (isset($proveedores_db)) {
    foreach($proveedores_db as $p) {
        $listado_prov_modal_json[] = [
            'id' => (int)$p['id'],
            'rut' => $p['rut'],
            'razon_social' => $p['razon_social'],
            'frecuente' => false
        ];
    }
}
?>
<div class="modal fade" id="modalAdjudicar" tabindex="-1" aria-labelledby="modalAdjudicarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content rounded-3 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-dark" id="modalAdjudicarLabel">Evaluación y Adjudicación Final</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form method="POST" action="mis_solicitudes.php" enctype="multipart/form-data" id="formAdjudicar" onsubmit="return validarAdjudicacion()">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="accion" value="adjudicar">
                <input type="hidden" name="expediente_id" id="modalId">
                
                <div class="modal-body p-4 overflow-visible">
                    <div class="bg-light border p-3 rounded-3 mb-4 text-center shadow-sm">
                        <p class="text-uppercase text-muted fw-bold mb-1" style="font-size: 9px; letter-spacing: 0.5px;">Expediente a evaluar:</p>
                        <h5 id="modalCodigo" class="font-monospace fw-bold text-primary mb-0"></h5>
                    </div>

                    <div class="row g-4">
                        <!-- 1. Seleccione el Proveedor Ganador -->
                        <div class="col-12">
                            <div class="card border border-light-subtle shadow-sm overflow-visible">
                                <div class="card-header bg-white py-2.5">
                                    <h6 class="fw-bold mb-0 text-dark">1. Seleccione el Proveedor Ganador</h6>
                                </div>
                                <div class="card-body p-3 overflow-visible">
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">🔎 Buscar y Seleccionar Proveedor (RUT o Razón Social)</label>
                                            <div class="custom-select-container" id="containerProveedorModal">
                                                <input type="text" id="buscadorProvModal" class="form-control form-control-sm bg-white custom-select-input" placeholder="🔎 Escriba RUT o Razón Social para buscar..." autocomplete="off" onfocus="showProvDropdownModal()" oninput="filterProvDropdownModal()">
                                                <input type="hidden" name="proveedor_id" id="selProvModal" value="" required>
                                                <div class="custom-select-dropdown" id="dropdownProvModal">
                                                    <!-- Opciones dinámicas por JS -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div id="panelNuevoProvModal" class="panel-slide bg-white border border-info rounded-3 shadow-sm d-none">
                                        <div class="p-3">
                                            <h6 class="text-info-emphasis fw-bold mb-1">Pre-registro de Nuevo Proveedor Adjudicado</h6>
                                            <p class="text-muted mb-3" style="font-size: 11px;">Ingrese los datos para pre-registro. <b>Es obligatorio</b> subir la Ficha o Cotización formal del proveedor.</p>
                                            
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold text-secondary small">RUT Sugerido <span class="text-danger">*</span></label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" name="rut_proveedor" id="inpNuevoProvRutModal" class="form-control inp-nuevo-modal bg-light font-monospace" placeholder="12.345.678-K" oninput="handleRutInputModal(this)">
                                                        <span class="input-group-text bg-light text-secondary py-0" id="rutStatusIconModal" style="font-size: 11px; min-width: 32px; text-align: center; justify-content: center;">➖</span>
                                                    </div>
                                                    <div id="rutValidationMsgModal" class="small text-danger d-none mt-1" style="font-size: 10px;">⚠️ El formato o dígito verificador del RUT es inválido.</div>
                                                    <div id="rutDuplicateAlertModal" class="alert alert-warning p-2 mt-2 mb-0 small d-none" style="font-size: 11px; border-left: 4px solid #ffc107;">
                                                        <strong>⚠️ Ya registrado:</strong> <span id="duplicateProvNameModal" class="fw-bold"></span>. 
                                                        <button type="button" class="btn btn-xs btn-warning py-0 px-2 fw-bold text-dark border-0 ms-2" onclick="selectDuplicateProviderModal()" style="font-size: 10px; background: #e0a800;">Seleccionar</button>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold text-secondary small">Razón Social / Nombre Sugerido <span class="text-danger">*</span></label>
                                                    <input type="text" name="nombre_proveedor" id="inpNuevoProvNombreModal" class="form-control form-control-sm inp-nuevo-modal bg-light">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold text-secondary small">Dirección y Comuna Sugerida</label>
                                                    <input type="text" name="direccion_proveedor" id="inpNuevoProvDireccionModal" class="form-control form-control-sm bg-light">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold text-secondary small">Ficha del Proveedor (PDF) <span class="text-danger">*</span></label>
                                                    <input type="file" name="ficha_proveedor" id="inpFichaModal" accept="application/pdf" class="form-control form-control-sm bg-light">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Detalle de Adjudicación por Ítem -->
                        <div class="col-12">
                            <div class="card border border-light-subtle shadow-sm">
                                <div class="card-header bg-white py-2.5 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark">2. Detalle de Adjudicación por Ítem</h6>
                                        <p class="text-muted small mb-0" style="font-size: 10px;">Ingrese el valor adjudicado para cada línea.</p>
                                    </div>
                                    <input type="hidden" name="tipo_impuesto_adj" value="NETO">
                                </div>
                                <div class="card-body p-3">
                                    <div class="table-responsive rounded-3 border">
                                        <table class="table table-hover align-middle mb-0 text-sm">
                                            <thead class="table-light text-uppercase small text-secondary">
                                                <tr>
                                                    <th class="p-2">Ítem</th>
                                                    <th class="p-2 text-center" style="width: 80px;">Cant.</th>
                                                    <th class="p-2 text-end" style="width: 120px;">Precio Ref. (Bruto)</th>
                                                    <th class="p-2 text-end" style="width: 140px;">Nvo. Precio Unit.</th>
                                                    <th class="p-2 text-end" style="width: 120px;">Total Línea</th>
                                                </tr>
                                            </thead>
                                            <tbody id="modalItemsBody"></tbody>
                                            <tfoot class="table-light text-secondary">
                                                <tr>
                                                    <td colspan="4" class="p-2 text-end fw-semibold" style="font-size: 11px;">Subtotal Neto:</td>
                                                    <td class="p-2 text-end fw-semibold text-dark" id="modalAdjNeto">$ 0</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="4" class="p-2 text-end fw-semibold" style="font-size: 11px;">Impuestos (IVA):</td>
                                                    <td class="p-2 text-end fw-semibold text-dark" id="modalAdjIva">$ 0</td>
                                                </tr>
                                                <tr class="table-active">
                                                    <td colspan="4" class="p-2 text-end fw-bold text-dark" style="font-size: 11px;">Total Final a Pagar:</td>
                                                    <td class="p-2 text-end fw-bold text-primary fs-5" id="modalAdjTotal">$ 0</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Acta de Adjudicación -->
                        <div class="col-12 mt-3">
                            <div class="card border border-light-subtle shadow-sm">
                                <div class="card-header bg-white py-2.5">
                                    <h6 class="fw-bold mb-0 text-dark">3. Acta de Adjudicación</h6>
                                    <p class="text-muted small mb-0" style="font-size: 10px;">Adjunte obligatoriamente el Acta de Adjudicación oficial firmada (PDF).</p>
                                </div>
                                <div class="card-body p-3">
                                    <div>
                                        <label class="form-label fw-bold text-secondary small" style="font-size: 9.5px;">Acta de Adjudicación (PDF) <span class="text-danger">*</span></label>
                                        <input type="file" name="acta_adjudicacion" id="inpActaAdjudicacion" accept="application/pdf" required class="form-control form-control-sm">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-4 fw-semibold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold shadow">Confirmar y Adjudicar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // --- LÓGICA MODAL ADJUDICAR ---
    let modalAdjudicarInstance = null;
    let montoEstimadoExpediente = 0;
    let totalBrutoAdjudicacion = 0;

    function abrirModalAdjudicar(data) {
        document.getElementById('modalId').value = data.id;
        document.getElementById('modalCodigo').innerText = data.codigo_interno;
        montoEstimadoExpediente = parseFloat(data.monto_estimado) || 0;
        
        // Reset Proveedor
        document.getElementById('buscadorProvModal').value = '';
        document.getElementById('selProvModal').value = '';
        toggleNuevoProvModal();

        // Dibujar Items con valor inicial 0
        const tbody = document.getElementById('modalItemsBody');
        tbody.innerHTML = '';
        
        if (data.items_detalle && data.items_detalle.length > 0) {
            data.items_detalle.forEach(item => {
                const cant = parseFloat(item.cantidad);
                const precOri = parseFloat(item.precio_unitario);
                
                const tr = document.createElement('tr');
                tr.className = "align-middle";
                tr.innerHTML = `
                    <td class="p-2 small text-secondary fw-medium" style="max-width: 500px;">${escapeHTML(item.descripcion)}</td>
                    <td class="p-2 text-center fw-bold text-dark">${cant} <span class="text-muted d-block" style="font-size: 9px;">${item.unidad_medida}</span></td>
                    <td class="p-2 text-end text-muted text-decoration-line-through small" style="font-size: 11px;">${formatCurrency(precOri)}</td>
                    <td class="p-2">
                        <input type="text" name="item_precio[${item.id}]" required class="form-control form-control-sm text-end fw-bold input-precio-adjudicar" value="0" data-cant="${cant}" oninput="calcAdjudicacion()">
                    </td>
                    <td class="p-2 text-end fw-bold text-dark span-total-linea">$ 0</td>
                `;
                tbody.appendChild(tr);
            });
        }

        calcAdjudicacion();

        if (!modalAdjudicarInstance) {
            modalAdjudicarInstance = new bootstrap.Modal(document.getElementById('modalAdjudicar'));
        }
        modalAdjudicarInstance.show();
    }

    function calcAdjudicacion() {
        let totalNeto = 0, totalIva = 0, totalBruto = 0;
        const tipo = 'NETO';
        const ivaRate = 0.19;

        document.querySelectorAll('.input-precio-adjudicar').forEach(inp => {
            let valStr = inp.value.replace(/\D/g, "");
            let formattedVal = valStr ? valStr.replace(/\B(?=(\d{3})+(?!\d))/g, ".") : "0";
            if (inp.value !== formattedVal) {
                inp.value = formattedVal;
            } 
            
            let val = parseFloat(valStr) || 0;
            let cant = parseFloat(inp.getAttribute('data-cant')) || 0;
            
            let lineaNeto = 0, lineaBruto = 0;
            if (tipo === 'NETO') {
                lineaNeto = cant * val; 
                lineaBruto = lineaNeto * (1 + ivaRate);
            } else if (tipo === 'IVA_INCLUIDO') {
                lineaBruto = cant * val; 
                lineaNeto = lineaBruto / (1 + ivaRate);
            } else if (tipo === 'EXENTO') {
                lineaNeto = cant * val;
                lineaBruto = lineaNeto;
            }

            inp.closest('tr').querySelector('.span-total-linea').innerText = formatCurrency(lineaBruto);
            totalNeto += lineaNeto;
            totalBruto += lineaBruto;
        });

        totalIva = totalBruto - totalNeto;
        document.getElementById('modalAdjNeto').innerText = formatCurrency(totalNeto);
        document.getElementById('modalAdjIva').innerText = formatCurrency(totalIva);
        document.getElementById('modalAdjTotal').innerText = formatCurrency(totalBruto);
        totalBrutoAdjudicacion = totalBruto;
    }

    function formatNumber(num) { return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, "."); }
    function formatCurrency(num) { return '$ ' + formatNumber(num); }

    function validarAdjudicacion() {
        const selProv = document.getElementById('selProvModal').value;
        if (!selProv) {
            alert("⚠️ Por favor, seleccione un proveedor.");
            return false;
        }

        if (selProv === 'NUEVO') {
            const inpRut = document.getElementById('inpNuevoProvRutModal');
            const inpNombre = document.getElementById('inpNuevoProvNombreModal');
            const inpFicha = document.getElementById('inpFichaModal');
            
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
                alert("⚠️ Faltan datos del Proveedor Nuevo: Por favor ingrese un RUT válido, Razón Social y suba el archivo PDF de la Ficha.");
                return false;
            }
        }

        // Validar precios positivos
        let preciosValidos = true;
        document.querySelectorAll('.input-precio-adjudicar').forEach(inp => {
            const val = parseFloat(inp.value.replace(/\D/g, "")) || 0;
            inp.classList.remove('is-invalid');
            if (val <= 0) {
                inp.classList.add('is-invalid');
                preciosValidos = false;
            }
        });

        if (!preciosValidos) {
            alert("⚠️ Todos los ítems adjudicados deben tener un precio unitario mayor a cero.");
            return false;
        }

        // Validar que no supere el monto_estimado
        if (totalBrutoAdjudicacion > montoEstimadoExpediente) {
            alert("⚠️ Error de Adjudicación: El monto total adjudicado (" + formatCurrency(totalBrutoAdjudicacion) + " con IVA) supera el presupuesto disponible para este trámite (" + formatCurrency(montoEstimadoExpediente) + ").");
            return false;
        }

        return true;
    }

    // --- LÓGICA DE PROVEEDORES AUTOCOMPLETE & RUT EN MODAL ---
    const listadoProveedoresModal = <?= json_encode($listado_prov_modal_json) ?>;
    let currentFocusedIndexModal = -1;
    let filteredItemsModal = [];
    let selectedDuplicateIdModal = null;

    function escapeHTML(str) {
        if (!str) return '';
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    function getFilteredListModal(query) {
        const q = query.toLowerCase().trim();
        if (!q) return listadoProveedoresModal;
        return listadoProveedoresModal.filter(p => 
            p.rut.toLowerCase().includes(q) || 
            p.razon_social.toLowerCase().includes(q)
        );
    }

    function renderProvDropdownModal(items) {
        const dropdown = document.getElementById('dropdownProvModal');
        dropdown.innerHTML = '';
        
        // Opción nuevo
        const optNew = document.createElement('div');
        optNew.className = 'custom-select-option text-primary-emphasis fw-bold border-bottom';
        optNew.innerHTML = '➕ INGRESAR NUEVO PROVEEDOR MANUALMENTE';
        optNew.onclick = () => selectProviderModal('NUEVO', 'INGRESAR NUEVO PROVEEDOR MANUALMENTE', '');
        dropdown.appendChild(optNew);
        
        // Directorio
        if (items.length > 0) {
            const header = document.createElement('div');
            header.className = 'custom-select-group-header';
            header.innerText = '🏢 Directorio de Proveedores';
            dropdown.appendChild(header);
            
            items.forEach(p => {
                const opt = document.createElement('div');
                opt.className = 'custom-select-option';
                opt.innerHTML = `<strong>${escapeHTML(p.rut)}</strong> - ${escapeHTML(p.razon_social)}`;
                opt.onclick = () => selectProviderModal(p.id, p.razon_social, p.rut);
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

    function showProvDropdownModal() {
        const dropdown = document.getElementById('dropdownProvModal');
        dropdown.classList.add('show');
        currentFocusedIndexModal = -1;
        
        const selectVal = document.getElementById('selProvModal').value;
        if (selectVal && selectVal !== 'NUEVO') {
            renderProvDropdownModal(listadoProveedoresModal);
        } else {
            filterProvDropdownModal();
        }
    }

    function filterProvDropdownModal() {
        const query = document.getElementById('buscadorProvModal').value;
        const selectVal = document.getElementById('selProvModal').value;
        if (selectVal && selectVal !== 'NUEVO') {
            const p = listadoProveedoresModal.find(x => x.id == selectVal);
            if (p && `${p.rut} - ${p.razon_social}` === query) {
                renderProvDropdownModal(listadoProveedoresModal);
                return;
            }
        }
        filteredItemsModal = getFilteredListModal(query);
        renderProvDropdownModal(filteredItemsModal);
    }

    function selectProviderModal(id, name, rut) {
        document.getElementById('selProvModal').value = id;
        document.getElementById('buscadorProvModal').value = id ? (id === 'NUEVO' ? name : `${rut} - ${name}`) : '';
        document.getElementById('dropdownProvModal').classList.remove('show');
        toggleNuevoProvModal();
    }

    function toggleNuevoProvModal() {
        const val = document.getElementById('selProvModal').value;
        const panel = document.getElementById('panelNuevoProvModal');
        const reqInputs = panel.querySelectorAll('.inp-nuevo-modal');
        const inpFicha = document.getElementById('inpFichaModal');
        
        if (val === 'NUEVO') {
            toggleSlidePanel('panelNuevoProvModal', true);
            reqInputs.forEach(i => i.setAttribute('required', 'required'));
            inpFicha.setAttribute('required', 'required');
        } else {
            toggleSlidePanel('panelNuevoProvModal', false);
            reqInputs.forEach(i => { i.removeAttribute('required'); i.value = ''; });
            inpFicha.removeAttribute('required');
            inpFicha.value = '';
            
            const icon = document.getElementById('rutStatusIconModal');
            if (icon) {
                icon.innerText = '➖';
                icon.className = 'input-group-text bg-light text-secondary py-0';
            }
            const msg = document.getElementById('rutValidationMsgModal');
            if (msg) msg.classList.add('d-none');
            const dupAlert = document.getElementById('rutDuplicateAlertModal');
            if (dupAlert) dupAlert.classList.add('d-none');
            selectedDuplicateIdModal = null;
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

    function handleRutInputModal(input) {
        let raw = input.value;
        let formatted = formatRut(raw);
        input.value = formatted;
        
        const icon = document.getElementById('rutStatusIconModal');
        const msg = document.getElementById('rutValidationMsgModal');
        const dupAlert = document.getElementById('rutDuplicateAlertModal');
        
        if (!formatted) {
            icon.innerText = '➖';
            icon.className = 'input-group-text bg-light text-secondary py-0';
            msg.classList.add('d-none');
            dupAlert.classList.add('d-none');
            selectedDuplicateIdModal = null;
            return;
        }
        
        const isValid = validateRut(formatted);
        if (isValid) {
            icon.innerText = '✅';
            icon.className = 'input-group-text bg-success-subtle text-success py-0';
            msg.classList.add('d-none');
            
            // Comprobar duplicado
            const cleanRut = formatted.replace(/[^0-9kK]/g, '').toLowerCase();
            const dup = listadoProveedoresModal.find(p => p.rut.replace(/[^0-9kK]/g, '').toLowerCase() === cleanRut);
            if (dup) {
                document.getElementById('duplicateProvNameModal').innerText = dup.razon_social;
                dupAlert.classList.remove('d-none');
                selectedDuplicateIdModal = dup.id;
            } else {
                dupAlert.classList.add('d-none');
                selectedDuplicateIdModal = null;
            }
        } else {
            icon.innerText = '❌';
            icon.className = 'input-group-text bg-danger-subtle text-danger py-0';
            msg.classList.remove('d-none');
            dupAlert.classList.add('d-none');
            selectedDuplicateIdModal = null;
        }
    }

    function selectDuplicateProviderModal() {
        if (selectedDuplicateIdModal) {
            const p = listadoProveedoresModal.find(x => x.id == selectedDuplicateIdModal);
            if (p) {
                selectProviderModal(p.id, p.razon_social, p.rut);
                // Reset
                document.getElementById('inpNuevoProvRutModal').value = '';
                document.getElementById('inpNuevoProvNombreModal').value = '';
                document.getElementById('inpNuevoProvDireccionModal').value = '';
                document.getElementById('inpFichaModal').value = '';
                document.getElementById('rutStatusIconModal').innerText = '➖';
                document.getElementById('rutStatusIconModal').className = 'input-group-text bg-light text-secondary py-0';
                document.getElementById('rutDuplicateAlertModal').classList.add('d-none');
            }
        }
    }

    // Cerrar dropdown al hacer click afuera
    document.addEventListener('click', function(e) {
        const container = document.getElementById('containerProveedorModal');
        if (container && !container.contains(e.target)) {
            document.getElementById('dropdownProvModal').classList.remove('show');
            
            const val = document.getElementById('selProvModal').value;
            const text = document.getElementById('buscadorProvModal').value;
            if (!val) {
                document.getElementById('buscadorProvModal').value = '';
            } else if (val === 'NUEVO') {
                document.getElementById('buscadorProvModal').value = 'INGRESAR NUEVO PROVEEDOR MANUALMENTE';
            } else {
                const p = listadoProveedoresModal.find(x => x.id == val);
                if (p && `${p.rut} - ${p.razon_social}` !== text) {
                    document.getElementById('buscadorProvModal').value = `${p.rut} - ${p.razon_social}`;
                }
            }
        }
    });

    // Keyboard navigation
    document.addEventListener('DOMContentLoaded', () => {
        const buscador = document.getElementById('buscadorProvModal');
        if (buscador) {
            buscador.addEventListener('keydown', function(e) {
                const dropdown = document.getElementById('dropdownProvModal');
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
                    currentFocusedIndexModal = (currentFocusedIndexModal + 1) % options.length;
                    updateOptionFocusModal(options);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    currentFocusedIndexModal = (currentFocusedIndexModal - 1 + options.length) % options.length;
                    updateOptionFocusModal(options);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (currentFocusedIndexModal >= 0 && currentFocusedIndexModal < options.length) {
                        options[currentFocusedIndexModal].click();
                    }
                } else if (e.key === 'Escape') {
                    dropdown.classList.remove('show');
                }
            });
        }
    });

    function updateOptionFocusModal(options) {
        options.forEach((opt, idx) => {
            if (idx === currentFocusedIndexModal) {
                opt.classList.add('active');
                opt.scrollIntoView({ block: 'nearest' });
            } else {
                opt.classList.remove('active');
            }
        });
    }

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
</script>