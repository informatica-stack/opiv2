<?php
// preview_opi_diseno.php - Endpoint de Generacion de Vista Previa Dinamica de la OPI en Tiempo Real
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/fpdf.php';
require_once __DIR__ . '/pdf_helper.php';
require_once __DIR__ . '/firmagob_helper.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die("Acceso denegado.");
}

$rol = $_SESSION['user_rol'] ?? '';
if ($rol !== 'SYSADMIN' && $rol !== 'ADMIN_MUNICIPAL') {
    http_response_code(403);
    die("Acceso denegado. Se requieren privilegios de administracion.");
}

// Cargar configuraciones del sistema desde BD si no están inicializadas
if (!isset($config_sistema) || empty($config_sistema)) {
    try {
        $stmtCfg = $pdo->query("SELECT clave, valor FROM configuraciones_sistema");
        $config_sistema = $stmtCfg->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) {
        $config_sistema = [];
    }
}

// 1. Obtener parametros desde GET/POST con fallback a $config_sistema de BD
$tamano_papel   = isset($_REQUEST['opi_tamano_papel']) ? strtoupper(trim($_REQUEST['opi_tamano_papel'])) : ($config_sistema['opi_tamano_papel'] ?? 'OFICIO');
$es_oficio      = ($tamano_papel === 'OFICIO');
$dimensiones    = $es_oficio ? [215.9, 330.2] : 'Letter';
$alto_pagina_mm = $es_oficio ? 330.2 : 279.4;
$y_firmas_default = $es_oficio ? 306.0 : 256.0;

$titulo_doc     = $_REQUEST['opi_titulo_documento'] ?? ($config_sistema['opi_titulo_documento'] ?? 'ORDEN DE PEDIDO INTERNO');
$clausula1_txt  = $_REQUEST['opi_clausula1_texto'] ?? ($config_sistema['opi_clausula1_texto'] ?? '1. Agradeceré a Usted, tenga a bien efectuar la adquisición de los siguientes bienes y/o servicios:');
$clausula2_txt  = $_REQUEST['opi_clausula2_texto'] ?? ($config_sistema['opi_clausula2_texto'] ?? '2. Los presentes bienes/servicios serán destinados a:');
$pie_legal_txt  = $_REQUEST['opi_pie_legal'] ?? ($config_sistema['opi_pie_legal'] ?? 'Documento Oficial emitido por el Sistema Institucional OPI - Validez legal bajo Ley N° 19.799 de Firma Electrónica');
$firmas_linea_y = isset($_REQUEST['opi_firmas_linea_y']) ? floatval($_REQUEST['opi_firmas_linea_y']) : floatval($config_sistema['opi_firmas_linea_y'] ?? $y_firmas_default);
$simular_estampas = isset($_REQUEST['simular_estampas']) ? (int)$_REQUEST['simular_estampas'] : 1;

// Títulos y subtítulos configurables de los 3 pies de firma (permite subtítulos vacíos)
$firma1_tit = isset($_REQUEST['opi_firma1_titulo']) ? $_REQUEST['opi_firma1_titulo'] : (array_key_exists('opi_firma1_titulo', $config_sistema) ? $config_sistema['opi_firma1_titulo'] : 'JEFATURA UNIDAD SOLICITANTE');
$firma1_sub = isset($_REQUEST['opi_firma1_subtitulo']) ? $_REQUEST['opi_firma1_subtitulo'] : (array_key_exists('opi_firma1_subtitulo', $config_sistema) ? $config_sistema['opi_firma1_subtitulo'] : 'V°B° Requerimiento Técnico');
$firma2_tit = isset($_REQUEST['opi_firma2_titulo']) ? $_REQUEST['opi_firma2_titulo'] : (array_key_exists('opi_firma2_titulo', $config_sistema) ? $config_sistema['opi_firma2_titulo'] : 'DIRECCIÓN DE ADM. Y FINANZAS');
$firma2_sub = isset($_REQUEST['opi_firma2_subtitulo']) ? $_REQUEST['opi_firma2_subtitulo'] : (array_key_exists('opi_firma2_subtitulo', $config_sistema) ? $config_sistema['opi_firma2_subtitulo'] : 'Control e Imputación Presupuestaria');
$firma3_tit = isset($_REQUEST['opi_firma3_titulo']) ? $_REQUEST['opi_firma3_titulo'] : (array_key_exists('opi_firma3_titulo', $config_sistema) ? $config_sistema['opi_firma3_titulo'] : 'ADMINISTRADOR MUNICIPAL');
$firma3_sub = isset($_REQUEST['opi_firma3_subtitulo']) ? $_REQUEST['opi_firma3_subtitulo'] : (array_key_exists('opi_firma3_subtitulo', $config_sistema) ? $config_sistema['opi_firma3_subtitulo'] : 'Autorización Final del Gasto');

