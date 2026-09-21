<?php
// diseno_opi.php - Taller Visual de Diseno y Calibracion de Plantilla OPI (Modelo Oficial Lebu N° 758)
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['user_rol'] ?? '';
if ($rol !== 'SYSADMIN' && $rol !== 'ADMIN_MUNICIPAL') {
    die("Acceso Denegado. Modulo exclusivo para Administracion del Sistema.");
}

$mensaje = '';
$tipo_mensaje = '';

// Valores oficiales por defecto
$defaults = [
    'opi_titulo_documento' => 'ORDEN DE PEDIDO INTERNO',
    'opi_clausula1_texto'  => '1. Agradeceré a Usted, tenga a bien efectuar la adquisición de los siguientes bienes y/o servicios:',
    'opi_clausula2_texto'  => '2. Los presentes bienes/servicios serán destinados a:',
    'opi_pie_legal'        => 'Documento Oficial emitido por el Sistema Institucional OPI - Validez legal bajo Ley N° 19.799 de Firma Electrónica',
    'opi_firmas_linea_y'   => '256.0'
];

// Procesamiento de formulario POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'guardar';

    if ($accion === 'restablecer') {
        try {
            $stmtDel = $pdo->prepare("DELETE FROM configuraciones_sistema WHERE clave IN ('opi_titulo_documento', 'opi_clausula1_texto', 'opi_clausula2_texto', 'opi_pie_legal', 'opi_firmas_linea_y')");
            $stmtDel->execute();
            $mensaje = "Se han restablecido los textos y parametros oficiales por defecto de la plantilla OPI.";
            $tipo_mensaje = "success";
        } catch (Exception $e) {
            $mensaje = "Error al restablecer valores: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    } else {
        try {
            $titulo_doc     = trim($_POST['opi_titulo_documento'] ?? $defaults['opi_titulo_documento']);
            $clausula1_txt  = trim($_POST['opi_clausula1_texto'] ?? $defaults['opi_clausula1_texto']);
            $clausula2_txt  = trim($_POST['opi_clausula2_texto'] ?? $defaults['opi_clausula2_texto']);
            $pie_legal_txt  = trim($_POST['opi_pie_legal'] ?? $defaults['opi_pie_legal']);
            $firmas_linea_y = floatval($_POST['opi_firmas_linea_y'] ?? 256.0);

            // Validar límites razonables para posición Y
            if ($firmas_linea_y < 220 || $firmas_linea_y > 270) {
                $firmas_linea_y = 256.0;
            }

            $guardar = [
                'opi_titulo_documento' => $titulo_doc,
                'opi_clausula1_texto'  => $clausula1_txt,
                'opi_clausula2_texto'  => $clausula2_txt,
                'opi_pie_legal'        => $pie_legal_txt,
                'opi_firmas_linea_y'   => (string)$firmas_linea_y
            ];

            $stmtSave = $pdo->prepare("INSERT INTO configuraciones_sistema (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
            foreach ($guardar as $k => $v) {
                $stmtSave->execute([$k, $v]);
            }

            $mensaje = "Diseno y parametros de la plantilla OPI guardados exitosamente. Todas las nuevas OPIs generadas reflejaran estos cambios.";
            $tipo_mensaje = "success";

        } catch (Exception $e) {
            $mensaje = "Error al guardar el diseno: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }
}

// Cargar valores actuales desde la base de datos
$configs = $defaults;
try {
    $stmt = $pdo->query("SELECT clave, valor FROM configuraciones_sistema WHERE clave LIKE 'opi_%'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['valor'] !== null && $row['valor'] !== '') {
            $configs[$row['clave']] = $row['valor'];
        }
    }
} catch (Exception $e) {
    // Usar defaults
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <?php 
    $titulo_pagina = "Disenador Visual de Plantilla OPI";
    include __DIR__ . '/head.php'; 
    ?>
    <style>
        .preview-pane {
            position: sticky;
            top: 20px;
            height: calc(100vh - 100px);
            min-height: 650px;
        }
        .preview-iframe {
            width: 100%;
            height: 100%;
            border: 0;
            border-radius: 8px;
            background-color: #525659;
        }
        .range-value-badge {
            font-size: 0.9rem;
            font-weight: 700;
            font-family: monospace;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans d-flex flex-column min-vh-100">

    <?php include __DIR__ . '/nav.php'; ?>

    <div class="container-fluid px-3 px-lg-4 mt-3 mb-5">
        
        <!-- CABECERA -->
        <div class="row align-items-center mb-3 g-3">
            <div class="col-12 col-md">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-primary-subtle text-primary rounded-3">
                        <i class="bi bi-file-earmark-pdf-fill fs-4"></i>
                    </div>
                    <div>
                        <h1 class="h4 fw-bold text-dark mb-0">Diseñador y Calibrador de Plantilla OPI</h1>
                        <p class="text-muted small mb-0">Personalice textos oficiales, títulos y calibre la posición de las líneas base de firma digital en tiempo real.</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <a href="configuracion_sistema.php" class="btn btn-outline-secondary btn-sm shadow-sm">
                    <i class="bi bi-sliders me-1"></i> Parametros Globales
                </a>
                <a href="index.php" class="btn btn-outline-secondary btn-sm shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i> Volver al Inicio
                </a>
            </div>
        </div>

        <!-- ALERTA MENSAJE -->
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?= $tipo_mensaje === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show d-flex align-items-center gap-2 mb-3 shadow-sm" role="alert">
                <i class="bi bi-<?= $tipo_mensaje === 'error' ? 'exclamation-triangle-fill' : 'check-circle-fill' ?> fs-5"></i>
                <div class="fw-semibold"><?= htmlspecialchars($mensaje) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- SPLIT WORKSHOP CONTAINER -->
        <div class="row g-3">

            <!-- COLUMNA IZQUIERDA: CONTROLES DE EDICIÓN -->
            <div class="col-12 col-lg-5">
                <form id="formDisenoOpi" method="POST" action="diseno_opi.php">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="accion" id="formAccion" value="guardar">

                    <!-- TARJETA 1: TEXTOS Y CLÁUSULAS OFICIALES -->
                    <div class="card shadow-sm border-light mb-3">
                        <div class="card-header bg-white py-2.5 border-bottom d-flex align-items-center gap-2">
                            <i class="bi bi-pencil-square text-primary fs-5"></i>
                            <h6 class="fw-bold mb-0 text-dark">1. Textos y Cláusulas del Documento</h6>
                        </div>
                        <div class="card-body p-3">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold text-secondary small mb-1">Título de Cabecera Oficial</label>
                                <input type="text" name="opi_titulo_documento" id="inputTitulo" class="form-control form-control-sm fw-bold text-dark" value="<?= htmlspecialchars($configs['opi_titulo_documento']) ?>" required>
                                <div class="form-text small text-muted">Texto central en la cabecera (por defecto: ORDEN DE PEDIDO INTERNO).</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold text-secondary small mb-1">Cláusula 1: Texto Previo a Ítems/Servicios</label>
                                <textarea name="opi_clausula1_texto" id="inputClausula1" rows="2" class="form-control form-control-sm" required><?= htmlspecialchars($configs['opi_clausula1_texto']) ?></textarea>
                                <div class="form-text small text-muted">Instrucción formal previa a la tabla de productos y montos.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold text-secondary small mb-1">Cláusula 2: Destino de Bienes / Servicios</label>
                                <textarea name="opi_clausula2_texto" id="inputClausula2" rows="2" class="form-control form-control-sm" required><?= htmlspecialchars($configs['opi_clausula2_texto']) ?></textarea>
                                <div class="form-text small text-muted">Encabezado de la justificación del gasto ingresada por la unidad solicitante.</div>
                            </div>

                            <div class="mb-0">
                                <label class="form-label fw-bold text-secondary small mb-1">Pie de Página Legal</label>
                                <textarea name="opi_pie_legal" id="inputPieLegal" rows="2" class="form-control form-control-sm" required><?= htmlspecialchars($configs['opi_pie_legal']) ?></textarea>
                                <div class="form-text small text-muted">Mención de validez legal bajo Ley N° 19.799 en el borde inferior.</div>
                            </div>

                        </div>
                    </div>

                    <!-- TARJETA 2: CALIBRACIÓN DE FIRMAS DIGITALES -->
                    <div class="card shadow-sm border-light mb-3">
                        <div class="card-header bg-white py-2.5 border-bottom d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-vector-pen text-success fs-5"></i>
                                <h6 class="fw-bold mb-0 text-dark">2. Calibración de Líneas de Firmas</h6>
                            </div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                FirmaGob Compatible
                            </span>
                        </div>
                        <div class="card-body p-3">
                            
                            <div class="p-2.5 bg-light rounded-3 border mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-bold text-dark small mb-0">Posición Vertical Y de las Líneas Base (mm)</label>
                                    <span class="badge bg-primary range-value-badge" id="badgePosY"><?= htmlspecialchars($configs['opi_firmas_linea_y']) ?> mm</span>
                                </div>
                                <input type="range" class="form-range" id="rangePosY" name="opi_firmas_linea_y" min="235" max="265" step="0.5" value="<?= htmlspecialchars($configs['opi_firmas_linea_y']) ?>">
                                <div class="d-flex justify-content-between text-muted" style="font-size: 10px;">
                                    <span>235 mm (Más arriba)</span>
                                    <span>256 mm (Óptimo Oficial)</span>
                                    <span>265 mm (Más abajo)</span>
                                </div>
                            </div>

                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="swSimularEstampas" checked>
                                <label class="form-check-label fw-bold text-dark small" for="swSimularEstampas">
                                    Simular Estampas Digitales FirmaGob en la Vista Previa
                                </label>
                                <div class="text-muted small" style="font-size: 11px;">Muestra las insignias de firma electrónica avanzada directamente sobre las 3 líneas base para validar su alineación.</div>
                            </div>

                        </div>
                    </div>

                    <!-- BOTONES DE ACCIÓN -->
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="restablecerValores()">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Restablecer por Defecto
                        </button>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm shadow-sm" onclick="actualizarVistaPrevia()">
                                <i class="bi bi-arrow-repeat me-1"></i> Refrescar Vista Previa
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold shadow">
                                <i class="bi bi-floppy-fill me-1"></i> Guardar Cambios
                            </button>
                        </div>
                    </div>

                </form>
            </div>

            <!-- COLUMNA DERECHA: VISTA PREVIA EN VIVO (PDF) -->
            <div class="col-12 col-lg-7">
                <div class="card shadow-sm border-light preview-pane d-flex flex-column">
                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                <i class="bi bi-eye-fill me-1"></i> Vista Previa en Vivo
                            </span>
                            <span class="text-muted small d-none d-sm-inline">Renderizado nativo FPDF</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-outline-secondary py-0.5 px-2" title="Recargar PDF" onclick="actualizarVistaPrevia()">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                            <a id="btnAbrirNuevaPestana" href="preview_opi_diseno.php" target="_blank" class="btn btn-sm btn-outline-primary py-0.5 px-2" title="Abrir PDF en pestaña independiente">
                                <i class="bi bi-box-arrow-up-right me-1"></i> Abrir PDF
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0 flex-grow-1 bg-dark">
                        <iframe id="previewIframe" src="preview_opi_diseno.php" class="preview-iframe" title="Vista Previa de la OPI"></iframe>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <?php include __DIR__ . '/footer.php'; ?>

    <!-- JAVASCRIPT DE VINCULACIÓN DINÁMICA -->
    <script>
    let debounceTimer = null;

    function obtenerParametrosPreview() {
        const titulo = encodeURIComponent(document.getElementById('inputTitulo').value);
        const clausula1 = encodeURIComponent(document.getElementById('inputClausula1').value);
        const clausula2 = encodeURIComponent(document.getElementById('inputClausula2').value);
        const pieLegal = encodeURIComponent(document.getElementById('inputPieLegal').value);
        const posY = encodeURIComponent(document.getElementById('rangePosY').value);
        const simular = document.getElementById('swSimularEstampas').checked ? '1' : '0';

        return `opi_titulo_documento=${titulo}&opi_clausula1_texto=${clausula1}&opi_clausula2_texto=${clausula2}&opi_pie_legal=${pieLegal}&opi_firmas_linea_y=${posY}&simular_estampas=${simular}&t=${Date.now()}`;
    }

    function actualizarVistaPrevia() {
        const query = obtenerParametrosPreview();
        const url = `preview_opi_diseno.php?${query}`;
        document.getElementById('previewIframe').src = url;
        document.getElementById('btnAbrirNuevaPestana').href = url;
    }

    function dispararActualizacionDebounce() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            actualizarVistaPrevia();
        }, 350);
    }

    // Sincronizar Slider de Posición Y
    const rangePosY = document.getElementById('rangePosY');
    const badgePosY = document.getElementById('badgePosY');
    if (rangePosY && badgePosY) {
        rangePosY.addEventListener('input', function() {
            badgePosY.textContent = parseFloat(this.value).toFixed(1) + ' mm';
            dispararActualizacionDebounce();
        });
    }

    // Escuchar cambios en todos los campos de texto
    ['inputTitulo', 'inputClausula1', 'inputClausula2', 'inputPieLegal'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', dispararActualizacionDebounce);
        }
    });

    // Escuchar toggle de estampas simuladas
    const swEstampas = document.getElementById('swSimularEstampas');
    if (swEstampas) {
        swEstampas.addEventListener('change', actualizarVistaPrevia);
    }

    function restablecerValores() {
        if (confirm('¿Está seguro de que desea restablecer todos los textos y posiciones a los valores oficiales predeterminados?')) {
            document.getElementById('formAccion').value = 'restablecer';
            document.getElementById('formDisenoOpi').submit();
        }
    }

    // Carga inicial
    document.addEventListener('DOMContentLoaded', () => {
        actualizarVistaPrevia();
    });
    </script>
</body>
</html>
