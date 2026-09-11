<?php
// pdf_helper.php - Generador Server-Side de Documentos Oficiales OPI en PDF (basado en FPDF)
require_once __DIR__ . '/fpdf.php';

class OPI_PDF extends FPDF {
    protected function utf($str) {
        return iconv('UTF-8', 'windows-1252//TRANSLIT', (string)$str);
    }
}

/**
 * Genera y almacena el archivo inmutable OPI_BASE.pdf en el servidor.
 *
 * @param PDO $pdo Conexión PDO a base de datos.
 * @param int $expediente_id ID del expediente/OPI.
 * @return string Ruta relativa al archivo PDF generado.
 * @throws Exception
 */
function generar_pdf_base_opi($pdo, $expediente_id) {
    // 1. Obtener datos del expediente
    $stmt = $pdo->prepare("
        SELECT e.*, un.nombre as unidad, cc.nombre as centro_costo, tc.codigo as tipo_compra_cod, tc.nombre as tipo_compra_nom, 
               prov.razon_social, prov.rut as rut_proveedor, prov.direccion as direccion_proveedor,
               u.nombre_completo as solicitante
        FROM expedientes e
        JOIN usuarios u ON e.usuario_creador_id = u.id
        JOIN unidades un ON e.unidad_origen_id = un.id
        JOIN centros_costo cc ON e.centro_costo_id = cc.id
        JOIN tipos_compra tc ON e.tipo_compra_id = tc.id
        LEFT JOIN proveedores prov ON e.proveedor_adjudicado_id = prov.id
        WHERE e.id = ?
    ");
    $stmt->execute([$expediente_id]);
    $exp = $stmt->fetch();
    if (!$exp) throw new Exception("Expediente N° $expediente_id no encontrado.");

    // 2. Obtener ítems
    $stmtItems = $pdo->prepare("
        SELECT ei.*, cm.codigo as cuenta_codigo, ag.codigo as ag_codigo 
        FROM expedientes_items ei 
        LEFT JOIN presupuestos_asignados pa ON ei.presupuesto_asignado_id = pa.id 
        LEFT JOIN cuentas_maestras cm ON pa.cuenta_maestra_id = cm.id 
        LEFT JOIN areas_gestion ag ON pa.area_gestion_id = ag.id
        WHERE ei.expediente_id = ?
    ");
    $stmtItems->execute([$expediente_id]);
    $items = $stmtItems->fetchAll();

    // 3. Crear directorio si no existe
    $anio = date('Y');
    $dir = __DIR__ . "/uploads/$anio/exp_$expediente_id/";
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }

    $nombre_archivo = "OPI_BASE_" . time() . ".pdf";
    $ruta_absoluta = $dir . $nombre_archivo;
    $ruta_relativa = "uploads/$anio/exp_$expediente_id/" . $nombre_archivo;

    // 4. Instanciar FPDF
    $pdf = new FPDF('P', 'mm', 'Letter');
    $pdf->SetMargins(15, 12, 15);
    $pdf->SetAutoPageBreak(true, 18);
    $pdf->AddPage();

    $utf = function($text) {
        return iconv('UTF-8', 'windows-1252//TRANSLIT', (string)$text);
    };

    // --- ENCABEZADO INSTITUCIONAL ---
    if (file_exists(__DIR__ . '/logo.png')) {
        $pdf->Image(__DIR__ . '/logo.png', 15, 10, 28);
    }

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY(46, 12);
    $pdf->Cell(95, 5, $utf("ILUSTRE MUNICIPALIDAD"), 0, 1, 'L');
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetX(46);
    $pdf->Cell(95, 4, $utf("DEPARTAMENTO DE ADQUISICIONES"), 0, 1, 'L');
    $pdf->SetX(46);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(95, 4, $utf("Sistema de Órdenes de Pedido Interno (OPI)"), 0, 1, 'L');

    // Recuadro Superior Derecho: Título y Folio
    $pdf->SetXY(145, 10);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetFillColor(240, 243, 246);
    $pdf->Cell(56, 7, $utf("ORDEN DE PEDIDO"), 1, 1, 'C', true);

    $pdf->SetXY(145, 17);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(56, 5, $utf("Ref: " . $exp['codigo_interno']), 'LR', 1, 'C');

    $pdf->SetXY(145, 22);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(56, 5, $utf("Fecha: " . date('d/m/Y')), 'LRB', 1, 'C');

    $pdf->Ln(6);

    // --- DATOS DEL PROVEEDOR Y SOLICITUD ---
    $y_bloque = $pdf->GetY() + 2;

    // Caja Proveedor (Izquierda)
    $pdf->SetXY(15, $y_bloque);
    $pdf->SetFont('Arial', 'B', 8.5);
    $pdf->SetFillColor(235, 238, 242);
    $pdf->Cell(90, 5, $utf(" DATOS DEL PROVEEDOR ADJUDICADO"), 1, 1, 'L', true);

    $pdf->SetFont('Arial', '', 8);
    $pdf->SetX(15);
    $pdf->Cell(90, 4.5, $utf(" Razón Social: " . ($exp['razon_social'] ?: 'Pendiente / Cotización')), 'LR', 1);
    $pdf->SetX(15);
    $pdf->Cell(90, 4.5, $utf(" RUT: " . ($exp['rut_proveedor'] ?: 'N/A')), 'LR', 1);
    $pdf->SetX(15);
    $pdf->Cell(90, 4.5, $utf(" Dirección: " . ($exp['direccion_proveedor'] ?: 'N/A')), 'LRB', 1);

    // Caja Solicitud (Derecha)
    $pdf->SetXY(110, $y_bloque);
    $pdf->SetFont('Arial', 'B', 8.5);
    $pdf->Cell(91, 5, $utf(" DATOS DE LA SOLICITUD"), 1, 1, 'L', true);

    $pdf->SetFont('Arial', '', 8);
    $pdf->SetX(110);
    $pdf->Cell(91, 4.5, $utf(" Unidad Solicitante: " . $exp['unidad']), 'LR', 1);
    $pdf->SetX(110);
    $pdf->Cell(91, 4.5, $utf(" Solicitante: " . $exp['solicitante']), 'LR', 1);
    $pdf->SetX(110);
    $pdf->Cell(91, 4.5, $utf(" Modalidad: " . $exp['tipo_compra_nom']), 'LRB', 1);

    $pdf->Ln(4);

    // --- DESTINO / MOTIVO ---
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(186, 4.5, $utf("Destino de los bienes / Justificación del gasto:"), 0, 1, 'L');
    $pdf->SetFont('Arial', '', 8);
    $pdf->MultiCell(186, 4, $utf($exp['motivo_compra'] ?: $exp['titulo_compra']), 1, 'L');

    $pdf->Ln(3);

    // --- TABLA DE ÍTEMS ---
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetFillColor(225, 230, 238);
    $pdf->Cell(86, 6, $utf("DESCRIPCIÓN DE BIENES / SERVICIOS"), 1, 0, 'L', true);
    $pdf->Cell(20, 6, $utf("UNIDAD"), 1, 0, 'C', true);
    $pdf->Cell(18, 6, $utf("CANT."), 1, 0, 'C', true);
    $pdf->Cell(30, 6, $utf("P. UNIT. NETO"), 1, 0, 'R', true);
    $pdf->Cell(32, 6, $utf("TOTAL NETO"), 1, 1, 'R', true);

    $pdf->SetFont('Arial', '', 8);
    $total_neto = 0;

    foreach ($items as $item) {
        $cant = floatval($item['cantidad']);
        $p_unit_neto = round(floatval($item['precio_unitario']) / 1.19); // Ajuste neto si incluye IVA
        $subtotal = $cant * $p_unit_neto;
        $total_neto += $subtotal;

        $desc = strlen($item['descripcion']) > 55 ? substr($item['descripcion'], 0, 52) . '...' : $item['descripcion'];

        $pdf->Cell(86, 5.5, $utf(" " . $desc), 1, 0, 'L');
        $pdf->Cell(20, 5.5, $utf($item['unidad_medida'] ?: 'UNID'), 1, 0, 'C');
        $pdf->Cell(18, 5.5, number_format($cant, 0, ',', '.'), 1, 0, 'C');
        $pdf->Cell(30, 5.5, "$ " . number_format($p_unit_neto, 0, ',', '.'), 1, 0, 'R');
        $pdf->Cell(32, 5.5, "$ " . number_format($subtotal, 0, ',', '.'), 1, 1, 'R');
    }

    $monto_def = floatval($exp['monto_definitivo'] ?: $exp['monto_estimado']);
    $total_bruto = $monto_def > 0 ? $monto_def : round($total_neto * 1.19);
    $iva = round($total_bruto - ($total_bruto / 1.19));
    $neto_final = $total_bruto - $iva;

    // Totales
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(154, 5, $utf("SUBTOTAL NETO"), 1, 0, 'R');
    $pdf->Cell(32, 5, "$ " . number_format($neto_final, 0, ',', '.'), 1, 1, 'R');

    $pdf->Cell(154, 5, $utf("I.V.A. (19%)"), 1, 0, 'R');
    $pdf->Cell(32, 5, "$ " . number_format($iva, 0, ',', '.'), 1, 1, 'R');

    $pdf->SetFillColor(240, 243, 246);
    $pdf->Cell(154, 6, $utf("TOTAL A PAGAR (CLP)"), 1, 0, 'R', true);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(32, 6, "$ " . number_format($total_bruto, 0, ',', '.'), 1, 1, 'R', true);

    // --- RECUADROS INFERIORES DE LAS 3 FIRMAS (Pre-diseñados limpios) ---
    // Posicionamiento en la parte inferior para que FirmaGob inserte sus estampas
    $y_firmas = 215;
    $pdf->SetXY(15, $y_firmas);

    // Cuadrante 1: Jefatura (Izquierda)
    $pdf->SetFont('Arial', 'B', 7.5);
    $pdf->SetFillColor(245, 247, 250);
    $pdf->Rect(15, $y_firmas, 58, 40);
    $pdf->SetXY(15, $y_firmas + 1);
    $pdf->Cell(58, 4, $utf("1. V°B° TÉCNICO JEFATURA"), 0, 1, 'C');
    $pdf->SetFont('Arial', 'I', 7);
    $pdf->SetXY(15, $y_firmas + 34);
    $pdf->Cell(58, 4, $utf("(Estampa Firma Digital FirmaGob)"), 0, 1, 'C');

    // Cuadrante 2: Presupuesto (Centro)
    $pdf->Rect(79, $y_firmas, 58, 40);
    $pdf->SetXY(79, $y_firmas + 1);
    $pdf->SetFont('Arial', 'B', 7.5);
    $pdf->Cell(58, 4, $utf("2. V°B° PRESUPUESTARIO"), 0, 1, 'C');
    $pdf->SetFont('Arial', 'I', 7);
    $pdf->SetXY(79, $y_firmas + 34);
    $pdf->Cell(58, 4, $utf("(Estampa Firma Digital FirmaGob)"), 0, 1, 'C');

    // Cuadrante 3: Administrador Municipal (Derecha)
    $pdf->Rect(143, $y_firmas, 58, 40);
    $pdf->SetXY(143, $y_firmas + 1);
    $pdf->SetFont('Arial', 'B', 7.5);
    $pdf->Cell(58, 4, $utf("3. AUTORIZACIÓN FINAL"), 0, 1, 'C');
    $pdf->SetFont('Arial', 'I', 7);
    $pdf->SetXY(143, $y_firmas + 34);
    $pdf->Cell(58, 4, $utf("(Estampa Firma Digital FirmaGob)"), 0, 1, 'C');

    // Pie de página oficial
    $pdf->SetXY(15, 260);
    $pdf->SetFont('Arial', '', 6.5);
    $pdf->SetTextColor(120, 120, 120);
    $pdf->Cell(186, 3, $utf("Documento Oficial emitido por el Sistema Institucional OPI - Validez legal bajo Ley N° 19.799"), 0, 1, 'C');

    // Guardar archivo binario
    $pdf->Output('F', $ruta_absoluta);

    // Registrar en expedientes_documentos
    $pdo->prepare("INSERT INTO expedientes_documentos (expediente_id, subido_por_id, tipo_doc, ruta_archivo, nombre_original) VALUES (?, ?, 'OPI_FIRMADA_PDF', ?, ?)")
        ->execute([$expediente_id, $exp['usuario_creador_id'], $ruta_relativa, $nombre_archivo]);

    return $ruta_relativa;
}