// Altura física de la estampa en el PDF (entre 18 y 28 mm, recomendado 24 mm)
$alto_estampa_mm = isset($_REQUEST['estampa_alto_mm']) ? floatval($_REQUEST['estampa_alto_mm']) : floatval($config_sistema['estampa_alto_mm'] ?? 24.0);
if ($alto_estampa_mm < 18.0 || $alto_estampa_mm > 28.0) {
    $alto_estampa_mm = 24.0;
}

// Parámetros de personalización de la estampa digital
$estampa_opciones = [
    'estampa_tema'            => $_REQUEST['estampa_tema'] ?? ($config_sistema['estampa_tema'] ?? 'azul_institucional'),
    'estampa_fuente'          => $_REQUEST['estampa_fuente'] ?? ($config_sistema['estampa_fuente'] ?? 'segoeui'),
    'estampa_titulo_texto'    => $_REQUEST['estampa_titulo_texto'] ?? ($config_sistema['estampa_titulo_texto'] ?? 'FIRMADO ELECTRÓNICAMENTE (FEA)'),
    'estampa_icono'           => $_REQUEST['estampa_icono'] ?? ($config_sistema['estampa_icono'] ?? 'check'),
    'estampa_tamano_texto'    => $_REQUEST['estampa_tamano_texto'] ?? ($config_sistema['estampa_tamano_texto'] ?? 'normal'),
    'estampa_mostrar_run'     => $_REQUEST['estampa_mostrar_run'] ?? ($config_sistema['estampa_mostrar_run'] ?? '1'),
    'estampa_mostrar_cargo'   => $_REQUEST['estampa_mostrar_cargo'] ?? ($config_sistema['estampa_mostrar_cargo'] ?? '1'),
    'estampa_mostrar_fecha'   => $_REQUEST['estampa_mostrar_fecha'] ?? ($config_sistema['estampa_mostrar_fecha'] ?? '1'),
    'estampa_mostrar_entidad' => $_REQUEST['estampa_mostrar_entidad'] ?? ($config_sistema['estampa_mostrar_entidad'] ?? '1'),
    'estampa_mostrar_ley'     => $_REQUEST['estampa_mostrar_ley'] ?? ($config_sistema['estampa_mostrar_ley'] ?? '1'),
];

// 2. Instanciar FPDF (Oficio Chileno 215.9 x 330.2 mm o Carta 215.9 x 279.4 mm)
$pdf = new FPDF('P', 'mm', $dimensiones);
$pdf->SetMargins(14, 10, 14);
$pdf->SetAutoPageBreak(false);
$pdf->AddPage();

$utf = function($text) {
    return iconv('UTF-8', 'windows-1252//TRANSLIT', (string)$text);
};

// Helper seguro para textos multibyte con longitud máxima opcional
$safe_text = function($text, $max_len = 0) use ($utf) {
    $str = (string)($text ?? '');
    if ($max_len > 0 && mb_strlen($str, 'UTF-8') > $max_len) {
        $str = mb_substr($str, 0, $max_len - 3, 'UTF-8') . '...';
    }
    return $utf($str);
};

// --- ENCABEZADO INSTITUCIONAL ---
if (file_exists(__DIR__ . '/logo.png')) {
    $pdf->Image(__DIR__ . '/logo.png', 14, 8, 22);
}

// Columna Izquierda: Institución y Unidad Solicitante de muestra
$pdf->SetXY(38, 8);
$pdf->SetFont('Arial', '', 7.5);
$pdf->Cell(54, 3.5, $utf("República de Chile"), 0, 1, 'L');
$pdf->SetX(38);
$pdf->SetFont('Arial', 'B', 8.5);
$pdf->Cell(54, 4, $utf("MUNICIPALIDAD DE LEBU"), 0, 1, 'L');
$pdf->SetX(38);
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(54, 3.5, $utf("DEPARTAMENTO DE ADQUISICIONES"), 0, 1, 'L');

