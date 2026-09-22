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

// Asegurar existencia de la tabla configuraciones_sistema
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `configuraciones_sistema` (
        `clave` varchar(50) NOT NULL,
        `valor` text DEFAULT NULL,
        PRIMARY KEY (`clave`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

$mensaje = '';
$tipo_mensaje = '';

// Valores oficiales por defecto
$defaults = [
    'opi_tamano_papel'        => 'OFICIO',
    'opi_titulo_documento'    => 'ORDEN DE PEDIDO INTERNO',
    'opi_clausula1_texto'     => '1. Agradeceré a Usted, tenga a bien efectuar la adquisición de los siguientes bienes y/o servicios:',
    'opi_clausula2_texto'     => '2. Los presentes bienes/servicios serán destinados a:',
    'opi_pie_legal'           => 'Documento Oficial emitido por el Sistema Institucional OPI - Validez legal bajo Ley N° 19.799 de Firma Electrónica',
    'opi_firmas_linea_y'      => '306.0',
    'estampa_tema'            => 'azul_institucional',
    'estampa_fuente'          => 'segoeui',
    'estampa_titulo_texto'    => 'FIRMADO ELECTRÓNICAMENTE (FEA)',
    'estampa_icono'           => 'check',
    'estampa_alto_mm'         => '24.0',
    'estampa_tamano_texto'    => 'normal',
    'estampa_mostrar_run'     => '1',
    'estampa_mostrar_cargo'   => '1',
    'estampa_mostrar_fecha'   => '1',
    'estampa_mostrar_entidad' => '1',
    'estampa_mostrar_ley'     => '1',
    'opi_firma1_titulo'       => 'JEFATURA UNIDAD SOLICITANTE',
    'opi_firma1_subtitulo'    => 'V°B° Requerimiento Técnico',
    'opi_firma2_titulo'       => 'DIRECCIÓN DE ADM. Y FINANZAS',
    'opi_firma2_subtitulo'    => 'Control e Imputación Presupuestaria',
    'opi_firma3_titulo'       => 'ADMINISTRADOR MUNICIPAL',
    'opi_firma3_subtitulo'    => 'Autorización Final del Gasto',
];

// Procesamiento de formulario POST
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $accion = $_POST['accion'] ?? 'guardar';

    if ($accion === 'restablecer') {
        try {
            $stmtDel = $pdo->prepare("DELETE FROM configuraciones_sistema WHERE clave IN (
                'opi_tamano_papel', 'opi_titulo_documento', 'opi_clausula1_texto', 'opi_clausula2_texto', 'opi_pie_legal', 'opi_firmas_linea_y',
                'estampa_tema', 'estampa_fuente', 'estampa_titulo_texto', 'estampa_icono', 'estampa_alto_mm', 'estampa_tamano_texto',
                'estampa_mostrar_run', 'estampa_mostrar_cargo', 'estampa_mostrar_fecha', 'estampa_mostrar_entidad', 'estampa_mostrar_ley',
                'opi_firma1_titulo', 'opi_firma1_subtitulo', 'opi_firma2_titulo', 'opi_firma2_subtitulo', 'opi_firma3_titulo', 'opi_firma3_subtitulo'
            )");
            $stmtDel->execute();
            $mensaje = "Se han restablecido los textos, formato y parámetros oficiales por defecto de la plantilla y estampa OPI.";
            $tipo_mensaje = "success";
        } catch (Exception $e) {
            $mensaje = "Error al restablecer valores: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    } else {
        try {
            $tamano_papel   = strtoupper(trim($_POST['opi_tamano_papel'] ?? 'OFICIO'));
            if ($tamano_papel !== 'LETTER') {
                $tamano_papel = 'OFICIO';
            }

            $titulo_doc     = trim($_POST['opi_titulo_documento'] ?? $defaults['opi_titulo_documento']);
            $clausula1_txt  = trim($_POST['opi_clausula1_texto'] ?? $defaults['opi_clausula1_texto']);
            $clausula2_txt  = trim($_POST['opi_clausula2_texto'] ?? $defaults['opi_clausula2_texto']);
            $pie_legal_txt  = trim($_POST['opi_pie_legal'] ?? $defaults['opi_pie_legal']);

            $y_default = ($tamano_papel === 'OFICIO') ? 306.0 : 256.0;
            $firmas_linea_y = floatval($_POST['opi_firmas_linea_y'] ?? $y_default);

            // Validar límites exactos para posición Y según formato de hoja
            if ($tamano_papel === 'OFICIO') {
                if ($firmas_linea_y < 275 || $firmas_linea_y > 318) {
                    $firmas_linea_y = 306.0;
                }
            } else {
                if ($firmas_linea_y < 235 || $firmas_linea_y > 265) {
                    $firmas_linea_y = 256.0;
                }
            }

            // Validar lista blanca de temas, tipografías e iconos
            $temas_validos   = ['azul_institucional', 'verde_validacion', 'monocromatico', 'barra_lateral'];
            $fuentes_validas = ['segoeui', 'calibri', 'arial'];
            $iconos_validos  = ['check', 'candado', 'escudo', 'ninguno'];
            $tamanos_validos = ['normal', 'grande', 'extragrande'];

            $tema_post   = $_POST['estampa_tema'] ?? 'azul_institucional';
            $fuente_post = $_POST['estampa_fuente'] ?? 'segoeui';
            $icono_post  = $_POST['estampa_icono'] ?? 'check';
            $tam_post    = $_POST['estampa_tamano_texto'] ?? 'normal';

            $estampa_tema   = in_array($tema_post, $temas_validos, true) ? $tema_post : 'azul_institucional';
            $estampa_fuente = in_array($fuente_post, $fuentes_validas, true) ? $fuente_post : 'segoeui';
            $estampa_icono  = in_array($icono_post, $iconos_validos, true) ? $icono_post : 'check';
            $estampa_tam_txt = in_array($tam_post, $tamanos_validos, true) ? $tam_post : 'normal';

            $estampa_alto_mm = floatval($_POST['estampa_alto_mm'] ?? 24.0);
            if ($estampa_alto_mm < 18.0 || $estampa_alto_mm > 28.0) {
                $estampa_alto_mm = 24.0;
            }

            $estampa_titulo_texto = trim($_POST['estampa_titulo_texto'] ?? '');
            if (empty($estampa_titulo_texto)) {
                $estampa_titulo_texto = 'FIRMADO ELECTRÓNICAMENTE (FEA)';
            }
            if (mb_strlen($estampa_titulo_texto, 'UTF-8') > 50) {
                $estampa_titulo_texto = mb_substr($estampa_titulo_texto, 0, 50, 'UTF-8');
            }

            // Sanitizar textos de los pies de firma (permite dejar subtítulos en blanco)
            $limpiar_pie = function($val, $max = 50) {
                $t = trim((string)($val ?? ''));
                return mb_substr($t, 0, $max, 'UTF-8');
            };

            $firma1_tit = $limpiar_pie($_POST['opi_firma1_titulo'] ?? '', 45);
            $firma1_sub = $limpiar_pie($_POST['opi_firma1_subtitulo'] ?? '', 50);
            $firma2_tit = $limpiar_pie($_POST['opi_firma2_titulo'] ?? '', 45);
            $firma2_sub = $limpiar_pie($_POST['opi_firma2_subtitulo'] ?? '', 50);
            $firma3_tit = $limpiar_pie($_POST['opi_firma3_titulo'] ?? '', 45);
            $firma3_sub = $limpiar_pie($_POST['opi_firma3_subtitulo'] ?? '', 50);

            $guardar = [
                'opi_tamano_papel'        => $tamano_papel,
                'opi_titulo_documento'    => $titulo_doc,
                'opi_clausula1_texto'     => $clausula1_txt,
                'opi_clausula2_texto'     => $clausula2_txt,
                'opi_pie_legal'           => $pie_legal_txt,
                'opi_firmas_linea_y'      => number_format($firmas_linea_y, 1, '.', ''),
                'estampa_tema'            => $estampa_tema,
                'estampa_fuente'          => $estampa_fuente,
                'estampa_titulo_texto'    => $estampa_titulo_texto,
                'estampa_icono'           => $estampa_icono,
                'estampa_alto_mm'         => number_format($estampa_alto_mm, 1, '.', ''),
                'estampa_tamano_texto'    => $estampa_tam_txt,
                'estampa_mostrar_run'     => isset($_POST['estampa_mostrar_run']) ? '1' : '0',
                'estampa_mostrar_cargo'   => isset($_POST['estampa_mostrar_cargo']) ? '1' : '0',
                'estampa_mostrar_fecha'   => isset($_POST['estampa_mostrar_fecha']) ? '1' : '0',
                'estampa_mostrar_entidad' => isset($_POST['estampa_mostrar_entidad']) ? '1' : '0',
                'estampa_mostrar_ley'     => isset($_POST['estampa_mostrar_ley']) ? '1' : '0',
                'opi_firma1_titulo'       => $firma1_tit,
                'opi_firma1_subtitulo'    => $firma1_sub,
                'opi_firma2_titulo'       => $firma2_tit,
                'opi_firma2_subtitulo'    => $firma2_sub,
                'opi_firma3_titulo'       => $firma3_tit,
                'opi_firma3_subtitulo'    => $firma3_sub,
            ];

            $stmtSave = $pdo->prepare("INSERT INTO configuraciones_sistema (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
            foreach ($guardar as $k => $v) {
                $stmtSave->execute([$k, $v]);
            }

            $mensaje = "Diseño de OPI, dimensiones de estampa y pies de firma guardados exitosamente.";
            $tipo_mensaje = "success";

        } catch (Exception $e) {
            $mensaje = "Error al guardar el diseño: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }
}

// Cargar valores actuales desde la base de datos
$configs = $defaults;
try {
    $stmt = $pdo->query("SELECT clave, valor FROM configuraciones_sistema WHERE clave LIKE 'opi_%' OR clave LIKE 'estampa_%'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['valor'] !== null && $row['valor'] !== '') {
            $configs[$row['clave']] = $row['valor'];
        }
    }
} catch (Exception $e) {
    // Usar defaults
}

// Preparar cálculo seguro de posición vertical según tamaño de hoja actual
$tamano_actual = $configs['opi_tamano_papel'] ?? 'OFICIO';
$es_oficio_actual = ($tamano_actual === 'OFICIO');
$min_slider_y = $es_oficio_actual ? 275 : 235;
$max_slider_y = $es_oficio_actual ? 318 : 265;
$val_slider_y = floatval($configs['opi_firmas_linea_y'] ?? ($es_oficio_actual ? 306.0 : 256.0));
if ($val_slider_y < $min_slider_y || $val_slider_y > $max_slider_y) {
    $val_slider_y = $es_oficio_actual ? 306.0 : 256.0;
}

$val_alto_estampa = floatval($configs['estampa_alto_mm'] ?? 24.0);
if ($val_alto_estampa < 18.0 || $val_alto_estampa > 28.0) {
    $val_alto_estampa = 24.0;
}

// Pre-calcular URL de vista previa con parámetros sincronizados para carga directa sin doble render
$query_preview_inicial = http_build_query([
    'opi_tamano_papel'        => $tamano_actual,
    'opi_titulo_documento'    => $configs['opi_titulo_documento'] ?? 'ORDEN DE PEDIDO INTERNO',
    'opi_clausula1_texto'     => $configs['opi_clausula1_texto'] ?? $defaults['opi_clausula1_texto'],
    'opi_clausula2_texto'     => $configs['opi_clausula2_texto'] ?? $defaults['opi_clausula2_texto'],
    'opi_pie_legal'           => $configs['opi_pie_legal'] ?? $defaults['opi_pie_legal'],
    'opi_firmas_linea_y'      => number_format($val_slider_y, 1, '.', ''),
    'simular_estampas'        => '1',
    'estampa_tema'            => $configs['estampa_tema'] ?? 'azul_institucional',
    'estampa_fuente'          => $configs['estampa_fuente'] ?? 'segoeui',
    'estampa_titulo_texto'    => $configs['estampa_titulo_texto'] ?? 'FIRMADO ELECTRÓNICAMENTE (FEA)',
    'estampa_icono'           => $configs['estampa_icono'] ?? 'check',
    'estampa_alto_mm'         => number_format($val_alto_estampa, 1, '.', ''),
    'estampa_tamano_texto'    => $configs['estampa_tamano_texto'] ?? 'normal',
    'estampa_mostrar_run'     => $configs['estampa_mostrar_run'] ?? '1',
    'estampa_mostrar_cargo'   => $configs['estampa_mostrar_cargo'] ?? '1',
    'estampa_mostrar_fecha'   => $configs['estampa_mostrar_fecha'] ?? '1',
    'estampa_mostrar_entidad' => $configs['estampa_mostrar_entidad'] ?? '1',
    'estampa_mostrar_ley'     => $configs['estampa_mostrar_ley'] ?? '1',
    'opi_firma1_titulo'       => $configs['opi_firma1_titulo'] ?? $defaults['opi_firma1_titulo'],
    'opi_firma1_subtitulo'    => $configs['opi_firma1_subtitulo'] ?? $defaults['opi_firma1_subtitulo'],
    'opi_firma2_titulo'       => $configs['opi_firma2_titulo'] ?? $defaults['opi_firma2_titulo'],
    'opi_firma2_subtitulo'    => $configs['opi_firma2_subtitulo'] ?? $defaults['opi_firma2_subtitulo'],
    'opi_firma3_titulo'       => $configs['opi_firma3_titulo'] ?? $defaults['opi_firma3_titulo'],
    'opi_firma3_subtitulo'    => $configs['opi_firma3_subtitulo'] ?? $defaults['opi_firma3_subtitulo'],
    't'                       => time()
]);
$url_preview_inicial = 'preview_opi_diseno.php?' . $query_preview_inicial;
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <?php 
    $titulo_pagina = "Diseñador Visual de Plantilla OPI";
    include __DIR__ . '/head.php'; 
    ?>
    <style>
        @media (min-width: 992px) {
            .preview-pane {
                position: sticky;
                top: 20px;
                height: calc(100vh - 100px);
                min-height: 650px;
            }
        }
        @media (max-width: 991.98px) {
            .preview-pane {
                position: relative;
                height: 620px;
            }
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

                    <!-- TARJETA 0: FORMATO Y TAMAÑO DE HOJA -->
                    <div class="card shadow-sm border-light mb-3">
                        <div class="card-header bg-white py-2.5 border-bottom d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-aspect-ratio text-primary fs-5"></i>
                                <h6 class="fw-bold mb-0 text-dark">Formato y Dimensiones de Impresión</h6>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                Estándar Chile
                            </span>
                        </div>
                        <div class="card-body p-3">
                            <?php 
                            $tamano_actual = $configs['opi_tamano_papel'] ?? 'OFICIO';
                            $es_oficio_actual = ($tamano_actual === 'OFICIO');
                            ?>
                            <div class="mb-0">
                                <label class="form-label fw-bold text-secondary small mb-1">Tamaño de Hoja para OPIs</label>
                                <select name="opi_tamano_papel" id="selectTamanoPapel" class="form-select form-select-sm fw-bold text-dark">
                                    <option value="OFICIO" <?= $es_oficio_actual ? 'selected' : '' ?>>Oficio Chileno (8.5 x 13" — 215.9 x 330.2 mm) [Recomendado: +5cm de espacio]</option>
                                    <option value="LETTER" <?= !$es_oficio_actual ? 'selected' : '' ?>>Carta / Letter (8.5 x 11" — 215.9 x 279.4 mm) [Estándar Corto]</option>
                                </select>
                                <div class="form-text small text-muted">Oficio Chileno añade +50.8 mm de altura permitiendo hasta 16 ítems en una sola página sin descuadres ni saltos de hoja.</div>
                                <div class="alert alert-info py-1.5 px-2.5 mt-2 mb-0 small d-flex align-items-center gap-2" style="font-size: 11.5px;">
                                    <i class="bi bi-info-circle-fill text-primary fs-6"></i>
                                    <span><strong>Consejo de Impresión:</strong> Si en sus bandejas de impresora cargan resmas estándar <strong>Carta</strong>, seleccione <em>Carta</em> para evitar que la impresora reduzca la escala automáticamente al 84%.</span>
                                </div>
                            </div>
                        </div>
                    </div>

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
                                    <span class="badge bg-primary range-value-badge" id="badgePosY"><?= htmlspecialchars(number_format($val_slider_y, 1, '.', '')) ?> mm</span>
                                </div>
                                <input type="range" class="form-range" id="rangePosY" name="opi_firmas_linea_y" min="<?= $min_slider_y ?>" max="<?= $max_slider_y ?>" step="0.5" value="<?= htmlspecialchars(number_format($val_slider_y, 1, '.', '')) ?>">
                                <div class="d-flex justify-content-between text-muted" style="font-size: 10px;">
                                    <span id="sliderHelpMin"><?= $es_oficio_actual ? '275 mm (Más arriba)' : '235 mm (Más arriba)' ?></span>
                                    <span id="sliderHelpOpt"><?= $es_oficio_actual ? '306 mm (Óptimo Oficio)' : '256 mm (Óptimo Carta)' ?></span>
                                    <span id="sliderHelpMax"><?= $es_oficio_actual ? '318 mm (Más abajo)' : '265 mm (Más abajo)' ?></span>
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

                    <!-- TARJETA 3: ESTILO Y PERSONALIZACIÓN DE LA ESTAMPA DIGITAL FIRMAGOB -->
                    <div class="card shadow-sm border-light mb-3">
                        <div class="card-header bg-white py-2.5 border-bottom d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-shield-check text-primary fs-5"></i>
                                <h6 class="fw-bold mb-0 text-dark">3. Diseño de Estampa Digital FirmaGob</h6>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                Motor HD FreeType
                            </span>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-2 mb-3">
                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-bold text-secondary small mb-1">Paleta Cromática / Tema</label>
                                    <select name="estampa_tema" id="selectEstampaTema" class="form-select form-select-sm fw-bold">
                                        <option value="azul_institucional" <?= ($configs['estampa_tema'] ?? '') === 'azul_institucional' ? 'selected' : '' ?>>Azul Institucional (Gobierno)</option>
                                        <option value="verde_validacion" <?= ($configs['estampa_tema'] ?? '') === 'verde_validacion' ? 'selected' : '' ?>>Verde Certificación (Seguro)</option>
                                        <option value="monocromatico" <?= ($configs['estampa_tema'] ?? '') === 'monocromatico' ? 'selected' : '' ?>>Gris Pizarra (Monocromo)</option>
                                        <option value="barra_lateral" <?= ($configs['estampa_tema'] ?? '') === 'barra_lateral' ? 'selected' : '' ?>>Minimalista con Barra Lateral</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-bold text-secondary small mb-1">Tipografía TrueType</label>
                                    <select name="estampa_fuente" id="selectEstampaFuente" class="form-select form-select-sm fw-bold">
                                        <option value="segoeui" <?= ($configs['estampa_fuente'] ?? '') === 'segoeui' ? 'selected' : '' ?>>Segoe UI (Moderna & Nítida)</option>
                                        <option value="calibri" <?= ($configs['estampa_fuente'] ?? '') === 'calibri' ? 'selected' : '' ?>>Calibri (Corporativa Compacta)</option>
                                        <option value="arial" <?= ($configs['estampa_fuente'] ?? '') === 'arial' ? 'selected' : '' ?>>Arial (Formal Clásica)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-12 col-sm-7">
                                    <label class="form-label fw-bold text-secondary small mb-1">Texto Cabecera de la Estampa</label>
                                    <input type="text" name="estampa_titulo_texto" id="inputEstampaTitulo" class="form-control form-control-sm fw-bold" maxlength="50" placeholder="Ej: FIRMADO ELECTRÓNICAMENTE (FEA)" value="<?= htmlspecialchars($configs['estampa_titulo_texto'] ?? 'FIRMADO ELECTRÓNICAMENTE (FEA)') ?>">
                                </div>
                                <div class="col-12 col-sm-5">
                                    <label class="form-label fw-bold text-secondary small mb-1">Icono / Distintivo</label>
                                    <select name="estampa_icono" id="selectEstampaIcono" class="form-select form-select-sm fw-bold">
                                        <option value="check" <?= ($configs['estampa_icono'] ?? '') === 'check' ? 'selected' : '' ?>>✓ Tilde de Verificación</option>
                                        <option value="candado" <?= ($configs['estampa_icono'] ?? '') === 'candado' ? 'selected' : '' ?>>🔒 Candado Criptográfico</option>
                                        <option value="escudo" <?= ($configs['estampa_icono'] ?? '') === 'escudo' ? 'selected' : '' ?>>🛡️ Escudo Institucional</option>
                                        <option value="ninguno" <?= ($configs['estampa_icono'] ?? '') === 'ninguno' ? 'selected' : '' ?>>Sin Icono (Solo Texto)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-bold text-secondary small mb-1">Tamaño de Texto / Escala</label>
                                    <select name="estampa_tamano_texto" id="selectEstampaTamanoTexto" class="form-select form-select-sm fw-bold">
                                        <option value="normal" <?= ($configs['estampa_tamano_texto'] ?? '') === 'normal' ? 'selected' : '' ?>>Estándar (Legible & Nítida)</option>
                                        <option value="grande" <?= ($configs['estampa_tamano_texto'] ?? '') === 'grande' ? 'selected' : '' ?>>Grande (+15% Visibilidad)</option>
                                        <option value="extragrande" <?= ($configs['estampa_tamano_texto'] ?? '') === 'extragrande' ? 'selected' : '' ?>>Extra Grande (+28% Máxima Lectura)</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label fw-bold text-secondary small mb-0">Altura de Estampa en PDF</label>
                                        <span class="badge bg-secondary range-value-badge" id="badgeEstampaAlto"><?= htmlspecialchars(number_format($val_alto_estampa, 1, '.', '')) ?> mm</span>
                                    </div>
                                    <input type="range" class="form-range" id="rangeEstampaAlto" name="estampa_alto_mm" min="18.0" max="28.0" step="0.5" value="<?= htmlspecialchars(number_format($val_alto_estampa, 1, '.', '')) ?>">
                                    <div class="d-flex justify-content-between text-muted" style="font-size: 10px;">
                                        <span>18 mm (Compacto)</span>
                                        <span class="fw-bold text-primary">24 mm (Óptimo)</span>
                                        <span>28 mm (Prominente)</span>
                                    </div>
                                </div>
                            </div>

                            <label class="form-label fw-bold text-secondary small mb-1.5">Metadatos a Incluir en la Estampa</label>
                            <div class="p-2.5 bg-light rounded-3 border">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="form-check form-switch mb-1.5">
                                            <input class="form-check-input check-estampa-meta" type="checkbox" name="estampa_mostrar_run" id="swEstampaRun" value="1" <?= ($configs['estampa_mostrar_run'] ?? '1') === '1' ? 'checked' : '' ?>>
                                            <label class="form-check-label small fw-semibold" for="swEstampaRun">RUN del firmante</label>
                                        </div>
                                        <div class="form-check form-switch mb-1.5">
                                            <input class="form-check-input check-estampa-meta" type="checkbox" name="estampa_mostrar_cargo" id="swEstampaCargo" value="1" <?= ($configs['estampa_mostrar_cargo'] ?? '1') === '1' ? 'checked' : '' ?>>
                                            <label class="form-check-label small fw-semibold" for="swEstampaCargo">Cargo / Rol</label>
                                        </div>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input check-estampa-meta" type="checkbox" name="estampa_mostrar_fecha" id="swEstampaFecha" value="1" <?= ($configs['estampa_mostrar_fecha'] ?? '1') === '1' ? 'checked' : '' ?>>
                                            <label class="form-check-label small fw-semibold" for="swEstampaFecha">Fecha y hora CLT</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check form-switch mb-1.5">
                                            <input class="form-check-input check-estampa-meta" type="checkbox" name="estampa_mostrar_entidad" id="swEstampaEntidad" value="1" <?= ($configs['estampa_mostrar_entidad'] ?? '1') === '1' ? 'checked' : '' ?>>
                                            <label class="form-check-label small fw-semibold" for="swEstampaEntidad">Entidad (Mun. Lebu)</label>
                                        </div>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input check-estampa-meta" type="checkbox" name="estampa_mostrar_ley" id="swEstampaLey" value="1" <?= ($configs['estampa_mostrar_ley'] ?? '1') === '1' ? 'checked' : '' ?>>
                                            <label class="form-check-label small fw-semibold" for="swEstampaLey">Mención Ley N° 19.799</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TARJETA 4: PERSONALIZACIÓN DE PIES DE FIRMA Y CARGOS -->
                    <div class="card shadow-sm border-light mb-3">
                        <div class="card-header bg-white py-2.5 border-bottom d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-people-fill text-primary fs-5"></i>
                                <h6 class="fw-bold mb-0 text-dark">4. Textos de Pies de Firma y Cargos</h6>
                            </div>
                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                3 Firmantes
                            </span>
                        </div>
                        <div class="card-body p-3">
                            <div class="alert alert-light border py-2 px-3 small text-muted mb-3 d-flex align-items-center gap-2">
                                <i class="bi bi-info-circle text-primary fs-6"></i>
                                <div>Personalice los títulos principales y subtítulos de rol que se imprimen bajo cada una de las 3 líneas de firma.</div>
                            </div>

                            <!-- FIRMA 1 (IZQUIERDA) -->
                            <div class="p-2.5 bg-light rounded-3 border mb-2.5">
                                <div class="d-flex align-items-center gap-1.5 mb-2">
                                    <span class="badge bg-primary text-white rounded-pill" style="font-size: 10px;">Firma 1</span>
                                    <span class="fw-bold small text-dark">Izquierda (Unidad Solicitante / Origen)</span>
                                </div>
                                <div class="row g-2">
                                    <div class="col-12 col-sm-6">
                                        <label class="form-label fw-bold text-secondary small mb-1" style="font-size: 11px;">Título Principal</label>
                                        <input type="text" name="opi_firma1_titulo" id="inputFirma1Tit" class="form-control form-control-sm fw-bold input-pie-firma" maxlength="45" value="<?= htmlspecialchars($configs['opi_firma1_titulo'] ?? $defaults['opi_firma1_titulo']) ?>">
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <label class="form-label fw-bold text-secondary small mb-1" style="font-size: 11px;">Subtítulo de Rol / Acto</label>
                                        <input type="text" name="opi_firma1_subtitulo" id="inputFirma1Sub" class="form-control form-control-sm input-pie-firma" maxlength="50" value="<?= htmlspecialchars($configs['opi_firma1_subtitulo'] ?? $defaults['opi_firma1_subtitulo']) ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- FIRMA 2 (CENTRO) -->
                            <div class="p-2.5 bg-light rounded-3 border mb-2.5">
                                <div class="d-flex align-items-center gap-1.5 mb-2">
                                    <span class="badge bg-success text-white rounded-pill" style="font-size: 10px;">Firma 2</span>
                                    <span class="fw-bold small text-dark">Centro (Control Presupuestario / Finanzas)</span>
                                </div>
                                <div class="row g-2">
                                    <div class="col-12 col-sm-6">
                                        <label class="form-label fw-bold text-secondary small mb-1" style="font-size: 11px;">Título Principal</label>
                                        <input type="text" name="opi_firma2_titulo" id="inputFirma2Tit" class="form-control form-control-sm fw-bold input-pie-firma" maxlength="45" value="<?= htmlspecialchars($configs['opi_firma2_titulo'] ?? $defaults['opi_firma2_titulo']) ?>">
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <label class="form-label fw-bold text-secondary small mb-1" style="font-size: 11px;">Subtítulo de Rol / Acto</label>
                                        <input type="text" name="opi_firma2_subtitulo" id="inputFirma2Sub" class="form-control form-control-sm input-pie-firma" maxlength="50" value="<?= htmlspecialchars($configs['opi_firma2_subtitulo'] ?? $defaults['opi_firma2_subtitulo']) ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- FIRMA 3 (DERECHA) -->
                            <div class="p-2.5 bg-light rounded-3 border mb-0">
                                <div class="d-flex align-items-center gap-1.5 mb-2">
                                    <span class="badge bg-dark text-white rounded-pill" style="font-size: 10px;">Firma 3</span>
                                    <span class="fw-bold small text-dark">Derecha (Autorización Final / Alcaldía / Adm.)</span>
                                </div>
                                <div class="row g-2">
                                    <div class="col-12 col-sm-6">
                                        <label class="form-label fw-bold text-secondary small mb-1" style="font-size: 11px;">Título Principal</label>
                                        <input type="text" name="opi_firma3_titulo" id="inputFirma3Tit" class="form-control form-control-sm fw-bold input-pie-firma" maxlength="45" value="<?= htmlspecialchars($configs['opi_firma3_titulo'] ?? $defaults['opi_firma3_titulo']) ?>">
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <label class="form-label fw-bold text-secondary small mb-1" style="font-size: 11px;">Subtítulo de Rol / Acto</label>
                                        <input type="text" name="opi_firma3_subtitulo" id="inputFirma3Sub" class="form-control form-control-sm input-pie-firma" maxlength="50" value="<?= htmlspecialchars($configs['opi_firma3_subtitulo'] ?? $defaults['opi_firma3_subtitulo']) ?>">
                                    </div>
                                </div>
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
                            <a id="btnAbrirNuevaPestana" href="<?= htmlspecialchars($url_preview_inicial) ?>" target="_blank" class="btn btn-sm btn-outline-primary py-0.5 px-2" title="Abrir PDF en pestaña independiente">
                                <i class="bi bi-box-arrow-up-right me-1"></i> Abrir PDF
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0 flex-grow-1 bg-dark">
                        <iframe id="previewIframe" src="<?= htmlspecialchars($url_preview_inicial) ?>#toolbar=1&navpanes=0&view=FitH" class="preview-iframe" title="Vista Previa de la OPI"></iframe>
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
        const selectTam = document.getElementById('selectTamanoPapel');
        const tamano = selectTam ? encodeURIComponent(selectTam.value) : 'OFICIO';
        const titulo = encodeURIComponent(document.getElementById('inputTitulo').value);
        const clausula1 = encodeURIComponent(document.getElementById('inputClausula1').value);
        const clausula2 = encodeURIComponent(document.getElementById('inputClausula2').value);
        const pieLegal = encodeURIComponent(document.getElementById('inputPieLegal').value);
        const posY = encodeURIComponent(document.getElementById('rangePosY').value);
        const simular = document.getElementById('swSimularEstampas').checked ? '1' : '0';

        // Metadatos de Estampa FirmaGob
        const estampaTema = encodeURIComponent(document.getElementById('selectEstampaTema')?.value || 'azul_institucional');
        const estampaFuente = encodeURIComponent(document.getElementById('selectEstampaFuente')?.value || 'segoeui');
        const estampaTitulo = encodeURIComponent(document.getElementById('inputEstampaTitulo')?.value || '');
        const estampaIcono = encodeURIComponent(document.getElementById('selectEstampaIcono')?.value || 'check');
        const estampaRun = document.getElementById('swEstampaRun')?.checked ? '1' : '0';
        const estampaCargo = document.getElementById('swEstampaCargo')?.checked ? '1' : '0';
        const estampaFecha = document.getElementById('swEstampaFecha')?.checked ? '1' : '0';
        const estampaEntidad = document.getElementById('swEstampaEntidad')?.checked ? '1' : '0';
        const estampaLey = document.getElementById('swEstampaLey')?.checked ? '1' : '0';

        // Escala y Altura de Estampa
        const estampaAlto = encodeURIComponent(document.getElementById('rangeEstampaAlto')?.value || '24.0');
        const estampaTamanoTexto = encodeURIComponent(document.getElementById('selectEstampaTamanoTexto')?.value || 'normal');

        // Pies de Firma y Cargos Dinámicos
        const f1Tit = encodeURIComponent(document.getElementById('inputFirma1Tit')?.value || '');
        const f1Sub = encodeURIComponent(document.getElementById('inputFirma1Sub')?.value || '');
        const f2Tit = encodeURIComponent(document.getElementById('inputFirma2Tit')?.value || '');
        const f2Sub = encodeURIComponent(document.getElementById('inputFirma2Sub')?.value || '');
        const f3Tit = encodeURIComponent(document.getElementById('inputFirma3Tit')?.value || '');
        const f3Sub = encodeURIComponent(document.getElementById('inputFirma3Sub')?.value || '');

        return `opi_tamano_papel=${tamano}&opi_titulo_documento=${titulo}&opi_clausula1_texto=${clausula1}&opi_clausula2_texto=${clausula2}&opi_pie_legal=${pieLegal}&opi_firmas_linea_y=${posY}&simular_estampas=${simular}&estampa_tema=${estampaTema}&estampa_fuente=${estampaFuente}&estampa_titulo_texto=${estampaTitulo}&estampa_icono=${estampaIcono}&estampa_mostrar_run=${estampaRun}&estampa_mostrar_cargo=${estampaCargo}&estampa_mostrar_fecha=${estampaFecha}&estampa_mostrar_entidad=${estampaEntidad}&estampa_mostrar_ley=${estampaLey}&estampa_alto_mm=${estampaAlto}&estampa_tamano_texto=${estampaTamanoTexto}&opi_firma1_titulo=${f1Tit}&opi_firma1_subtitulo=${f1Sub}&opi_firma2_titulo=${f2Tit}&opi_firma2_subtitulo=${f2Sub}&opi_firma3_titulo=${f3Tit}&opi_firma3_subtitulo=${f3Sub}&t=${Date.now()}`;
    }

    function actualizarVistaPrevia() {
        const query = obtenerParametrosPreview();
        const urlVisual = `preview_opi_diseno.php?${query}#toolbar=1&navpanes=0&view=FitH`;
        const urlDirecta = `preview_opi_diseno.php?${query}`;
        document.getElementById('previewIframe').src = urlVisual;
        document.getElementById('btnAbrirNuevaPestana').href = urlDirecta;
    }

    function dispararActualizacionDebounce() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            actualizarVistaPrevia();
        }, 350);
    }

    function adaptarControlesTamanoPapel(tamano, recalcularY = false) {
        const range = document.getElementById('rangePosY');
        const badge = document.getElementById('badgePosY');
        const helpMin = document.getElementById('sliderHelpMin');
        const helpOpt = document.getElementById('sliderHelpOpt');
        const helpMax = document.getElementById('sliderHelpMax');

        if (tamano === 'OFICIO') {
            range.min = "275";
            range.max = "318";
            if (recalcularY || parseFloat(range.value) < 275 || parseFloat(range.value) > 318) {
                range.value = "306.0";
            }
            if (helpMin) helpMin.textContent = "275 mm (Más arriba)";
            if (helpOpt) helpOpt.textContent = "306 mm (Óptimo Oficio)";
            if (helpMax) helpMax.textContent = "318 mm (Más abajo)";
        } else {
            range.min = "235";
            range.max = "265";
            if (recalcularY || parseFloat(range.value) < 235 || parseFloat(range.value) > 265) {
                range.value = "256.0";
            }
            if (helpMin) helpMin.textContent = "235 mm (Más arriba)";
            if (helpOpt) helpOpt.textContent = "256 mm (Óptimo Carta)";
            if (helpMax) helpMax.textContent = "265 mm (Más abajo)";
        }
        if (badge) badge.textContent = parseFloat(range.value).toFixed(1) + ' mm';
    }

    // Escuchar cambio de tamaño de papel
    const selectTamanoPapel = document.getElementById('selectTamanoPapel');
    if (selectTamanoPapel) {
        selectTamanoPapel.addEventListener('change', function() {
            adaptarControlesTamanoPapel(this.value, true);
            actualizarVistaPrevia();
        });
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

    // Sincronizar Slider de Alto de Estampa
    const rangeEstampaAlto = document.getElementById('rangeEstampaAlto');
    const badgeEstampaAlto = document.getElementById('badgeEstampaAlto');
    if (rangeEstampaAlto && badgeEstampaAlto) {
        rangeEstampaAlto.addEventListener('input', function() {
            badgeEstampaAlto.textContent = parseFloat(this.value).toFixed(1) + ' mm';
            asegurarEstampasActivas();
            dispararActualizacionDebounce();
        });
    }

    // Escuchar cambios en todos los campos de texto
    ['inputTitulo', 'inputClausula1', 'inputClausula2', 'inputPieLegal', 'inputEstampaTitulo'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', dispararActualizacionDebounce);
        }
    });

    // Escuchar cambios en los 6 campos de pies de firma y cargos
    document.querySelectorAll('.input-pie-firma').forEach(el => {
        el.addEventListener('input', dispararActualizacionDebounce);
    });

    // Auto-activar conmutador de simulación si se edita el diseño de la estampa
    function asegurarEstampasActivas() {
        const sw = document.getElementById('swSimularEstampas');
        if (sw && !sw.checked) {
            sw.checked = true;
        }
    }

    // Escuchar texto de cabecera de estampa con auto-activación
    const inputTituloEstampa = document.getElementById('inputEstampaTitulo');
    if (inputTituloEstampa) {
        inputTituloEstampa.addEventListener('input', () => {
            asegurarEstampasActivas();
            dispararActualizacionDebounce();
        });
    }

    // Escuchar cambios en selectores de estampa
    ['selectEstampaTema', 'selectEstampaFuente', 'selectEstampaIcono', 'selectEstampaTamanoTexto'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', () => {
                asegurarEstampasActivas();
                actualizarVistaPrevia();
            });
        }
    });

    // Escuchar checkboxes de metadatos de estampa
    document.querySelectorAll('.check-estampa-meta').forEach(cb => {
        cb.addEventListener('change', () => {
            asegurarEstampasActivas();
            actualizarVistaPrevia();
        });
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

    // Carga inicial: calibrar controles visuales (la vista previa ya cargó sincronizada desde PHP)
    document.addEventListener('DOMContentLoaded', () => {
        const selTam = document.getElementById('selectTamanoPapel');
        if (selTam) {
            adaptarControlesTamanoPapel(selTam.value, false);
        }
    });
    </script>
</body>
</html>
