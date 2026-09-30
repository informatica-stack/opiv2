<?php
// firmador_institucional.php - Módulo Firmador Institucional para Firma al Paso de Documentos PDF
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/firmagob_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Control de Permisos por Rol
$rol_usuario = $_SESSION['user_rol'] ?? '';
$es_jefe = intval($_SESSION['es_jefe'] ?? 0);
$roles_permitidos = ['JEFE_UNIDAD', 'ADMIN_MUNICIPAL', 'SYSADMIN', 'FINANZAS', 'PRESUPUESTO'];
$es_autorizado = in_array($rol_usuario, $roles_permitidos) || ($es_jefe === 1);

if (!isset($_SESSION['user_id']) || !$es_autorizado) {
    header("Location: index.php");
    exit;
}

$user_name = $_SESSION['user_name'] ?? ($_SESSION['user_nombre'] ?? 'Funcionario');
$user_rut = $_SESSION['user_rut'] ?? '';
$user_cargo = $_SESSION['user_cargo'] ?? ($rol_usuario === 'SYSADMIN' ? 'Administrador del Sistema' : $rol_usuario);
$es_subrogante = !empty($_SESSION['es_subrogante']);
$subrogado_nombre = $_SESSION['subrogado_nombre'] ?? '';
$subrogado_cargo = $_SESSION['subrogado_cargo'] ?? '';

$csrf_token = $_SESSION['csrf_token'] ?? '';
$modo_firmagob = FIRMAGOB_MODO;
$ambiente_firmagob = FIRMAGOB_AMBIENTE;

?><!DOCTYPE html>
<html lang="es">
<head>
    <?php 
    $titulo_pagina = "Firmador Institucional al Paso - FirmaGob";
    include __DIR__ . '/head.php'; 
    ?>
    <!-- PDF.js para renderizado e interacción de alta precisión -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <style>
        .firmador-workspace {
            min-height: calc(100vh - 180px);
        }
        .dropzone-firmador {
            border: 2px dashed #0d6efd;
            border-radius: 12px;
            background: #f8fafd;
            transition: all 0.25s ease-in-out;
            cursor: pointer;
        }
        .dropzone-firmador:hover, .dropzone-firmador.dragover {
            background: #eef5ff;
            border-color: #0b5ed7;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(13, 110, 253, 0.12);
        }
        .pdf-viewer-card {
            background: #4a4d52;
            border-radius: 10px;
            position: relative;
            overflow: auto;
            max-height: 820px;
            min-height: 580px;
            box-shadow: inset 0 2px 6px rgba(0,0,0,0.25);
        }
        .pdf-canvas-wrapper {
            position: relative;
            margin: 20px auto;
            box-shadow: 0 6px 20px rgba(0,0,0,0.35);
            background: #ffffff;
            display: inline-block;
            user-select: none;
        }
        #pdfCanvas {
            display: block;
        }
        /* Estampa Interactiva Flotante */
        .stamp-box-draggable {
            position: absolute;
            cursor: grab;
            border: 2px dashed #0d6efd;
            background: rgba(255, 255, 255, 0.94);
            border-radius: 6px;
            box-shadow: 0 4px 18px rgba(13, 110, 253, 0.3);
            touch-action: none;
            z-index: 50;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 8px 10px;
            box-sizing: border-box;
            min-width: 190px;
            min-height: 85px;
            backdrop-filter: blur(4px);
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .stamp-box-draggable:active {
            cursor: grabbing;
            border-color: #ff9800;
            box-shadow: 0 6px 20px rgba(255, 152, 0, 0.4);
        }
        .stamp-box-draggable.hidden-stamp {
            display: none !important;
        }
        .stamp-header-badge {
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #0d6efd;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .stamp-name {
            font-size: 11px;
            font-weight: 700;
            color: #1a1a1a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.2;
        }
        .stamp-meta {
            font-size: 8.5px;
            color: #555;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .stamp-coords-pill {
            font-family: monospace;
            font-size: 8px;
            background: rgba(13, 110, 253, 0.12);
            color: #0b5ed7;
            padding: 1px 4px;
            border-radius: 3px;
        }
        .btn-pos-quick {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 5px;
        }
        @keyframes pulseSign {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.7); }
            50% { transform: scale(1.03); box-shadow: 0 0 0 8px rgba(13, 110, 253, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(13, 110, 253, 0); }
        }
        .pulse-anim {
            animation: pulseSign 0.6s ease-in-out 2;
        }
        /* Barra Flotante de Acción Inferior en Móviles */
        .mobile-floating-sign-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(10px);
            border-top: 1px solid #dee2e6;
            padding: 10px 16px;
            z-index: 1040;
            box-shadow: 0 -4px 16px rgba(0,0,0,0.12);
            align-items: center;
            justify-content: space-between;
        }

        /* Optimizaciones Específicas para Pantallas Móviles (< 768px) */
        @media (max-width: 768px) {
            .saas-container {
                padding-left: 12px !important;
                padding-right: 12px !important;
            }
            .page-header-row {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 10px;
            }
            .pdf-viewer-card {
                min-height: 380px !important;
                max-height: 60vh !important;
            }
            .pdf-canvas-wrapper {
                margin: 8px auto !important;
            }
            .stamp-box-draggable {
                min-width: 140px !important;
                min-height: 66px !important;
                padding: 4px 6px !important;
            }
            .stamp-header-badge {
                font-size: 7.5px !important;
            }
            .stamp-name {
                font-size: 9.5px !important;
            }
            .stamp-meta {
                font-size: 7.2px !important;
            }
            .stamp-coords-pill {
                font-size: 6.5px !important;
                padding: 0 3px !important;
            }
            .btn-pos-quick {
                font-size: 10px !important;
                padding: 2px 6px !important;
            }
            body.has-mobile-bar {
                padding-bottom: 70px;
            }
        }
