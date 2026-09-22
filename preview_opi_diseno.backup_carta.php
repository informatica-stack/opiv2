<?php
// preview_opi_diseno.php - Endpoint de Generacion de Vista Previa Dinamica de la OPI en Tiempo Real
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/fpdf.php';
require_once __DIR__ . '/pdf_helper.php';

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

// 1. Obtener parametros desde GET/POST con fallback a $config_sistema de BD
$titulo_doc     = $_REQUEST['opi_titulo_documento'] ?? ($config_sistema['opi_titulo_documento'] ?? 'ORDEN DE PEDIDO INTERNO');
$clausula1_txt  = $_REQUEST['opi_clausula1_texto'] ?? ($config_sistema['opi_clausula1_texto'] ?? '1. Agradecere a Usted, tenga a bien efectuar la adquisicion de los siguientes bienes y/o servicios:');
$clausula2_txt  = $_REQUEST['opi_clausula2_texto'] ?? ($config_sistema['opi_clausula2_texto'] ?? '2. Los presentes bienes/servicios seran destinados a:');
$pie_legal_txt  = $_REQUEST['opi_pie_legal'] ?? ($config_sistema['opi_pie_legal'] ?? 'Documento Oficial emitido por el Sistema Institucional OPI - Validez legal bajo Ley N° 19.799 de Firma Electronica');
$firmas_linea_y = isset($_REQUEST['opi_firmas_linea_y']) ? floatval($_REQUEST['opi_firmas_linea_y']) : floatval($config_sistema['opi_firmas_linea_y'] ?? 256.0);
$simular_estampas = isset($_REQUEST['simular_estampas']) ? (int)$_REQUEST['simular_estampas'] : 1;

// 2. Instanciar FPDF (Carta: 215.9 x 279.4 mm, margenes 14mm)
$pdf = new FPDF('P', 'mm', 'Letter');
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
    $pdf->SetX(14);
    $pdf->Cell(16, 4.8, $it['cant'] ? number_format($it['cant'], 0, ',', '.') : '', 1, 0, 'C');
    $pdf->Cell(18, 4.8, $utf($it['unidad']), 1, 0, 'C');
    $pdf->Cell(92, 4.8, $utf(" " . $it['desc']), 1, 0, 'L');
    $pdf->Cell(31, 4.8, $it['unit'] ? "$ " . number_format($it['unit'], 0, ',', '.') : '', 1, 0, 'R');
    $pdf->Cell(31, 4.8, $it['total'] ? "$ " . number_format($it['total'], 0, ',', '.') : '', 1, 1, 'R');
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

// --- RECUADROS INSTITUCIONALES DE INFORMACIÓN ---
$pdf->SetDrawColor(160, 165, 175);
$pdf->SetLineWidth(0.25);
$y_cajas = $pdf->GetY() + 2.5;

// 1. RECUADRO DATOS PROVEEDOR
$pdf->SetFillColor(248, 250, 252);
$pdf->Rect(14, $y_cajas, 188, 12, 'DF');

$pdf->SetXY(16, $y_cajas + 1.5);
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(36, 4, $utf("DATOS PROVEEDOR:"), 0, 0, 'L');
$pdf->Cell(24, 4, $utf("RAZÓN SOCIAL:"), 0, 0, 'L');
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(124, 4, $safe_text("DISTRIBUIDORA Y SERVICIOS INTEGRALES SUR LTDA.", 78), 0, 1, 'L');

$pdf->SetXY(52, $y_cajas + 6);
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(12, 4, $utf("RUT:"), 0, 0, 'L');
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(44, 4, $utf("76.890.123-4"), 0, 0, 'L');

$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(20, 4, $utf("DIRECCIÓN:"), 0, 0, 'L');
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(72, 4, $safe_text("Av. Ignacio Carrera Pinto N° 450, Lebu", 50), 0, 1, 'L');

// 2. RECUADRO IMPUTACIÓN PRESUPUESTARIA
$y_caja2 = $y_cajas + 14;
$pdf->SetFillColor(248, 250, 252);
$pdf->Rect(14, $y_caja2, 188, 12, 'DF');

$pdf->SetXY(16, $y_caja2 + 1.5);
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(42, 4, $utf("IMPUTACIÓN PRESUPUESTARIA:"), 0, 0, 'L');

