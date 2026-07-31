<!-- modal_trazabilidad.php - Modal Universal de Trazabilidad -->
<div class="modal fade" id="modalTrazabilidad" tabindex="-1" aria-labelledby="modalTrazabilidadLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-3 shadow border-0">
            <div class="modal-header border-bottom bg-slate-50 py-3">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="p-2 bg-primary-subtle text-primary rounded-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-clock-history fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark" id="modalTrazabilidadLabel" style="font-size: 16px;">Trazabilidad del Trámite</h5>
                        <div id="modalTrazabilidadCodigo" class="font-monospace text-muted mt-0.5" style="font-size: 11px;"></div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-slate-50" style="max-height: 65vh; overflow-y: auto;" id="modalTrazabilidadBody">
                <!-- Contenido dinámico (timeline) -->
            </div>
            <div class="modal-footer border-top bg-slate-50 py-2.5">
                <button type="button" class="btn btn-secondary btn-sm px-3 fw-bold shadow-sm" data-bs-dismiss="modal">Cerrar Visor</button>
            </div>
        </div>
    </div>
</div>

<script>
function verTrazabilidad(expedienteId) {
    const modalEl = document.getElementById('modalTrazabilidad');
    const bodyEl = document.getElementById('modalTrazabilidadBody');
    const codigoEl = document.getElementById('modalTrazabilidadCodigo');
    
    // Instanciar o recuperar el objeto Modal de Bootstrap 5
    let modalObj = bootstrap.Modal.getInstance(modalEl);
    if (!modalObj) {
        modalObj = new bootstrap.Modal(modalEl);
    }
    modalObj.show();
    
    // Estado de carga inicial (spinner)
    codigoEl.innerText = 'Cargando datos...';
    bodyEl.innerHTML = `
        <div class="text-center p-5">
            <div class="spinner-border text-primary" role="status" style="width: 2.5rem; height: 2.5rem;">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="text-muted mt-3 mb-0 fw-semibold" style="font-size: 13px;">Consultando historial del expediente...</p>
        </div>
    `;
    
    // Consumir API AJAX
    fetch('trazabilidad_ajax.php?id=' + expedienteId)
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { 
                    throw new Error(err.error || 'Error al obtener trazabilidad'); 
                });
            }
            return response.json();
        })
        .then(data => {
            // Cabecera del modal
            codigoEl.innerHTML = `
                <span class="badge bg-primary-subtle text-primary font-monospace px-2 py-1 me-1">${data.codigo_interno}</span>
                <span class="text-dark fw-semibold">${data.titulo_compra || 'Sin Título'}</span>
            `;
            
            if (!data.historial || data.historial.length === 0) {
                bodyEl.innerHTML = `
                    <div class="alert alert-info text-center m-0 shadow-sm rounded-3 py-4">
                        <i class="bi bi-info-circle-fill d-block fs-3 mb-2 text-info"></i>
                        No se registran movimientos previos para este expediente en el sistema.
                    </div>
                `;
                return;
            }
            
            let html = '<div class="timeline-wrapper">';
            
            data.historial.forEach((item, index) => {
                let badgeClass = 'badge-primary';
                let iconClass = 'bi-gear-fill';
                let commentClass = '';
                
                const action = item.accion.toUpperCase();
                
                // Mapeo semántico de acciones, badges e iconos
                if (action === 'CREAR') {
                    badgeClass = 'badge-primary';
                    iconClass = 'bi-plus-circle-fill';
                } else if (action === 'CORREGIR') {
                    badgeClass = 'badge-info';
                    iconClass = 'bi-pencil-square';
                } else if (action === 'DEVOLVER') {
                    badgeClass = 'badge-warning';
                    iconClass = 'bi-arrow-counterclockwise';
                    commentClass = 'timeline-comment-warning';
                } else if (action === 'RECHAZAR' || action === 'ANULAR') {
                    badgeClass = 'badge-danger';
                    iconClass = 'bi-x-circle-fill';
                    commentClass = 'timeline-comment-danger';
                } else if (
                    action.includes('APROBAR') || 
                    action.includes('VISAR') || 
                    action.includes('EMITIR') || 
                    action.includes('SOLICITAR_CDP') ||
                    action.includes('ADJUDICAR')
                ) {
                    badgeClass = 'badge-success';
                    iconClass = 'bi-check-circle-fill';
                }
                
                // Formateo de fecha según configuración regional de Chile
                const fecha = new Date(item.fecha_accion);
                const fechaStr = fecha.toLocaleDateString('es-CL', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                }) + ' ' + fecha.toLocaleTimeString('es-CL', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
                
                // Formateo de nombres de estados
                const formatEst = (est) => {
                    if (!est) return '-';
                    return est.replace(/_/g, ' ').toUpperCase();
                };
                
                const estadoAnt = formatEst(item.estado_anterior);
                const estadoNvo = formatEst(item.estado_nuevo);
                const accionAmigable = action.replace(/_/g, ' ');
                
                html += `
                    <div class="timeline-item">
                        <div class="timeline-badge ${badgeClass}" title="${accionAmigable}">
                            <i class="bi ${iconClass}"></i>
                        </div>
                        <div class="timeline-card border-light-subtle">
                            <div class="timeline-header">
                                <span class="timeline-title text-uppercase font-black text-primary" style="font-size: 12px; letter-spacing: 0.5px;">${accionAmigable}</span>
                                <span class="timeline-time text-muted"><i class="bi bi-clock me-1"></i>${fechaStr}</span>
                            </div>
                            <div class="timeline-actor mt-1">
                                <i class="bi bi-person-circle text-secondary me-1"></i>
                                <span class="text-dark fw-bold">${item.usuario_nombre}</span>
                                <span class="badge bg-slate-200 text-slate-800 border ms-1 px-1.5 py-0.5 font-bold uppercase" style="font-size: 8px;">${item.rol_nombre}</span>
                            </div>
                            <div class="timeline-transition mt-2 bg-light p-2 rounded border" style="font-size: 11px;">
                                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                    <span class="text-muted text-uppercase fw-bold" style="font-size: 9px;">Estado:</span>
                                    <span class="text-secondary">${estadoAnt}</span>
                                    <i class="bi bi-arrow-right text-muted"></i>
                                    <span class="text-primary fw-bold">${estadoNvo}</span>
                                </div>
                            </div>
                            ${item.comentario ? `
                                <div class="timeline-comment ${commentClass} mt-2">
                                    <div class="fw-bold mb-1" style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.3px;">Comentario / Justificación:</div>
                                    <div style="white-space: pre-wrap;">${escapeHtml(item.comentario)}</div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            bodyEl.innerHTML = html;
        })
        .catch(err => {
            // Manejador de errores visual
            bodyEl.innerHTML = `
                <div class="alert alert-danger d-flex align-items-start gap-2.5 m-0 shadow-sm rounded-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-4 text-danger mt-0.5 shrink-0"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Error al consultar trazabilidad</h6>
                        <p class="mb-0 small text-secondary">${err.message}</p>
                    </div>
                </div>
            `;
        });
}

// Función auxiliar de escape de caracteres HTML para prevenir XSS
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}
</script>
