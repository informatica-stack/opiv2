<?php 
// mis_solicitudes.php - Vista UI (V5.3 - Modulares e Ítems)
require_once __DIR__ . '/mis_solicitudes_controller.php'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php 
    $titulo_pagina = "Mis Solicitudes";
    include __DIR__ . '/head.php'; 
    ?>
</head>
<body class="bg-slate-50 text-slate-800 font-sans pb-20">

    <?php include __DIR__ . '/nav.php'; ?>

    <div class="container mt-4 px-3 px-md-4">
        
        <!-- CABECERA PRINCIPAL -->
        <div class="row align-items-center mb-4 g-3">
            <div class="col-12 col-md">
                <h1 class="h3 fw-bold text-dark mb-1">Mis Solicitudes</h1>
                <p class="text-muted small mb-0">Historial y gestión de sus requerimientos.</p>
            </div>
            <div class="col-12 col-md-auto d-flex flex-wrap gap-2">
                <button onclick="toggleFiltros()" class="btn btn-outline-secondary btn-sm shadow-sm d-flex align-items-center gap-1.5">
                    <i class="bi bi-funnel"></i>
                    Filtros
                </button>
                <a href="nueva_solicitud.php" class="btn btn-primary btn-sm shadow-sm d-flex align-items-center gap-1.5">
                    <i class="bi bi-plus-lg"></i>
                    Nueva Solicitud
                </a>
            </div>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert <?= $tipo_mensaje === 'error' ? 'alert-danger' : 'alert-success' ?> d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="bi <?= $tipo_mensaje === 'error' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill' ?>"></i>
                <div><?= htmlspecialchars($mensaje) ?></div>
            </div>
        <?php endif; ?>

        <!-- PANEL DE FILTROS -->
        <div id="filtroPanel" class="card shadow-sm mb-4 <?= ($f_q || $f_tipo || $f_estado || $f_desde || $f_hasta) ? '' : 'd-none' ?>">
            <div class="card-body p-3">
                <form method="GET" action="mis_solicitudes.php">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Buscar (ID o Título)</label>
                            <input type="text" name="f_q" value="<?= htmlspecialchars($f_q) ?>" class="form-control form-control-sm">
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Tipo de Compra</label>
                            <select name="f_tipo" class="form-select form-select-sm bg-white">
                                <option value="">Todos</option>
                                <?php foreach($tipos_compra_filtro as $t): ?>
                                    <option value="<?= $t['id'] ?>" <?= $f_tipo==$t['id']?'selected':'' ?>><?= htmlspecialchars($t['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Estado</label>
                            <select name="f_estado" class="form-select form-select-sm bg-white">
                                <option value="">Todos</option>
                                <?php foreach($estados_filtro as $e): ?>
                                    <option value="<?= $e['codigo'] ?>" <?= $f_estado==$e['codigo']?'selected':'' ?>><?= htmlspecialchars($e['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Desde</label>
                                    <input type="date" name="f_desde" value="<?= htmlspecialchars($f_desde) ?>" class="form-control form-control-sm text-secondary">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Hasta</label>
                                    <input type="date" name="f_hasta" value="<?= htmlspecialchars($f_hasta) ?>" class="form-control form-control-sm text-secondary">
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-2 d-flex gap-2 justify-content-end mt-2 mt-lg-0">
                            <a href="mis_solicitudes.php" class="btn btn-light btn-sm w-100 fw-bold border">Limpiar</a>
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold shadow-sm">Aplicar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- TABLA PRINCIPAL DE SOLICITUDES -->
        <div class="card shadow-sm border-light mb-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0" style="min-width: 1000px;">
                    <thead class="table-light text-uppercase small text-secondary">
                        <tr>
                            <th class="p-3 text-nowrap" style="width: 180px;">ID / Fecha / Prioridad</th>
                            <th class="p-3" style="min-width: 250px;">Trámite / Requerimiento</th>
                            <th class="p-3 text-nowrap" style="width: 150px;">Clasificación</th>
                            <th class="p-3 text-nowrap" style="width: 180px;">Estado Actual</th>
                            <th class="p-3 text-end text-nowrap" style="width: 150px;">Monto Estimado</th>
                            <th class="p-3 text-center text-nowrap" style="width: 150px;">Gestión</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($mis_solicitudes) > 0): ?>
                            <?php foreach($mis_solicitudes as $row): ?>
                            <tr>
                                <td class="p-3 text-nowrap">
                                    <button type="button" onclick="abrirModalVerItems(<?= htmlspecialchars(json_encode($row['items_detalle']), ENT_QUOTES, 'UTF-8') ?>, '<?= $row['codigo_interno'] ?>')" class="btn btn-link p-0 text-start font-monospace fw-bold text-primary text-decoration-underline mb-1">
                                        <?= htmlspecialchars($row['codigo_interno']) ?>
                                    </button>
                                    <div class="text-muted small" style="font-size: 11px;">
                                        <i class="bi bi-clock me-1"></i>
                                        <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?>
                                    </div>
                                    <span class="badge border mt-2 d-inline-block <?= $row['prioridad_css'] ?>" style="font-size: 10px;">
                                        <?= htmlspecialchars($row['prioridad_nombre']) ?>
                                    </span>
                                </td>

                                <td class="p-3">
                                    <div class="fw-bold text-dark mb-1 leading-snug break-words" style="max-width: 450px;">
                                        <?= htmlspecialchars($row['titulo_compra'] ?? 'Sin Título') ?>
                                    </div>
                                    <div class="text-secondary small mb-2 break-words" style="max-width: 450px; font-size: 11px; line-height: 1.4;">
                                        <?= htmlspecialchars($row['motivo_compra']) ?>
                                    </div>
                                    <div class="text-uppercase text-muted fw-bold mb-2" style="font-size: 9px; letter-spacing: 0.5px;">
                                        <i class="bi bi-tag-fill me-1"></i>
                                        CC: <?= htmlspecialchars($row['cc_nombre']) ?>
                                    </div>
                                    
                                    <?php if($row['docs_adjuntos']): 
                                        $docs_array = explode('||', $row['docs_adjuntos']);
                                        $docs_count = count($docs_array);
                                        $docs_json = htmlspecialchars(json_encode($docs_array), ENT_QUOTES, 'UTF-8');
                                    ?>
                                        <div class="mt-2">
                                            <button type="button" onclick="abrirModalAdjuntos(<?= $docs_json ?>, '<?= htmlspecialchars($row['codigo_interno']) ?>', <?= $row['id'] ?>)" class="btn btn-outline-primary btn-sm py-1 px-2.5 d-inline-flex align-items-center gap-1.5 font-bold" style="font-size: 10px;">
                                                <i class="bi bi-paperclip"></i>
                                                Ver Archivos (<?= $docs_count ?>)
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td class="p-3 text-nowrap">
                                    <span class="badge bg-light text-dark border px-2 py-1.5 fw-bold" style="font-size: 11px;">
                                        <?= htmlspecialchars($row['tipo_nombre']) ?>
                                    </span>
                                </td>

                                <td class="p-3 text-nowrap">
                                    <span class="badge border px-2 py-1.5 text-uppercase <?= color_estado($row['estado_actual']) ?>" style="font-size: 11px;">
                                        <?= htmlspecialchars($row['estado_nombre']) ?>
                                    </span>

                                    <div class="mt-1.5">
                                        <button type="button" onclick="verTrazabilidad(<?= (int)$row['id'] ?>)" class="btn btn-link p-0 text-decoration-none text-secondary d-flex align-items-center gap-1" style="font-size: 11px;">
                                            <i class="bi bi-clock-history text-primary"></i>
                                            Ver Historial
                                        </button>
                                    </div>
                                    
                                    <?php if($row['folio_opi']): ?>
                                        <div class="mt-1 fw-bold text-success d-flex align-items-center gap-1" style="font-size: 11px;">
                                            <i class="bi bi-check-circle"></i>
                                            OPI: <?= htmlspecialchars($row['folio_opi']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if($row['orden_compra_numero']): ?>
                                        <div class="mt-1 fw-bold text-primary d-flex align-items-center gap-1" style="font-size: 11px;">
                                            <i class="bi bi-cart-check"></i>
                                            N° OC: <?= htmlspecialchars($row['orden_compra_numero']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if($row['id_licitacion']): ?>
                                        <div class="mt-1 fw-bold text-purple d-flex align-items-center gap-1" style="font-size: 11px;">
                                            <i class="bi bi-diagram-3"></i>
                                            Licitación: <?= htmlspecialchars($row['id_licitacion']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if($row['id_contrato_suministro']): ?>
                                        <div class="mt-1 fw-bold text-info d-flex align-items-center gap-1" style="font-size: 11px;">
                                            <i class="bi bi-file-earmark-text"></i>
                                            Suministro: <?= htmlspecialchars($row['id_contrato_suministro']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td class="p-3 text-end fw-bold text-dark text-base text-nowrap font-monospace">
                                    <?= money($row['monto_definitivo'] ?? $row['monto_estimado']) ?>
                                </td>

                                <td class="p-3 text-center text-nowrap">
                                    <?php if (in_array($row['estado_actual'], ['BORRADOR', 'EN_REVISION_JEFATURA', 'EN_CORRECCION'])): ?>
                                        <div class="d-flex flex-column gap-1.5 align-items-center">
                                            <a href="editar_solicitud.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm text-dark px-3 py-1.5 fw-bold shadow-sm d-flex align-items-center gap-1">
                                               <i class="bi bi-pencil-square"></i>
                                               Editar
                                            </a>
                                            <a href="mis_solicitudes.php?anular_id=<?= $row['id'] ?>" onclick="return confirm('¿Está seguro de anular definitivamente esta solicitud?')" class="btn btn-outline-danger btn-sm px-3 py-1 fw-semibold" style="font-size: 11px;">
                                                <i class="bi bi-x-circle me-1"></i>Anular
                                            </a>
                                        </div>

                                    <?php elseif ($row['estado_actual'] === 'EN_EVALUACION_OFERTAS'): ?>
                                        <button onclick="abrirModalAdjudicar(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)" class="btn btn-primary btn-sm px-3 py-1.5 fw-bold shadow-sm d-flex align-items-center gap-1">
                                            <i class="bi bi-star"></i>
                                            Adjudicar
                                        </button>

                                    <?php else: ?>
                                        <span class="text-muted small fst-italic">En Proceso...</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="p-5 text-center text-muted italic">
                                    No se encontraron solicitudes con estos criterios.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- PAGINACIÓN -->
            <?php if($total_pages > 1): ?>
            <div class="card-footer bg-light p-3 d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3 border-top">
                <span class="text-muted small">Mostrando página <?= $page ?> de <?= $total_pages ?></span>
                <nav aria-label="Navegación de solicitudes">
                    <ul class="pagination pagination-sm mb-0">
                        <?php if($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= $base_url . ($page - 1) ?>">&laquo; Ant</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= $base_url . $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= $base_url . ($page + 1) ?>">Sig &raquo;</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL ADJUNTOS (BOOTSTRAP 5) -->
    <div class="modal fade" id="modalAdjuntos" tabindex="-1" aria-labelledby="modalAdjuntosLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-dark" id="modalAdjuntosLabel">Documentos Adjuntos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="bg-light border p-3 rounded-3 mb-3 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
                        <div>
                            <span class="text-uppercase text-muted fw-bold" style="font-size: 9px; letter-spacing: 0.5px;">Expediente:</span>
                            <div id="modalAdjuntosCodigo" class="font-monospace fw-bold text-dark"></div>
                        </div>
                        <a id="btnDescargarZip" href="#" class="btn btn-primary btn-sm fw-bold shadow-sm d-flex align-items-center gap-1.5 w-100 w-sm-auto justify-content-center">
                            <i class="bi bi-download"></i>
                            Bajar ZIP
                        </a>
                    </div>
                    
                    <div id="modalAdjuntosLista" class="d-flex flex-column gap-2 overflow-y-auto" style="max-height: 350px;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar Visor</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DETALLE ÍTEMS (BOOTSTRAP 5) -->
    <div class="modal fade" id="modalVerItems" tabindex="-1" aria-labelledby="modalVerItemsLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-3 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-dark" id="modalVerItemsLabel">Detalle de Ítems del Requerimiento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="bg-light border p-3 rounded-3 mb-3">
                        <span class="text-uppercase text-muted fw-bold" style="font-size: 9px; letter-spacing: 0.5px;">Expediente:</span>
                        <div id="modalVerItemsCodigo" class="font-monospace fw-bold text-primary"></div>
                    </div>
                    
                    <div class="table-responsive rounded-3 border">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-uppercase small text-secondary">
                                <tr>
                                    <th class="p-3">Descripción del Producto/Servicio</th>
                                    <th class="p-3 text-center" style="width: 100px;">Cant.</th>
                                    <th class="p-3 text-end" style="width: 150px;">Valor Unit. Ingresado</th>
                                    <th class="p-3 text-end" style="width: 160px;">Total Línea</th>
                                </tr>
                            </thead>
                            <tbody id="modalVerItemsBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar Visor</button>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/modal_adjudicacion.php'; ?>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
        function escapeHTML(str) { 
            return str.replace(/[&<>'"]/g, tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag])); 
        }

        const formatter = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', minimumFractionDigits: 0 });
        function formatCurrency(value) {
            return formatter.format(value);
        }

        // Instancias de Modal de Bootstrap
        let modalAdjuntosInstance = null;
        let modalVerItemsInstance = null;

        document.addEventListener('DOMContentLoaded', () => {
            modalAdjuntosInstance = new bootstrap.Modal(document.getElementById('modalAdjuntos'));
            modalVerItemsInstance = new bootstrap.Modal(document.getElementById('modalVerItems'));
        });

        // Alternar visualización de Filtros
        function toggleFiltros() {
            const panel = document.getElementById('filtroPanel');
            if (!panel) return;
            panel.classList.toggle('d-none');
        }

        // Funciones del Modal Adjuntos
        function abrirModalAdjuntos(docsArray, codigo, expId) {
            document.getElementById('modalAdjuntosCodigo').innerText = codigo;
            document.getElementById('btnDescargarZip').href = '?descargar_zip=' + expId;
            
            const listaContainer = document.getElementById('modalAdjuntosLista');
            listaContainer.innerHTML = ''; 

            docsArray.forEach(docStr => {
                const parts = docStr.split('::');
                if(parts.length >= 4) {
                    const ruta = parts[0];
                    const nombreOriginal = parts[1];
                    const tipoDoc = parts[2].replace(/_/g, ' '); 
                    const fecha = parts[3];
                    
                    const isAnulada = parts[2] === 'OPI_ANULADA';
                    const titleClass = isAnulada ? 'text-danger text-decoration-line-through' : 'text-dark fw-bold';
                    const subtitleClass = isAnulada ? 'text-danger' : 'text-muted';
                    
                    const link = document.createElement('a');
                    link.href = ruta;
                    link.target = '_blank';
                    link.title = nombreOriginal;
                    link.className = 'd-flex align-items-center justify-content-between p-2.5 bg-light border rounded-3 text-decoration-none hover-bg-gray transition mb-2';
                    
                    link.innerHTML = `
                        <div class="d-flex align-items-center gap-2 min-w-0 flex-1">
                            <i class="bi bi-file-earmark-text text-primary fs-5 shrink-0"></i>
                            <div class="text-truncate flex-1">
                                <p class="mb-0 text-truncate small ${titleClass}" style="max-width: 320px;">${nombreOriginal}</p>
                                <p class="mb-0 small text-uppercase tracking-wide ${subtitleClass}" style="font-size: 9px;">${tipoDoc} - ${fecha}</p>
                            </div>
                        </div>
                        <i class="bi bi-arrow-right-short text-secondary fs-4 shrink-0"></i>
                    `;
                    listaContainer.appendChild(link);
                }
            });

            modalAdjuntosInstance.show();
        }

        // Función para ver ítems
        function abrirModalVerItems(items, codigo) {
            document.getElementById('modalVerItemsCodigo').innerText = codigo;
            const tbody = document.getElementById('modalVerItemsBody');
            tbody.innerHTML = '';
            
            if (!items || items.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" class="p-4 text-center text-muted italic">No hay ítems registrados.</td></tr>`;
            } else {
                items.forEach(item => {
                    const cant = parseFloat(item.cantidad);
                    const prec = parseFloat(item.precio_unitario);
                    const tr = `
                        <tr class="align-middle">
                            <td class="p-3 text-secondary fw-semibold small">${escapeHTML(item.descripcion)}</td>
                            <td class="p-3 text-center fw-bold text-dark">${cant} <span class="text-muted d-block" style="font-size: 10px;">${item.unidad_medida}</span></td>
                            <td class="p-3 text-end text-muted font-monospace">${formatCurrency(prec)}</td>
                            <td class="p-3 text-end fw-bold text-dark font-monospace">${formatCurrency(cant * prec)}</td>
                        </tr>
                    `;
                    tbody.innerHTML += tr;
                });
            }
            
            modalVerItemsInstance.show();
        }
    </script>
</body>
</html>