$pdf->Cell(18, 4, $utf("CUENTA N°:"), 0, 0, 'L');
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(38, 4, $utf("215.22.04.001"), 0, 0, 'L');

$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(24, 4, $utf("ÁREA GESTIÓN:"), 0, 0, 'L');
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(14, 4, $utf("01"), 0, 0, 'L');

$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(24, 4, $utf("CENTRO COSTO:"), 0, 0, 'L');
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(26, 4, $safe_text("102 - DIDECO", 18), 0, 1, 'L');

$pdf->SetXY(58, $y_caja2 + 6);
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(28, 4, $utf("COMPLEMENTARIA:"), 0, 0, 'L');
$pdf->SetFont('Arial', '', 6.5);
$pdf->Cell(100, 4, $safe_text("AUT. CC EXT: Dirección de Obras ($ 150.000 - APROBADO)", 75), 0, 1, 'L');

// 3. RECUADRO PLAN DE COMPRAS Y MODALIDADES
$y_caja3 = $y_caja2 + 14;
$pdf->SetFillColor(248, 250, 252);
$pdf->Rect(14, $y_caja3, 188, 17, 'DF');

$pdf->SetXY(16, $y_caja3 + 1.5);
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(36, 4, $utf("PLAN DE COMPRAS:"), 0, 0, 'L');
$pdf->Cell(18, 4, $utf("PROYECTO:"), 0, 0, 'L');
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(48, 4, $safe_text("FORTALECIMIENTO GESTIÓN COMUNITARIA", 30), 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(12, 4, $utf("ÍTEM:"), 0, 0, 'L');
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(70, 4, $safe_text("MATERIALES E INSUMOS OFICINA", 45), 0, 1, 'L');

$pdf->SetXY(16, $y_caja3 + 6.5);
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(36, 4, $utf("C. SUMINISTROS ID:"), 0, 0, 'L');
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(48, 4, $safe_text("CS-2026-089", 26), 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(30, 4, $utf("CONV. MARCO O°C:"), 0, 0, 'L');
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(54, 4, $safe_text("2345-789-CM26", 32), 0, 1, 'L');

$pdf->SetXY(16, $y_caja3 + 11.5);
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(36, 4, $utf("COMPRA ÁGIL ID:"), 0, 0, 'L');
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(48, 4, $safe_text("CA-8902", 26), 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(34, 4, $utf("DECRETO ALCALDICIO N°:"), 0, 0, 'L');
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(50, 4, $safe_text("DEC-ALC-758/2026", 28), 0, 1, 'L');

// --- CLÁUSULA 2: DESTINO DE LOS BIENES / SERVICIOS ---
$y_clausula2 = $y_caja3 + 19;
$pdf->SetXY(14, $y_clausula2);
$pdf->SetFont('Arial', 'B', 7.5);
$pdf->Cell(188, 4, $utf($clausula2_txt), 0, 1, 'L');

$pdf->SetXY(14, $y_clausula2 + 4.2);
$motivo_ejemplo = "Insumos y servicios requeridos para el normal desarrollo de los talleres comunitarios vecinales y operatividad de la oficina de atención ciudadana del Departamento durante el período 2026.";
$pdf->SetFont('Arial', '', 7.5);
$pdf->MultiCell(188, 3.5, $utf($motivo_ejemplo), 1, 'L');
$y_despues_motivo = $pdf->GetY();

// --- LÍNEAS DE BASE PARA LAS 3 FIRMAS DIGITALES (FIRMAGOB) ---
$y_firmas_line = max($firmas_linea_y, $y_despues_motivo + 20.0);
if ($y_firmas_line > 263.0) {
    $y_firmas_line = 263.0;
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
$pdf->Cell(58, 2.8, $utf("JEFATURA UNIDAD SOLICITANTE"), 0, 1, 'C');
$pdf->SetXY(14, $y_firmas_line + 3.8);
$pdf->SetFont('Arial', '', 5.5);
$pdf->Cell(58, 2.5, $utf("V°B° Requerimiento Técnico"), 0, 1, 'C');

$pdf->SetFont('Arial', 'B', 6);
$pdf->SetXY(79, $y_firmas_line + 1.2);
$pdf->Cell(58, 2.8, $utf("DIRECCIÓN DE ADM. Y FINANZAS"), 0, 1, 'C');
$pdf->SetXY(79, $y_firmas_line + 3.8);
$pdf->SetFont('Arial', '', 5.5);
$pdf->Cell(58, 2.5, $utf("Control e Imputación Presupuestaria"), 0, 1, 'C');

$pdf->SetFont('Arial', 'B', 6);
$pdf->SetXY(144, $y_firmas_line + 1.2);
$pdf->Cell(58, 2.8, $utf("ADMINISTRADOR MUNICIPAL"), 0, 1, 'C');
$pdf->SetXY(144, $y_firmas_line + 3.8);
$pdf->SetFont('Arial', '', 5.5);
$pdf->Cell(58, 2.5, $utf("Autorización Final del Gasto"), 0, 1, 'C');

// --- SIMULACIÓN DE ESTAMPAS DIGITALES FIRMAGOB (SI ESTÁ ACTIVA LA OPCIÓN) ---
if ($simular_estampas === 1) {
    $dibujar_estampa_simulada = function($x, $y_base, $titulo_rol, $nombre, $run, $cargo) use ($pdf, $utf) {
        $w = 58;
        $h = 17;
        $y_top = $y_base - $h - 0.5;

        // Fondo blanco y borde suave
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->Rect($x, $y_top, $w, $h, 'DF');

        // Barra superior azul institucional
        $pdf->SetFillColor(238, 242, 255);
        $pdf->Rect($x + 0.3, $y_top + 0.3, $w - 0.6, 3.2, 'F');
        
        $pdf->SetXY($x + 1, $y_top + 0.4);
        $pdf->SetFont('Arial', 'B', 5);
        $pdf->SetTextColor(29, 78, 216);
        $pdf->Cell($w - 2, 3, $utf("FIRMADO ELECTRÓNICAMENTE (FEA)"), 0, 1, 'L');

        // Líneas de metadatos
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('Arial', 'B', 4.8);
        $pdf->SetXY($x + 1.2, $y_top + 3.8);
        $pdf->Cell($w - 2.4, 2.6, $utf("Firmante: " . mb_strtoupper($nombre, 'UTF-8')), 0, 1, 'L');

        $pdf->SetFont('Arial', '', 4.6);
        $pdf->SetXY($x + 1.2, $y_top + 6.5);
        $pdf->Cell($w - 2.4, 2.6, $utf("RUN: " . $run . " | Cargo: " . $cargo), 0, 1, 'L');

        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetXY($x + 1.2, $y_top + 9.2);
        $pdf->Cell($w - 2.4, 2.5, $utf("Fecha: " . date('d/m/Y H:i:s') . " CLT"), 0, 1, 'L');

        $pdf->SetXY($x + 1.2, $y_top + 11.7);
        $pdf->Cell($w - 2.4, 2.5, $utf("Entidad: Ilustre Municipalidad de Lebu"), 0, 1, 'L');

        $pdf->SetFont('Arial', 'I', 4.2);
        $pdf->SetXY($x + 1.2, $y_top + 14.2);
        $pdf->Cell($w - 2.4, 2.2, $utf("Validez Legal: Ley N° 19.799 sobre Firma Electrónica"), 0, 1, 'L');

        $pdf->SetTextColor(0, 0, 0);
    };

    // Estampa 1: Jefatura
    $dibujar_estampa_simulada(14, $y_firmas_line, "V°B° TÉCNICO JEFATURA", "JUAN CARLOS ARRIAGADA", "17.439.829-1", "Jefe DIDECO");
    // Estampa 2: Presupuesto
    $dibujar_estampa_simulada(79, $y_firmas_line, "V°B° PRESUPUESTARIO", "MARÍA JOSÉ CONTRERAS", "15.987.654-3", "Encargada Presupuesto");
    // Estampa 3: Administrador
    $dibujar_estampa_simulada(144, $y_firmas_line, "AUTORIZACIÓN FINAL", "ROBERTO SANDOVAL MORA", "12.345.678-9", "Administrador Municipal");
}

// Pie de página legal configurable
$y_pie = min(272.0, $y_firmas_line + 7.5);
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