</head>
<body class="bg-light">

    <?php include __DIR__ . '/nav.php'; ?>

    <div class="saas-container py-4">
        
        <!-- HEADER DE PÁGINA -->
        <div class="page-header-row mb-4">
            <div>
                <div class="breadcrumbs">
                    <a href="index.php">Inicio</a>
                    <i class="bi bi-chevron-right"></i>
                    <span class="current">Firmador Institucional</span>
                </div>
                <div class="page-title mt-1">
                    <h2 class="d-flex align-items-center gap-2">
                        <i class="bi bi-pen-fill text-primary"></i>
                        Firmador Institucional al Paso
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-xs">FirmaGob v2</span>
                    </h2>
                    <p class="text-secondary small mb-0">Firma digitalmente con Firma Electrónica Avanzada (FEA) cualquier documento PDF con total libertad de ubicación.</p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="bg-white border rounded-3 px-3 py-2 text-end shadow-xs">
                    <div class="text-xs text-secondary fw-semibold">Modalidad Activa</div>
                    <div class="small fw-bold text-dark d-flex align-items-center gap-1 justify-content-end">
                        <span class="badge rounded-pill bg-<?= $modo_firmagob === 'DESATENDIDA' ? 'success' : 'warning' ?> p-1"></span>
                        <?= $modo_firmagob === 'DESATENDIDA' ? 'Desatendida (1-Clic)' : 'Atendida (OTP)' ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- PASO 1: ZONA DE CARGA INICIAL (DROPZONE) -->
        <div id="seccionCarga" class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3 p-md-5">
                <div class="dropzone-firmador p-3 p-sm-4 p-md-5 text-center" id="dropzoneFirmador">
                    <input type="file" id="inputArchivoPdf" accept="application/pdf" class="d-none">
                    <div class="mb-3">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle shadow-xs" style="width: 64px; height: 64px;">
                            <i class="bi bi-cloud-arrow-up-fill fs-2"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold text-dark mb-1 fs-5 fs-md-4">Arrastra aquí tu documento PDF o haz clic para explorar</h4>
                    <p class="text-secondary small mb-3">Soporta cualquier documento oficial (Decretos, Resoluciones, Informes, Certificados, etc.)</p>
                    
                    <div class="d-flex flex-wrap align-items-center justify-content-center gap-2 mb-2">
                        <span class="badge bg-light text-dark border px-2.5 py-1.5 small fw-normal">
                            <i class="bi bi-file-earmark-pdf-fill text-danger me-1"></i> Formato: PDF
                        </span>
                        <span class="badge bg-light text-dark border px-2.5 py-1.5 small fw-normal">
                            <i class="bi bi-hdd-fill text-primary me-1"></i> Límite FirmaGob: Máx 5 MB
                        </span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 small fw-normal">
                            <i class="bi bi-shield-check me-1"></i> 100% Efímero y Privado
                        </span>
                    </div>

                    <div class="mt-3">
                        <button type="button" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold shadow-xs" onclick="document.getElementById('inputArchivoPdf').click();">
                            <i class="bi bi-folder2-open me-1.5"></i> Seleccionar Archivo PDF
                        </button>
                    </div>
                </div>

                <!-- Bloques Informativos de Seguridad y Ley -->
                <div class="row g-3 mt-4 pt-2">
                    <div class="col-md-4">
                        <div class="d-flex align-items-start gap-2.5 p-3 rounded-3 bg-white border">
                            <i class="bi bi-award-fill text-primary fs-4"></i>
                            <div>
                                <div class="fw-bold text-dark text-xs">Firma Electrónica Avanzada</div>
                                <div class="text-muted text-xs">Validez jurídica plena conforme a la Ley N° 19.799 del Estado de Chile.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-start gap-2.5 p-3 rounded-3 bg-white border">
                            <i class="bi bi-cursor-fill text-success fs-4"></i>
                            <div>
                                <div class="fw-bold text-dark text-xs">Ubicación 100% Libre</div>
                                <div class="text-muted text-xs">Arrastra la estampa a cualquier hoja y posición exacta del documento.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-start gap-2.5 p-3 rounded-3 bg-white border">
                            <i class="bi bi-trash3-fill text-secondary fs-4"></i>
                            <div>
                                <div class="fw-bold text-dark text-xs">Sin Residuos en el Servidor</div>
                                <div class="text-muted text-xs">El documento se firma al vuelo y se destruye de inmediato tras su descarga.</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- PASO 2: TALLER INTERACTIVO DE FIRMA (OCULTO INICIALMENTE) -->
        <div id="seccionTaller" class="d-none">
            
            <div class="row g-3">
                
                <!-- COLUMNA PRINCIPAL: VISOR Y ESTAMPADO INTERACTIVO -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        
                        <!-- Barra de Herramientas del Visor -->
                        <div class="card-header bg-white border-bottom py-2 px-2.5 px-md-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                            
                            <!-- Paginación -->
                            <div class="d-flex align-items-center gap-1">
                                <button class="btn btn-outline-secondary btn-sm px-1.5 py-1" id="btnPaginaPrimera" title="Primera Página">
                                    <i class="bi bi-chevron-double-left"></i>
                                </button>
                                <button class="btn btn-outline-secondary btn-sm px-1.5 py-1" id="btnPaginaAnterior" title="Página Anterior">
                                    <i class="bi bi-chevron-left"></i>
                                </button>
                                <select id="selectPaginaDirecta" class="form-select form-select-sm py-1 px-1.5 fw-semibold border-secondary-subtle" style="width: auto; min-width: 105px; max-width: 135px; font-size: 11px; cursor: pointer;"></select>
                                <button class="btn btn-outline-secondary btn-sm px-1.5 py-1" id="btnPaginaSiguiente" title="Página Siguiente">
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                                <button class="btn btn-outline-secondary btn-sm px-1.5 py-1" id="btnPaginaUltima" title="Última Página">
                                    <i class="bi bi-chevron-double-right"></i>
                                </button>
                            </div>

                            <!-- Botones de Alineación Rápida de Estampa (Visibles en Móvil y Desktop) -->
                            <div class="d-flex align-items-center gap-1 overflow-x-auto py-0.5" id="grupoPosicionesRapidas">
                                <span class="text-muted text-xs fw-semibold me-1 d-none d-md-inline">Posición:</span>
                                <button type="button" class="btn btn-light btn-pos-quick border text-nowrap" onclick="posicionarEstampa('bottom-right')" title="Inferior Derecha">
                                    <i class="bi bi-arrow-down-right"></i> Inf. Der.
                                </button>
                                <button type="button" class="btn btn-light btn-pos-quick border text-nowrap" onclick="posicionarEstampa('bottom-center')" title="Inferior Centro">
                                    <i class="bi bi-arrow-down"></i> Centro
                                </button>
                                <button type="button" class="btn btn-light btn-pos-quick border text-nowrap" onclick="posicionarEstampa('bottom-left')" title="Inferior Izquierda">
                                    <i class="bi bi-arrow-down-left"></i> Inf. Izq.
                                </button>
                            </div>

                            <!-- Controles de Zoom -->
                            <div class="d-flex align-items-center gap-1">
                                <button class="btn btn-outline-secondary btn-sm px-1.5 py-1" id="btnZoomOut" title="Reducir">
                                    <i class="bi bi-zoom-out"></i>
                                </button>
                                <span class="small fw-semibold px-1 text-muted" id="labelZoom" style="font-size: 11px;">100%</span>
                                <button class="btn btn-outline-secondary btn-sm px-1.5 py-1" id="btnZoomIn" title="Aumentar">
                                    <i class="bi bi-zoom-in"></i>
                                </button>
                                <button class="btn btn-outline-secondary btn-sm px-1.5 py-1" id="btnAjustarAncho" title="Ajustar al Ancho">
                                    <i class="bi bi-arrows-expand"></i>
                                </button>
                            </div>

                        </div>

                        <!-- Área de Renderizado Canvas PDF.js con Estampa Flotante -->
                        <div class="card-body p-2 p-md-3 bg-secondary-subtle">
                            <div class="pdf-viewer-card text-center" id="pdfViewerCard">
                                
                                <div class="pdf-canvas-wrapper" id="pdfCanvasWrapper">
                                    <!-- Canvas del PDF -->
                                    <canvas id="pdfCanvas"></canvas>

                                    <!-- Caja Flotante de Estampa Arrastrable -->
                                    <div class="stamp-box-draggable" id="stampBox" title="Arrastra esta estampa a cualquier lugar de la hoja">
                                        <div class="stamp-header-badge">
                                            <span><i class="bi bi-shield-lock-fill me-1"></i> Firma Digital FEA</span>
                                            <span class="stamp-coords-pill" id="stampCoordsPill">Pág 1 | X: 40, Y: 50</span>
                                        </div>
                                        <div class="my-auto py-1">
                                            <div class="stamp-name" id="stampPreviewNombre"><?= htmlspecialchars($user_name) ?></div>
                                            <div class="stamp-meta" id="stampPreviewMeta">RUN: <?= htmlspecialchars($user_rut) ?> | <?= htmlspecialchars($user_cargo) ?></div>
                                            <div class="stamp-meta text-muted" style="font-size: 8px;">Ley N° 19.799 Firma Electrónica</div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center text-muted" style="font-size: 7.5px;">
                                            <span><?= htmlspecialchars(FIRMAGOB_ENTITY) ?></span>
                                            <span><i class="bi bi-arrows-move"></i> Mover</span>
                                        </div>
                                    </div>

                                </div>

                            </div>
                        </div>

                        <!-- Footer con Coordenadas en Vivo -->
                        <div class="card-footer bg-white border-top py-2 px-3 d-flex align-items-center justify-content-between text-xs text-muted">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-info-circle text-primary"></i>
                                <span>Arrastra la caja azul sobre el documento para fijar el lugar del timbre.</span>
                            </div>
                            <div class="font-monospace text-dark fw-bold" id="infoCoordenadasCalculadas">
                                Coordenadas PDF: llx: 40, lly: 50, urx: 210, ury: 130
                            </div>
                        </div>

                    </div>
                </div>

                <!-- COLUMNA LATERAL: DETALLES, CONFIGURACIÓN Y ACCIÓN DE FIRMA -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 mb-3">
                        <div class="card-header bg-white border-bottom py-3 px-3.5">
                            <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-check-fill text-primary"></i>
                                Documento en Proceso
                            </h6>
                        </div>
                        <div class="card-body p-3.5">
                            
                            <!-- Metadatos del Documento -->
                            <div class="p-3 bg-light rounded-3 border mb-3">
                                <div class="d-flex align-items-center gap-2 mb-1.5">
                                    <i class="bi bi-file-earmark-pdf text-danger fs-3"></i>
                                    <div class="overflow-hidden">
                                        <div class="fw-bold text-dark text-truncate small" id="docInfoNombre">-</div>
                                        <div class="text-muted text-xs" id="docInfoTamano">-</div>
                                    </div>
                                </div>
                                <div class="text-xs text-muted font-monospace text-truncate" id="docInfoHash" title="Hash SHA-256 Original">
                                    SHA256: Calculando...
                                </div>
                            </div>

                            <button type="button" class="btn btn-outline-danger btn-sm w-100 rounded-3 mb-3" onclick="cancelarYReiniciar()">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Descartar y Cargar Otro
                            </button>

                            <hr class="my-3 opacity-25">

                            <!-- Formulario de Configuración de Firma -->
                            <form id="formFirmarDocumento" onsubmit="ejecutarFirma(event)">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <input type="hidden" id="tokenDocHidden" name="token_doc" value="">
                                <input type="hidden" id="pageHidden" name="page" value="1">
                                <input type="hidden" id="llxHidden" name="llx" value="40">
                                <input type="hidden" id="llyHidden" name="lly" value="50">
                                <input type="hidden" id="urxHidden" name="urx" value="210">
                                <input type="hidden" id="uryHidden" name="ury" value="130">

                                <!-- Tipo de Estampado -->
                                <div class="mb-3">
                                    <label class="form-label text-xs fw-bold text-secondary text-uppercase mb-2">Modalidad de Estampa</label>
                                    <div class="d-flex flex-column gap-2">
                                        <label class="form-check p-2.5 border rounded-3 bg-white d-flex align-items-center gap-2 cursor-pointer shadow-2xs">
                                            <input class="form-check-input ms-0 mt-0" type="radio" name="tipo_firma" id="tipoFirmaVisible" value="VISIBLE" checked onchange="alternarTipoFirma()">
                                            <div>
                                                <div class="fw-semibold text-dark small">Firma Visible (Con Estampa Oficial)</div>
                                                <div class="text-muted text-xs">Inserta el timbre gráfico institucional con sus datos legales.</div>
                                            </div>
                                        </label>

                                        <label class="form-check p-2.5 border rounded-3 bg-white d-flex align-items-center gap-2 cursor-pointer shadow-2xs">
                                            <input class="form-check-input ms-0 mt-0" type="radio" name="tipo_firma" id="tipoFirmaInvisible" value="INVISIBLE" onchange="alternarTipoFirma()">
                                            <div>
                                                <div class="fw-semibold text-dark small">Firma Invisible (Solo Criptográfica)</div>
                                                <div class="text-muted text-xs">Certifica el PDF sin alterar ninguna página visualmente.</div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Subrogancia (si aplica) -->
                                <?php if($es_subrogante): ?>
                                    <div class="mb-3 p-3 bg-warning-subtle border border-warning-subtle rounded-3">
                                        <div class="d-flex align-items-center gap-1.5 text-warning-emphasis fw-bold text-xs mb-1.5">
                                            <i class="bi bi-person-exclamation fs-6"></i> Subrogancia Habilitada
                                        </div>
                                        <div class="small mb-2">Usted es suplente de: <strong><?= htmlspecialchars($subrogado_nombre) ?></strong></div>
                                        
                                        <select class="form-select form-select-sm" name="firmar_como" id="selectFirmarComo" onchange="cambiarIdentidadFirmante()">
                                            <option value="TITULAR">Firmar a mi nombre propio (<?= htmlspecialchars($user_name) ?>)</option>
                                            <option value="SUBROGANTE" selected>Firmar como Suplente (S) de <?= htmlspecialchars($subrogado_nombre) ?></option>
                                        </select>
                                    </div>
                                <?php else: ?>
                                    <input type="hidden" name="firmar_como" value="TITULAR">
                                <?php endif; ?>

                                <!-- Datos del Firmante Activo -->
                                <div class="mb-3 p-2.5 bg-light rounded-3 border">
                                    <div class="text-xs text-muted fw-semibold mb-1">Firmante en Ejercicio:</div>
                                    <div class="fw-bold text-dark small" id="labelFirmanteNombre"><?= htmlspecialchars($user_name) ?></div>
                                    <div class="text-muted text-xs" id="labelFirmanteMeta">RUN: <?= htmlspecialchars($user_rut) ?> | <?= htmlspecialchars($user_cargo) ?></div>
                                </div>

                                <!-- Descripción opcional -->
                                <div class="mb-3">
                                    <label for="inputDescripcion" class="form-label text-xs fw-bold text-secondary text-uppercase mb-1">Descripción del Documento</label>
                                    <input type="text" class="form-control form-control-sm" id="inputDescripcion" name="descripcion" placeholder="Ej: Decreto Alcaldicio N° 123">
                                </div>

                                <!-- Campo OTP (Solo si modalidad es ATENDIDA) -->
                                <?php if($modo_firmagob === 'ATENDIDA'): ?>
                                    <div class="mb-3 p-3 bg-primary-subtle border border-primary-subtle rounded-3">
                                        <label for="inputOtp" class="form-label text-xs fw-bold text-primary text-uppercase mb-1 d-flex align-items-center gap-1">
                                            <i class="bi bi-key-fill"></i> Código OTP (Google Authenticator)
                                        </label>
                                        <input type="text" class="form-control form-control-sm text-center font-monospace fs-6 fw-bold" id="inputOtp" name="otp" placeholder="123456" maxlength="6" pattern="[0-9]{6}" required>
                                        <div class="text-muted text-xs mt-1">Ingrese el código dinámico de 6 dígitos de su aplicación móvil.</div>
                                    </div>
                                <?php endif; ?>

                                <!-- Botón de Ejecución Principal -->
                                <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-3 fw-bold shadow-xs d-flex align-items-center justify-content-center gap-2" id="btnEjecutarFirma">
                                    <i class="bi bi-shield-check fs-5"></i>
                                    <span>Estampar y Firmar con FirmaGob</span>
                                </button>

                            </form>

                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <!-- MODAL DE PROCESAMIENTO / LOADING -->
    <div class="modal fade" id="modalProcesando" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg rounded-4 text-center p-4">
                <div class="spinner-border text-primary my-3" role="status" style="width: 3.5rem; height: 3.5rem;"></div>
                <h5 class="fw-bold mb-1">Firmando Documento...</h5>
                <p class="text-muted small mb-0">Conectando con la plataforma oficial FirmaGob v2 del Estado de Chile.</p>
            </div>
        </div>
    </div>

    <!-- MODAL DE ÉXITO Y DESCARGA INMEDIATA -->
    <div class="modal fade" id="modalExitoFirma" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="bg-success text-white p-4 text-center">
                    <div class="d-inline-flex align-items-center justify-content-center bg-white text-success rounded-circle mb-2 shadow-xs" style="width: 64px; height: 64px;">
                        <i class="bi bi-check-lg fs-1 fw-bold"></i>
                    </div>
                    <h4 class="fw-bold mb-0">¡Firma Aplicada con Éxito!</h4>
                    <p class="small text-white-50 mb-0">El documento ha sido firmado electrónicamente (FEA).</p>
                </div>
                <div class="modal-body p-4">
                    
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="d-flex justify-content-between text-xs mb-1">
                            <span class="text-muted">Nombre del Archivo:</span>
                            <span class="fw-bold text-dark text-truncate" style="max-width: 260px;" id="resNombreArchivo">-</span>
                        </div>
                        <div class="d-flex justify-content-between text-xs mb-1">
                            <span class="text-muted">Tamaño Final:</span>
                            <span class="fw-semibold text-dark" id="resTamanoFinal">-</span>
                        </div>
                        <div class="d-flex justify-content-between text-xs mb-1">
                            <span class="text-muted">ID Solicitud FirmaGob:</span>
                            <span class="font-monospace text-dark" id="resIdSolicitud">-</span>
                        </div>
                        <div class="d-flex justify-content-between text-xs">
                            <span class="text-muted">Checksum SHA-256:</span>
                            <span class="font-monospace text-muted text-truncate" style="max-width: 240px;" id="resChecksumFirmado">-</span>
                        </div>
                    </div>

                    <div class="alert alert-info py-2 px-3 rounded-3 small d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-shield-lock-fill fs-5 shrink-0"></i>
                        <div>
                            <strong>Ciclo Efímero:</strong> Por seguridad, este documento no queda almacenado en el servidor. Descárguelo ahora.
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="#" id="btnDescargarPdf" class="btn btn-success py-2.5 rounded-3 fw-bold shadow-xs d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-download fs-5"></i> Descargar Documento Firmado
                        </a>
                        <a href="#" id="btnVerPdf" target="_blank" class="btn btn-outline-secondary py-2 rounded-3 fw-semibold d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-box-arrow-up-right"></i> Ver en Nueva Ventana
                        </a>
                        <button type="button" class="btn btn-link text-secondary text-decoration-none small mt-1" onclick="firmarOtroDocumento()">
                            <i class="bi bi-plus-circle me-1"></i> Firmar otro documento al paso
                        </button>
                    </div>

                </div>
            </div>
        </div>
    <!-- BARRA FLOTANTE DE ACCIÓN RÁPIDA PARA CELULARES -->
    <div id="mobileBottomBar" class="d-lg-none mobile-floating-sign-bar d-none">
        <div class="overflow-hidden me-2">
            <div class="fw-bold text-dark text-truncate text-xs" id="mobileDocNombre">Documento listo</div>
            <div class="text-primary text-xs d-flex align-items-center gap-1" style="font-size: 11px;">
                <i class="bi bi-geo-alt-fill"></i>
                <span id="mobilePillPos">Pág. 1 (Estampa lista)</span>
            </div>
        </div>
        <button type="button" class="btn btn-primary btn-sm px-3 py-2 rounded-pill fw-bold shadow-sm d-flex align-items-center gap-1.5 shrink-0" onclick="irAFormularioFirma()">
            <i class="bi bi-shield-check fs-6"></i>
            <span>Firmar Documento</span>
        </button>
    </div>

    <?php include __DIR__ . '/footer.php'; ?>

    <!-- Lógica JavaScript del Visor PDF y Posicionamiento Libre -->
    <script>
        // Configuración de PDF.js
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        // Estado Global del Visor
        let pdfDoc = null;
        let paginaActual = 1;
        let totalPaginas = 1;
        let escalaActual = 1.0;
        let escalaOriginal = 1.0;
        let docToken = null;
        let pdfDimensiones = { widthPt: 612, heightPt: 792 }; // Puntos PostScript nativos del PDF
        let stampDimensionesPt = { widthPt: 170, heightPt: 80 }; // Dimensiones físicas de la estampa en puntos PDF
        let currentRenderTask = null; // Control de renderizado concurrente

        // Coordenadas locales de la estampa en píxeles sobre el canvas
        let stampPosPx = { left: 40, top: 40 };
        let isDragging = false;
        let dragOffset = { x: 0, y: 0 };

        const canvas = document.getElementById('pdfCanvas');
        const ctx = canvas.getContext('2d');
        const wrapper = document.getElementById('pdfCanvasWrapper');
        const stampBox = document.getElementById('stampBox');
        const selectPagina = document.getElementById('selectPaginaDirecta');

        // Inicialización de Eventos Dropzone
        const dropzone = document.getElementById('dropzoneFirmador');
        const inputFile = document.getElementById('inputArchivoPdf');

        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('dragover');
        });
        dropzone.addEventListener('dragleave', () => {
            dropzone.classList.remove('dragover');
        });
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                cargarArchivoSeleccionado(e.dataTransfer.files[0]);
            }
        });

        inputFile.addEventListener('change', (e) => {
            if (e.target.files && e.target.files.length > 0) {
                cargarArchivoSeleccionado(e.target.files[0]);
            }
        });

        // 1. Subir archivo temporal y abrir en el visor
        async function cargarArchivoSeleccionado(file) {
            if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
                alert('Por favor seleccione un archivo PDF válido.');
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                alert('El archivo excede el tamaño máximo permitido por FirmaGob (5 MB).');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'subir_documento');
            formData.append('archivo_pdf', file);
            formData.append('csrf_token', '<?= $csrf_token ?>');

            try {
                // Mostrar loader básico
                dropzone.innerHTML = `
                    <div class="spinner-border text-primary my-4" role="status" style="width: 3rem; height: 3rem;"></div>
                    <div class="fw-bold text-dark">Analizando documento PDF...</div>
                    <div class="text-muted small">Cargando en memoria para vista previa.</div>
                `;

                const res = await fetch('firmador_institucional_controller.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (!data.success) {
                    throw new Error(data.error || 'Error al cargar el documento.');
                }

                docToken = data.token_doc;
                document.getElementById('tokenDocHidden').value = docToken;
                document.getElementById('docInfoNombre').innerText = data.nombre;
                document.getElementById('docInfoTamano').innerText = data.tamano_formato;
                document.getElementById('docInfoHash').innerText = 'SHA256: ' + data.checksum;
                document.getElementById('inputDescripcion').value = 'Firma Documento: ' + data.nombre;

                // CRÍTICO: Primero hacer visible la sección del taller para que el contenedor calcule correctamente su clientWidth
                document.getElementById('seccionCarga').classList.add('d-none');
                document.getElementById('seccionTaller').classList.remove('d-none');

                // Activar barra flotante en celulares
                const mobileBar = document.getElementById('mobileBottomBar');
                if (mobileBar && window.innerWidth < 992) {
                    mobileBar.classList.remove('d-none');
                    document.body.classList.add('has-mobile-bar');
                    const lblDocMob = document.getElementById('mobileDocNombre');
                    if (lblDocMob) lblDocMob.innerText = data.nombre;
                }

                // Breve pausa para asegurar que el navegador aplique los estilos de visualización
                await new Promise(r => setTimeout(r, 80));

                // Ahora cargar documento en PDF.js
                await cargarPdfEnVisor(`firmador_institucional_controller.php?action=obtener_preview_pdf&token_doc=${docToken}`);

            } catch (err) {
                alert('Error: ' + err.message);
                location.reload();
            }
        }

        // 2. Renderizado de PDF con PDF.js
        async function cargarPdfEnVisor(urlPdf) {
            try {
                const loadingTask = pdfjsLib.getDocument(urlPdf);
                pdfDoc = await loadingTask.promise;
                totalPaginas = pdfDoc.numPages;

                // Poblar selector directo de páginas
                if (selectPagina) {
                    selectPagina.innerHTML = '';
                    for (let p = 1; p <= totalPaginas; p++) {
                        const opt = document.createElement('option');
                        opt.value = p;
                        opt.innerText = `Pág. ${p} de ${totalPaginas}`;
                        selectPagina.appendChild(opt);
                    }
                }

                // Por defecto situar en la última página (habitual en decretos/oficios/informes)
                paginaActual = totalPaginas;
                if (selectPagina) selectPagina.value = paginaActual;
                document.getElementById('pageHidden').value = paginaActual;

                await renderizarPagina(paginaActual);

                // Ubicar la estampa por defecto abajo a la derecha
                posicionarEstampa('bottom-right');

            } catch (error) {
                console.error("Error al cargar PDF con PDF.js:", error);
                alert("No se pudo previsualizar el PDF: " + error.message);
            }
        }

        async function renderizarPagina(numPag) {
            if (!pdfDoc) return;

            // Si hay un renderizado en curso, cancelarlo limpiamente antes de iniciar el siguiente
            if (currentRenderTask) {
                try {
                    currentRenderTask.cancel();
                } catch(e) {}
                currentRenderTask = null;
            }

            try {
                const page = await pdfDoc.getPage(numPag);
                
                // Obtener dimensiones reales del PDF en puntos tipográficos
                const unscaledViewport = page.getViewport({ scale: 1.0 });
                pdfDimensiones.widthPt = unscaledViewport.width;
                pdfDimensiones.heightPt = unscaledViewport.height;

                // Escalar para ajustarse al contenedor con precisión (responsivo móvil / desktop)
                const viewerCard = document.getElementById('pdfViewerCard');
                const isMobile = window.innerWidth < 768;
                const margin = isMobile ? 12 : 48;
                const minWidth = isMobile ? 260 : 500;
                const clientW = viewerCard ? viewerCard.clientWidth : 0;
                const availableWidth = clientW > 80 ? (clientW - margin) : (isMobile ? 320 : 800);
                const containerWidth = Math.max(minWidth, availableWidth);
                escalaOriginal = containerWidth / unscaledViewport.width;
                
                const finalScale = escalaOriginal * escalaActual;
                const viewport = page.getViewport({ scale: finalScale });

                canvas.width = Math.floor(viewport.width);
                canvas.height = Math.floor(viewport.height);
                canvas.style.width = Math.floor(viewport.width) + 'px';
                canvas.style.height = Math.floor(viewport.height) + 'px';
                wrapper.style.width = Math.floor(viewport.width) + 'px';
                wrapper.style.height = Math.floor(viewport.height) + 'px';

                const renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };

                currentRenderTask = page.render(renderContext);
                await currentRenderTask.promise;
                currentRenderTask = null;

                // Ajustar tamaño visual y posición de la caja de estampa proporcional a la página
                actualizarTamanoEstampaPx();
                actualizarCoordenadasFirmaGob();

            } catch (err) {
                if (err && err.name !== 'RenderingCancelledException') {
                    console.error("Error en renderizado de página PDF:", err);
                }
            }
        }

        // Calcula el tamaño en píxeles de la caja de estampa manteniendo la escala física exacta
        function actualizarTamanoEstampaPx() {
            const scaleX = canvas.width / (pdfDimensiones.widthPt || 612);
            const scaleY = canvas.height / (pdfDimensiones.heightPt || 792);

            let boxWidthPx = Math.round(stampDimensionesPt.widthPt * scaleX);
            let boxHeightPx = Math.round(stampDimensionesPt.heightPt * scaleY);

            const isMobile = window.innerWidth < 768;
            const minW = isMobile ? 140 : 190;
            const minH = isMobile ? 66 : 85;

            // Límites adaptativos para garantizar máxima legibilidad en pantalla sin desbordar
            boxWidthPx = Math.max(minW, Math.min(320, boxWidthPx));
            boxHeightPx = Math.max(minH, Math.min(130, boxHeightPx));

            stampBox.style.width = boxWidthPx + 'px';
            stampBox.style.height = boxHeightPx + 'px';
        }

        // 3. Conversión de coordenadas de Pantalla (Top-Left) a FirmaGob PDF (Bottom-Left)
        function actualizarCoordenadasFirmaGob() {
            const scaleX = pdfDimensiones.widthPt / canvas.width;
            const scaleY = pdfDimensiones.heightPt / canvas.height;

            const boxWidthPx = stampBox.offsetWidth;
            const boxHeightPx = stampBox.offsetHeight;

            let llx = Math.round(stampPosPx.left * scaleX);
            let urx = Math.round((stampPosPx.left + boxWidthPx) * scaleX);

            // Inversión cartesiana eje Y: PDF (0,0) es inferior izquierdo
            let ury = Math.round((canvas.height - stampPosPx.top) * scaleY);
            let lly = Math.round((canvas.height - (stampPosPx.top + boxHeightPx)) * scaleY);

            // Asegurar límites dentro de la página
            llx = Math.max(0, Math.min(llx, Math.round(pdfDimensiones.widthPt - stampDimensionesPt.widthPt)));
            lly = Math.max(0, Math.min(lly, Math.round(pdfDimensiones.heightPt - stampDimensionesPt.heightPt)));
            urx = llx + stampDimensionesPt.widthPt;
            ury = lly + stampDimensionesPt.heightPt;

            document.getElementById('llxHidden').value = llx;
            document.getElementById('llyHidden').value = lly;
            document.getElementById('urxHidden').value = urx;
            document.getElementById('uryHidden').value = ury;
            document.getElementById('pageHidden').value = paginaActual;

            document.getElementById('stampCoordsPill').innerText = `Pág ${paginaActual} | X: ${llx}, Y: ${lly}`;
            document.getElementById('infoCoordenadasCalculadas').innerText = `Coordenadas PDF: llx: ${llx}, lly: ${lly}, urx: ${urx}, ury: ${ury} (Pág. ${paginaActual})`;

            // Actualizar indicador en la barra móvil
            const mobilePos = document.getElementById('mobilePillPos');
            if (mobilePos) {
                mobilePos.innerText = `Pág. ${paginaActual} (X: ${llx}, Y: ${lly})`;
            }
        }

        // Función para scroll rápido al formulario desde la barra móvil
        function irAFormularioFirma() {
            const target = document.getElementById('formFirmarDocumento');
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                const btn = document.getElementById('btnEjecutarFirma');
                if (btn) {
                    btn.classList.add('pulse-anim');
                    setTimeout(() => btn.classList.remove('pulse-anim'), 1400);
                }
            }
        }

        // Posicionamiento Rápido de la Estampa
        function posicionarEstampa(pos) {
            const boxW = stampBox.offsetWidth || 190;
            const boxH = stampBox.offsetHeight || 85;
            const isMobile = window.innerWidth < 768;
            const margin = isMobile ? 12 : 25;

            switch (pos) {
                case 'bottom-right':
                    stampPosPx.left = Math.max(margin, canvas.width - boxW - margin);
                    stampPosPx.top = Math.max(margin, canvas.height - boxH - margin);
                    break;
                case 'bottom-center':
                    stampPosPx.left = Math.max(margin, Math.round((canvas.width - boxW) / 2));
                    stampPosPx.top = Math.max(margin, canvas.height - boxH - margin);
                    break;
                case 'bottom-left':
                    stampPosPx.left = margin;
                    stampPosPx.top = Math.max(margin, canvas.height - boxH - margin);
                    break;
            }

            stampBox.style.left = stampPosPx.left + 'px';
            stampBox.style.top = stampPosPx.top + 'px';
            actualizarCoordenadasFirmaGob();
        }

        // 4. Arrastre Libre (Drag & Drop) de la Estampa con Puntero / Touch
        stampBox.addEventListener('pointerdown', (e) => {
            isDragging = true;
            stampBox.setPointerCapture(e.pointerId);
            const rect = stampBox.getBoundingClientRect();
            dragOffset.x = e.clientX - rect.left;
            dragOffset.y = e.clientY - rect.top;
        });

        window.addEventListener('pointermove', (e) => {
            if (!isDragging) return;

            const wrapperRect = wrapper.getBoundingClientRect();
            let newLeft = e.clientX - wrapperRect.left - dragOffset.x;
            let newTop = e.clientY - wrapperRect.top - dragOffset.y;

            // Contención dentro de los límites del Canvas
            const maxLeft = canvas.width - stampBox.offsetWidth;
            const maxTop = canvas.height - stampBox.offsetHeight;

            newLeft = Math.max(0, Math.min(newLeft, maxLeft));
            newTop = Math.max(0, Math.min(newTop, maxTop));

            stampPosPx.left = newLeft;
            stampPosPx.top = newTop;

            stampBox.style.left = newLeft + 'px';
            stampBox.style.top = newTop + 'px';

            actualizarCoordenadasFirmaGob();
        });

        window.addEventListener('pointerup', () => {
            isDragging = false;
        });

        // 5. Controles de Paginación Integrados
        if (selectPagina) {
            selectPagina.addEventListener('change', (e) => {
                paginaActual = parseInt(e.target.value, 10);
                document.getElementById('pageHidden').value = paginaActual;
                renderizarPagina(paginaActual);
            });
        }

        document.getElementById('btnPaginaPrimera').addEventListener('click', () => {
            if (paginaActual !== 1) {
                paginaActual = 1;
                if (selectPagina) selectPagina.value = paginaActual;
                document.getElementById('pageHidden').value = paginaActual;
                renderizarPagina(paginaActual);
            }
        });

        document.getElementById('btnPaginaUltima').addEventListener('click', () => {
            if (paginaActual !== totalPaginas) {
                paginaActual = totalPaginas;
                if (selectPagina) selectPagina.value = paginaActual;
                document.getElementById('pageHidden').value = paginaActual;
                renderizarPagina(paginaActual);
            }
        });

        document.getElementById('btnPaginaAnterior').addEventListener('click', () => {
            if (paginaActual > 1) {
                paginaActual--;
                if (selectPagina) selectPagina.value = paginaActual;
                document.getElementById('pageHidden').value = paginaActual;
                renderizarPagina(paginaActual);
            }
        });

        document.getElementById('btnPaginaSiguiente').addEventListener('click', () => {
            if (paginaActual < totalPaginas) {
                paginaActual++;
                if (selectPagina) selectPagina.value = paginaActual;
                document.getElementById('pageHidden').value = paginaActual;
                renderizarPagina(paginaActual);
            }
        });

        // Controles de Zoom
        document.getElementById('btnZoomIn').addEventListener('click', () => {
            if (escalaActual < 2.0) {
                escalaActual += 0.15;
                document.getElementById('labelZoom').innerText = Math.round(escalaActual * 100) + '%';
                renderizarPagina(paginaActual);
            }
        });

        document.getElementById('btnZoomOut').addEventListener('click', () => {
            if (escalaActual > 0.5) {
                escalaActual -= 0.15;
                document.getElementById('labelZoom').innerText = Math.round(escalaActual * 100) + '%';
                renderizarPagina(paginaActual);
            }
        });

        document.getElementById('btnAjustarAncho').addEventListener('click', () => {
            escalaActual = 1.0;
            document.getElementById('labelZoom').innerText = '100%';
            renderizarPagina(paginaActual);
        });

        // Reajuste responsivo ante cambio de tamaño de ventana (con debounce)
        let resizeTimeout = null;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                if (pdfDoc && !document.getElementById('seccionTaller').classList.contains('d-none')) {
                    renderizarPagina(paginaActual);
                }
            }, 250);
        });

        // 6. Alternar Firma Visible vs Invisible
        function alternarTipoFirma() {
            const esVisible = document.getElementById('tipoFirmaVisible').checked;
            if (esVisible) {
                stampBox.classList.remove('hidden-stamp');
                document.getElementById('grupoPosicionesRapidas').classList.remove('d-none');
            } else {
                stampBox.classList.add('hidden-stamp');
                document.getElementById('grupoPosicionesRapidas').classList.add('d-none');
                document.getElementById('infoCoordenadasCalculadas').innerText = 'Modo Firma Invisible: Sin estampa visual.';
            }
        }

        // Alternar Identidad Firmante (Subrogancia)
        function cambiarIdentidadFirmante() {
            const select = document.getElementById('selectFirmarComo');
            if (!select) return;

            if (select.value === 'SUBROGANTE') {
                const nombreS = '<?= addslashes($user_name) ?> (S)';
                const metaS = 'Suplente de: <?= addslashes($subrogado_nombre) ?> | <?= addslashes($subrogado_cargo) ?>';
                document.getElementById('stampPreviewNombre').innerText = nombreS;
                document.getElementById('stampPreviewMeta').innerText = metaS;
                document.getElementById('labelFirmanteNombre').innerText = nombreS;
                document.getElementById('labelFirmanteMeta').innerText = metaS;
            } else {
                const nombreT = '<?= addslashes($user_name) ?>';
                const metaT = 'RUN: <?= addslashes($user_rut) ?> | <?= addslashes($user_cargo) ?>';
                document.getElementById('stampPreviewNombre').innerText = nombreT;
                document.getElementById('stampPreviewMeta').innerText = metaT;
                document.getElementById('labelFirmanteNombre').innerText = nombreT;
                document.getElementById('labelFirmanteMeta').innerText = metaT;
            }
        }

        // 7. Enviar Petición de Firma a FirmaGob
        async function ejecutarFirma(e) {
            e.preventDefault();

            const form = document.getElementById('formFirmarDocumento');
            const formData = new FormData(form);
            formData.append('action', 'firmar_documento');

            const modalProc = new bootstrap.Modal(document.getElementById('modalProcesando'));
            modalProc.show();

            try {
                const res = await fetch('firmador_institucional_controller.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                modalProc.hide();

                if (!data.success) {
                    throw new Error(data.error || 'Ocurrió un error al firmar el documento.');
                }

                // Cargar datos en modal de éxito
                document.getElementById('resNombreArchivo').innerText = data.nombre_archivo;
                document.getElementById('resTamanoFinal').innerText = data.tamano_firmado;
                document.getElementById('resIdSolicitud').innerText = data.id_solicitud || 'N/A';
                document.getElementById('resChecksumFirmado').innerText = data.checksum_firmado;

                // Configurar botones de descarga y previsualización
                const urlDescarga = `firmador_institucional_controller.php?action=descargar_firmado&token=${data.token_descarga}`;
                const urlPreview = `firmador_institucional_controller.php?action=preview_firmado&token=${data.token_descarga}`;

                document.getElementById('btnDescargarPdf').href = urlDescarga;
                document.getElementById('btnVerPdf').href = urlPreview;

                const modalExito = new bootstrap.Modal(document.getElementById('modalExitoFirma'));
                modalExito.show();

                // Ocultar barra flotante móvil para no obstaculizar botones de descarga
                const mobBar = document.getElementById('mobileBottomBar');
                if (mobBar) mobBar.classList.add('d-none');
                document.body.classList.remove('has-mobile-bar');

            } catch (err) {
                modalProc.hide();
                alert('Error al firmar: ' + err.message);
            }
        }

        // Cancelar y Reiniciar
        async function cancelarYReiniciar() {
            if (confirm('¿Desea descartar este documento y purgar los archivos temporales?')) {
                if (docToken) {
                    const fd = new FormData();
                    fd.append('action', 'cancelar_temporal');
                    fd.append('token_doc', docToken);
                    fd.append('csrf_token', '<?= $csrf_token ?>');
                    await fetch('firmador_institucional_controller.php', { method: 'POST', body: fd });
                }
                location.reload();
            }
        }

        function firmarOtroDocumento() {
            location.reload();
        }
    </script>
</body>
</html>