// Columna Centro: Título Oficial Configurable
$pdf->SetXY(92, 9);
$pdf->SetFont('Arial', 'B', 10.5);
$pdf->Cell(54, 5, $safe_text($titulo_doc, 32), 0, 1, 'C');

// Columna Derecha: Folio Muestra y Fecha
$pdf->SetXY(146, 8);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(56, 4.5, $utf("N° OPI-" . date('Y') . "-0001 (VISTA PREVIA)"), 0, 1, 'R');
$pdf->SetXY(146, 13);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(56, 4, $utf("Lebu, " . pdf_fecha_espanol(date('Y-m-d'))), 0, 1, 'R');

// Línea separadora
$pdf->SetDrawColor(180, 180, 180);
$pdf->SetLineWidth(0.3);
$pdf->Line(14, 22.5, 202, 22.5);

// --- SECCIÓN DE / A ---
$pdf->SetXY(14, 24);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(8, 4, $utf("DE:"), 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(180, 4, $utf("DIRECCIÓN DE DESARROLLO COMUNITARIO (DIDECO)"), 0, 1, 'L');

$pdf->SetX(14);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(8, 4, $utf("A:"), 0, 0, 'L');
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(180, 4, $utf("DIRECCIÓN DE ADMINISTRACIÓN Y FINANZAS - UNIDAD DE ADQUISICIONES"), 0, 1, 'L');

// --- CLÁUSULA 1: TEXTO INTRODUCTORIO CONFIGURABLE (Soporte multilínea dinámico) ---
$pdf->SetXY(14, 33);
$pdf->SetFont('Arial', '', 7.5);
$pdf->MultiCell(188, 3.5, $utf($clausula1_txt), 0, 'L');

// --- TABLA PRINCIPAL DE PRODUCTOS / SERVICIOS (DATOS DE MUESTRA) ---
$y_tabla = max(39.0, $pdf->GetY() + 1.5);
$pdf->SetXY(14, $y_tabla);
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetFillColor(235, 238, 242);
$pdf->SetFont('Arial', 'B', 7);

$pdf->Cell(16, 5, $utf("CANT."), 1, 0, 'C', true);
$pdf->Cell(18, 5, $utf("UNIDAD"), 1, 0, 'C', true);
$pdf->Cell(92, 5, $utf("DESCRIPCIÓN DE LOS BIENES Y/O SERVICIOS"), 1, 0, 'L', true);
$pdf->Cell(31, 5, $utf("MONTO UNITARIO"), 1, 0, 'R', true);
$pdf->Cell(31, 5, $utf("MONTO TOTAL"), 1, 1, 'R', true);

$pdf->SetFont('Arial', '', 7.5);
$items_muestra = [
    ['cant' => 10, 'unidad' => 'UNID', 'desc' => 'RESMAS DE PAPEL CARTA 75 GR MULTIPROPÓSITO (ID CM: 154238)', 'unit' => 4500, 'total' => 45000],
    ['cant' => 5,  'unidad' => 'GLOBAL', 'desc' => 'SERVICIO DE MANTENCIÓN PREVENTIVA DE EQUIPOS COMPUTACIONALES', 'unit' => 60000, 'total' => 300000],
    ['cant' => 2,  'unidad' => 'SET', 'desc' => 'PACK DE TÓNER NEGRO DE ALTO RENDIMIENTO PARA IMPRESORAS', 'unit' => 35000, 'total' => 70000],
    ['cant' => '', 'unidad' => '', 'desc' => '', 'unit' => '', 'total' => '']
];

foreach ($items_muestra as $it) {
    if (empty($it['desc'])) {
        $pdf->SetX(14);
        $pdf->Cell(16, 4.8, "", 1, 0, 'C');
        $pdf->Cell(18, 4.8, "", 1, 0, 'C');
        $pdf->Cell(92, 4.8, "", 1, 0, 'L');
        $pdf->Cell(31, 4.8, "", 1, 0, 'R');
        $pdf->Cell(31, 4.8, "", 1, 1, 'R');
        continue;
    }

    $pdf->SetFont('Arial', '', 7.5);
    $desc = (string)$it['desc'];
    $ancho_txt = $pdf->GetStringWidth(" " . $desc);
    $lineas = max(1, (int)ceil($ancho_txt / 88.0));
    $lineas = min(3, $lineas);
    $line_h = 3.8;
    $row_h  = max(4.8, $lineas * $line_h);

    $x_fila = 14;
    $y_fila = $pdf->GetY();

    $pdf->Rect($x_fila, $y_fila, 188, $row_h);

    $pdf->SetXY($x_fila, $y_fila);
    $pdf->Cell(16, $row_h, $it['cant'] ? number_format($it['cant'], 0, ',', '.') : '', 'R', 0, 'C');
    $pdf->Cell(18, $row_h, $safe_text($it['unidad'], 8), 'R', 0, 'C');

    $pdf->SetXY($x_fila + 34, $y_fila + max(0, ($row_h - ($lineas * $line_h)) / 2));
    $pdf->MultiCell(92, $line_h, $safe_text($desc, 130), 0, 'L');

    $pdf->SetXY($x_fila + 34 + 92, $y_fila);
    $pdf->Cell(31, $row_h, $it['unit'] ? "$ " . number_format($it['unit'], 0, ',', '.') : '', 'LR', 0, 'R');
    $pdf->Cell(31, $row_h, $it['total'] ? "$ " . number_format($it['total'], 0, ',', '.') : '', 'L', 0, 'R');

    $pdf->SetXY($x_fila, $y_fila + $row_h);
}

// Totales de Muestra
$total_neto = 415000;
$iva_calc = round($total_neto * 0.19);
$total_bruto = $total_neto + $iva_calc;

$pdf->SetX(14);
$pdf->SetFont('Arial', 'B', 7.5);
$pdf->Cell(126, 4.5, "", 0, 0);
$pdf->Cell(31, 4.5, $utf("MONTO NETO:"), 1, 0, 'R', true);
$pdf->SetFont('Arial', '', 7.5);
$pdf->Cell(31, 4.5, "$ " . number_format($total_neto, 0, ',', '.'), 1, 1, 'R');

$pdf->SetX(14);
$pdf->SetFont('Arial', 'B', 7.5);
$pdf->Cell(126, 4.5, "", 0, 0);
$pdf->Cell(31, 4.5, $utf("I.V.A. (19%):"), 1, 0, 'R', true);
$pdf->SetFont('Arial', '', 7.5);
$pdf->Cell(31, 4.5, "$ " . number_format($iva_calc, 0, ',', '.'), 1, 1, 'R');

$pdf->SetX(14);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(126, 5, "", 0, 0);
$pdf->Cell(31, 5, $utf("TOTAL:"), 1, 0, 'R', true);
$pdf->SetFont('Arial', 'B', 8.5);
$pdf->Cell(31, 5, "$ " . number_format($total_bruto, 0, ',', '.'), 1, 1, 'R');

// --- RECUADROS INSTITUCIONALES DE INFORMACIÓN (Alineación Tabular Fija) ---
$pdf->SetDrawColor(160, 165, 175);
$pdf->SetLineWidth(0.25);
$y_cajas = $pdf->GetY() + 2.5;

// 1. RECUADRO DATOS PROVEEDOR (Cuadrícula simétrica 188mm)
$pdf->SetFillColor(248, 250, 252);
$pdf->Rect(14, $y_cajas, 188, 11, 'DF');

$pdf->SetXY(16, $y_cajas + 1.2);
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(34, 4, $utf("DATOS PROVEEDOR:"), 0, 0, 'L');

$pdf->SetXY(52, $y_cajas + 1.2);
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(25, 4, $utf("RAZÓN SOCIAL:"), 0, 0, 'L');
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(125, 4, $safe_text("DISTRIBUIDORA Y SERVICIOS INTEGRALES SUR LTDA.", 82), 0, 1, 'L');

$pdf->SetXY(52, $y_cajas + 5.8);
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(25, 4, $utf("RUT:"), 0, 0, 'L');
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(49, 4, $utf("76.890.123-4"), 0, 0, 'L');

$pdf->SetXY(126, $y_cajas + 5.8);
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(26, 4, $utf("DIRECCIÓN:"), 0, 0, 'L');
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(50, 4, $safe_text("Av. Ignacio Carrera Pinto N° 450, Lebu", 38), 0, 1, 'L');

// 2. RECUADRO IMPUTACIÓN PRESUPUESTARIA (Cuadrícula simétrica 188mm)
$y_caja2 = $y_cajas + 13.0;
$pdf->SetFillColor(248, 250, 252);
$pdf->Rect(14, $y_caja2, 188, 11, 'DF');

$pdf->SetXY(16, $y_caja2 + 1.2);
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(34, 4, $utf("IMPUTACIÓN PRESUP.:"), 0, 0, 'L');

$pdf->SetXY(52, $y_caja2 + 1.2);
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(25, 4, $utf("CUENTA N°:"), 0, 0, 'L');
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(49, 4, $utf("215.22.04.001"), 0, 0, 'L');

$pdf->SetXY(126, $y_caja2 + 1.2);
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(26, 4, $utf("CENTRO COSTO:"), 0, 0, 'L');
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(50, 4, $utf("11"), 0, 1, 'L'); // SOLO ID

$pdf->SetXY(52, $y_caja2 + 5.8);
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(25, 4, $utf("ÁREA GESTIÓN:"), 0, 0, 'L');
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(49, 4, $utf("01"), 0, 0, 'L');

$pdf->SetXY(126, $y_caja2 + 5.8);
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(26, 4, $utf("COMPLEMENTARIA:"), 0, 0, 'L');
$pdf->SetFont('Arial', '', 6.5);
$pdf->Cell(50, 4, $safe_text("AUT. EXT: Obras ($ 150.000)", 35), 0, 1, 'L');

// 3. RECUADRO PLAN DE COMPRAS Y MODALIDADES (Cuadrícula simétrica 188mm)
$y_caja3 = $y_caja2 + 13.0;
$pdf->SetFillColor(248, 250, 252);
$pdf->Rect(14, $y_caja3, 188, 16, 'DF');

$pdf->SetXY(16, $y_caja3 + 1.2);
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(34, 4, $utf("PLAN DE COMPRAS:"), 0, 0, 'L');

$pdf->SetXY(52, $y_caja3 + 1.2);
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(25, 4, $utf("PROYECTO ID:"), 0, 0, 'L');
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(49, 4, $utf("PROY-042"), 0, 0, 'L'); // SOLO ID

$pdf->SetXY(126, $y_caja3 + 1.2);
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(26, 4, $utf("ÍTEM N°:"), 0, 0, 'L');
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(50, 4, $utf("1"), 0, 1, 'L');

$pdf->SetXY(52, $y_caja3 + 5.8);
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(25, 4, $utf("C. SUMINISTROS:"), 0, 0, 'L');
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(49, 4, $safe_text("CS-2026-089", 24), 0, 0, 'L');

$pdf->SetXY(126, $y_caja3 + 5.8);
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(26, 4, $utf("CONV. MARCO OC:"), 0, 0, 'L');
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(50, 4, $safe_text("2345-789-CM26", 28), 0, 1, 'L');

$pdf->SetXY(52, $y_caja3 + 10.4);
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(25, 4, $utf("COMPRA ÁGIL ID:"), 0, 0, 'L');
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(49, 4, $safe_text("CA-8902", 24), 0, 0, 'L');

$pdf->SetXY(126, $y_caja3 + 10.4);
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(26, 4, $utf("DECRETO ALC. N°:"), 0, 0, 'L');
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(50, 4, $safe_text("DEC-ALC-758/2026", 28), 0, 1, 'L');

// --- CLÁUSULA 2: DESTINO DE LOS BIENES / SERVICIOS ---
$y_clausula2 = $y_caja3 + 18.0;
$pdf->SetXY(14, $y_clausula2);
$pdf->SetFont('Arial', 'B', 7.5);
$pdf->Cell(188, 4, $utf($clausula2_txt), 0, 1, 'L');

$pdf->SetXY(14, $y_clausula2 + 4.2);
$motivo_ejemplo = "Insumos y servicios requeridos para el normal desarrollo de los talleres comunitarios vecinales y operatividad de la oficina de atención ciudadana del Departamento durante el período 2026.";
$pdf->SetFont('Arial', '', 7.5);
$pdf->MultiCell(188, 3.5, $utf($motivo_ejemplo), 1, 'L');
$y_despues_motivo = $pdf->GetY();

// --- LÍNEAS DE BASE PARA LAS 3 FIRMAS DIGITALES (FIRMAGOB) ---
$tope_max_y = $alto_pagina_mm - 16.0;
$y_firmas_line = max($firmas_linea_y, $y_despues_motivo + 20.0);
if ($y_firmas_line > $tope_max_y) {
    $y_firmas_line = $tope_max_y;
}

$pdf->SetDrawColor(120, 120, 120);
$pdf->SetLineWidth(0.35);

// Línea 1: Jefatura Unidad Solicitante (Izquierda: X=14 a 72, Ancho=58mm)
$pdf->Line(14, $y_firmas_line, 72, $y_firmas_line);

// Línea 2: Dirección de Finanzas / Presupuesto (Centro: X=79 a 137, Ancho=58mm)
$pdf->Line(79, $y_firmas_line, 137, $y_firmas_line);

// Línea 3: Administrador Municipal (Derecha: X=144 a 202, Ancho=58mm)
$pdf->Line(144, $y_firmas_line, 202, $y_firmas_line);

// Subtítulos institucionales bajo cada línea de firma
$pdf->SetFont('Arial', 'B', 6);
$pdf->SetTextColor(70, 80, 95);

    $pdf->SetXY(14, $y_firmas_line + 1.2);
    $pdf->Cell(58, 2.8, $safe_text($firma1_tit, 42), 0, 1, 'C');
    $pdf->SetXY(14, $y_firmas_line + 3.8);
    $pdf->SetFont('Arial', '', 5.5);
    $pdf->Cell(58, 2.5, $safe_text($firma1_sub, 48), 0, 1, 'C');

    $pdf->SetFont('Arial', 'B', 6);
    $pdf->SetXY(79, $y_firmas_line + 1.2);
    $pdf->Cell(58, 2.8, $safe_text($firma2_tit, 42), 0, 1, 'C');
    $pdf->SetXY(79, $y_firmas_line + 3.8);
    $pdf->SetFont('Arial', '', 5.5);
    $pdf->Cell(58, 2.5, $safe_text($firma2_sub, 48), 0, 1, 'C');

    $pdf->SetFont('Arial', 'B', 6);
    $pdf->SetXY(144, $y_firmas_line + 1.2);
    $pdf->Cell(58, 2.8, $safe_text($firma3_tit, 42), 0, 1, 'C');
    $pdf->SetXY(144, $y_firmas_line + 3.8);
    $pdf->SetFont('Arial', '', 5.5);
    $pdf->Cell(58, 2.5, $safe_text($firma3_sub, 48), 0, 1, 'C');

    // --- SIMULACIÓN DE ESTAMPAS DIGITALES FIRMAGOB (SI ESTÁ ACTIVA LA OPCIÓN) ---
    if ($simular_estampas === 1) {
        $dibujar_estampa_real = function($x, $y_base, $nombre, $run, $cargo) use ($pdf, $estampa_opciones, $alto_estampa_mm) {
            $w = 58;
            $h = $alto_estampa_mm;
            $y_top = $y_base - $h - 0.5;

            // Generar estampa real idéntica a la que estampa FirmaGob
            $b64 = firmagob_generar_estampa_dinamica_base64($nombre, $run, $cargo, null, $estampa_opciones);
            if (!empty($b64)) {
                $pdf->Image('data://image/png;base64,' . $b64, $x, $y_top, $w, $h, 'PNG');
            }
        };

        // Estampa 1: Jefatura
        $dibujar_estampa_real(14, $y_firmas_line, "JUAN CARLOS ARRIAGADA", "17.439.829-1", "Jefe DIDECO");
        // Estampa 2: Presupuesto
        $dibujar_estampa_real(79, $y_firmas_line, "MARÍA JOSÉ CONTRERAS", "15.987.654-3", "Encargada Presupuesto");
        // Estampa 3: Administrador
        $dibujar_estampa_real(144, $y_firmas_line, "ROBERTO SANDOVAL MORA", "12.345.678-9", "Administrador Municipal");
    }

// Pie de página legal configurable
$y_pie = min($alto_pagina_mm - 7.0, $y_firmas_line + 7.5);
$pdf->SetXY(14, $y_pie);
$pdf->SetFont('Arial', '', 6.5);
$pdf->SetTextColor(110, 110, 110);
$pdf->Cell(188, 3, $utf($pie_legal_txt), 0, 1, 'C');

// Despacho de PDF
if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="preview_opi_diseno.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

$pdf->Output('I', 'preview_opi_diseno.pdf');
exit;
