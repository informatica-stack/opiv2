<?php
// pdf_helper.php - Generador Server-Side de Documentos Oficiales OPI en PDF (Modelo Oficial Lebu N° 758)
require_once __DIR__ . '/fpdf.php';

if (!class_exists('OPI_PDF')) {
    class OPI_PDF extends FPDF {
        protected function utf($str) {
            return iconv('UTF-8', 'windows-1252//TRANSLIT', (string)$str);
        }
    }
}

/**
 * Formatea una fecha de BD a formato oficial en español.
 */
function pdf_fecha_espanol($fecha_db) {
    $meses = [1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'];
    $ts = $fecha_db ? strtotime($fecha_db) : time();
    return date('j', $ts) . ' de ' . ($meses[(int)date('n', $ts)] ?? '') . ' de ' . date('Y', $ts);
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
        SELECT e.*, un.nombre as unidad, cc.nombre as centro_costo, cc.codigo_cuenta as centro_costo_cod,
               tc.codigo as tipo_compra_cod, tc.nombre as tipo_compra_nom, 
               prov.razon_social, prov.rut as rut_proveedor, prov.direccion as direccion_proveedor,
               u.nombre_completo as solicitante
        FROM expedientes e
        JOIN usuarios u ON e.usuario_creador_id = u.id
        JOIN unidades un ON e.unidad_origen_id = un.id
        LEFT JOIN centros_costo cc ON e.centro_costo_id = cc.id
        JOIN tipos_compra tc ON e.tipo_compra_id = tc.id
        LEFT JOIN proveedores prov ON e.proveedor_adjudicado_id = prov.id
        WHERE e.id = ?
    ");
    $stmt->execute([$expediente_id]);
    $exp = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$exp) throw new Exception("Expediente N° $expediente_id no encontrado.");

    // Reserva anticipada de Folio OPI Oficial si aún no ha sido asignado
    if (empty($exp['folio_opi'])) {
        $anio_act = date('Y');
        $intentos = 0;
        while ($intentos < 5) {
            try {
                $stmtMax = $pdo->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(folio_opi, '-', -1) AS UNSIGNED)) FROM expedientes WHERE folio_opi LIKE ?");
                $stmtMax->execute(["OPI-$anio_act-%"]);
                $max_num = $stmtMax->fetchColumn();
                
                $siguiente = $max_num ? $max_num + 1 : 1;
                $folio_opi = "OPI-" . $anio_act . "-" . str_pad($siguiente, 4, '0', STR_PAD_LEFT);

                $stmtUpd = $pdo->prepare("UPDATE expedientes SET folio_opi = ?, fecha_aprobacion_opi = IFNULL(fecha_aprobacion_opi, NOW()) WHERE id = ? AND (folio_opi IS NULL OR folio_opi = '')");
                $stmtUpd->execute([$folio_opi, $expediente_id]);
                $exp['folio_opi'] = $folio_opi;
                break;
            } catch (PDOException $e) {
                $intentos++;
                usleep(100000);
            }
        }
    }
    $folio_mostrar = !empty($exp['folio_opi']) ? $exp['folio_opi'] : ($exp['codigo_interno'] ?? "N° " . $exp['id']);

    // 2. Obtener ítems con imputación presupuestaria
    $stmtItems = $pdo->prepare("
        SELECT ei.*, cm.codigo as cuenta_codigo, cm.nombre as cuenta_nombre, ag.codigo as ag_codigo 
        FROM expedientes_items ei 
        LEFT JOIN presupuestos_asignados pa ON ei.presupuesto_asignado_id = pa.id 
        LEFT JOIN cuentas_maestras cm ON pa.cuenta_maestra_id = cm.id 
        LEFT JOIN areas_gestion ag ON pa.area_gestion_id = ag.id
        WHERE ei.expediente_id = ?
        ORDER BY ei.id ASC
    ");
    $stmtItems->execute([$expediente_id]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    // Cuentas y áreas de gestión agregadas
    $cuentas_usadas = array_unique(array_filter(array_column($items, 'cuenta_codigo')));
    $ags_usadas = array_unique(array_filter(array_column($items, 'ag_codigo')));
    $cuenta_str = !empty($cuentas_usadas) ? implode(", ", $cuentas_usadas) : '-';
    $ag_str = !empty($ags_usadas) ? implode(", ", $ags_usadas) : '01';
    // Mostrar únicamente el ID/código registrado del Centro de Costos
    $cc_str = !empty($exp['centro_costo_cod']) ? $exp['centro_costo_cod'] : (!empty($exp['centro_costo_id']) ? (string)$exp['centro_costo_id'] : '-');

    // 3. Crear directorio si no existe
    $anio = date('Y');
    $dir = __DIR__ . "/uploads/$anio/exp_$expediente_id/";
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }

    $nombre_archivo = "OPI_BASE_" . time() . ".pdf";
    $ruta_absoluta = $dir . $nombre_archivo;
    $ruta_relativa = "uploads/$anio/exp_$expediente_id/" . $nombre_archivo;

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

    // --- CONFIGURACIÓN DINÁMICA DE PLANTILLA (Base de Datos o Valores Predeterminados) ---
    global $config_sistema;
    if (!isset($config_sistema) || empty($config_sistema)) {
        try {
            $stmtCfg = $pdo->query("SELECT clave, valor FROM configuraciones_sistema");
            $config_sistema = $stmtCfg->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) {
            $config_sistema = [];
        }
    }

    $cfg_tamano_papel   = !empty($config_sistema['opi_tamano_papel']) ? strtoupper(trim($config_sistema['opi_tamano_papel'])) : 'OFICIO';
    $es_oficio          = ($cfg_tamano_papel === 'OFICIO');

    // Dimensiones en mm: Oficio Chileno (215.9 x 330.2 mm / 8.5 x 13") vs Carta (215.9 x 279.4 mm / 8.5 x 11")
    $dimensiones_papel  = $es_oficio ? [215.9, 330.2] : 'Letter';
    $alto_pagina_mm     = $es_oficio ? 330.2 : 279.4;
    $filas_min          = $es_oficio ? 6 : 4;
    $y_firmas_default   = $es_oficio ? 306.0 : 256.0;

    $cfg_titulo_doc     = !empty($config_sistema['opi_titulo_documento']) ? $config_sistema['opi_titulo_documento'] : 'ORDEN DE PEDIDO INTERNO';
    $cfg_clausula1_txt  = !empty($config_sistema['opi_clausula1_texto']) ? $config_sistema['opi_clausula1_texto'] : '1. Agradeceré a Usted, tenga a bien efectuar la adquisición de los siguientes bienes y/o servicios:';
    $cfg_clausula2_txt  = !empty($config_sistema['opi_clausula2_texto']) ? $config_sistema['opi_clausula2_texto'] : '2. Los presentes bienes/servicios serán destinados a:';
    $cfg_pie_legal_txt  = !empty($config_sistema['opi_pie_legal']) ? $config_sistema['opi_pie_legal'] : 'Documento Oficial emitido por el Sistema Institucional OPI - Validez legal bajo Ley N° 19.799 de Firma Electrónica';
    $cfg_firmas_linea_y = !empty($config_sistema['opi_firmas_linea_y']) ? floatval($config_sistema['opi_firmas_linea_y']) : $y_firmas_default;

    // Títulos y subtítulos configurables de los pies de firma (permite subtítulos vacíos)
    $cfg_firma1_tit     = !empty($config_sistema['opi_firma1_titulo']) ? $config_sistema['opi_firma1_titulo'] : 'JEFATURA UNIDAD SOLICITANTE';
    $cfg_firma1_sub     = array_key_exists('opi_firma1_subtitulo', $config_sistema) ? $config_sistema['opi_firma1_subtitulo'] : 'V°B° Requerimiento Técnico';
    $cfg_firma2_tit     = !empty($config_sistema['opi_firma2_titulo']) ? $config_sistema['opi_firma2_titulo'] : 'DIRECCIÓN DE ADM. Y FINANZAS';
    $cfg_firma2_sub     = array_key_exists('opi_firma2_subtitulo', $config_sistema) ? $config_sistema['opi_firma2_subtitulo'] : 'Control e Imputación Presupuestaria';
    $cfg_firma3_tit     = !empty($config_sistema['opi_firma3_titulo']) ? $config_sistema['opi_firma3_titulo'] : 'ADMINISTRADOR MUNICIPAL';
    $cfg_firma3_sub     = array_key_exists('opi_firma3_subtitulo', $config_sistema) ? $config_sistema['opi_firma3_subtitulo'] : 'Autorización Final del Gasto';

    // 4. Instanciar FPDF (Tamaño Oficio Chileno 215.9 x 330.2 mm o Carta 215.9 x 279.4 mm)
    $pdf = new FPDF('P', 'mm', $dimensiones_papel);
    $pdf->SetMargins(14, 10, 14);
    $pdf->SetAutoPageBreak(false);
    $pdf->AddPage();

    // --- ENCABEZADO INSTITUCIONAL OFICIAL (Modelo N° 758) ---
    if (file_exists(__DIR__ . '/logo.png')) {
        $pdf->Image(__DIR__ . '/logo.png', 14, 8, 22);
    }

    // Columna Izquierda: Institución y Unidad Solicitante
    $unidad_solicitante = mb_strtoupper($exp['unidad'] ?? 'DEPARTAMENTO DE ADQUISICIONES', 'UTF-8');
    $pdf->SetXY(38, 8);
    $pdf->SetFont('Arial', '', 7.5);
    $pdf->Cell(54, 3.5, $utf("República de Chile"), 0, 1, 'L');
    $pdf->SetX(38);
    $pdf->SetFont('Arial', 'B', 8.5);
    $pdf->Cell(54, 4, $utf("MUNICIPALIDAD DE LEBU"), 0, 1, 'L');
    $pdf->SetX(38);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(54, 3.5, $safe_text($unidad_solicitante, 36), 0, 1, 'L');

    // Columna Centro: Título Oficial Configurable (perfectamente centrado)
    $pdf->SetXY(92, 9);
    $pdf->SetFont('Arial', 'B', 10.5);
    $pdf->Cell(54, 5, $safe_text($cfg_titulo_doc, 32), 0, 1, 'C');

    // Columna Derecha: Folio y Fecha
    $pdf->SetXY(146, 8);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(56, 4.5, $utf("N° " . $folio_mostrar), 0, 1, 'R');
    $pdf->SetXY(146, 13);
    $pdf->SetFont('Arial', '', 8);
    $fecha_txt = "Lebu, " . pdf_fecha_espanol($exp['fecha_aprobacion_opi'] ?? $exp['created_at']);
    $pdf->Cell(56, 4, $utf($fecha_txt), 0, 1, 'R');

    // Línea separadora de cabecera
    $pdf->SetDrawColor(180, 180, 180);
    $pdf->SetLineWidth(0.3);
    $pdf->Line(14, 22.5, 202, 22.5);

    // --- SECCIÓN DE / A ---
    $pdf->SetXY(14, 24);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(8, 4, $utf("DE:"), 0, 0, 'L');
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(180, 4, $safe_text(mb_strtoupper($exp['unidad'] ?? '', 'UTF-8'), 95), 0, 1, 'L');

    $pdf->SetX(14);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(8, 4, $utf("A:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(180, 4, $utf("DIRECCIÓN DE ADMINISTRACIÓN Y FINANZAS - UNIDAD DE ADQUISICIONES"), 0, 1, 'L');

    // --- CLÁUSULA 1: TEXTO INTRODUCTORIO (Con soporte multilínea dinámico) ---
    $pdf->SetXY(14, 33);
    $pdf->SetFont('Arial', '', 7.5);
    $pdf->MultiCell(188, 3.5, $utf($cfg_clausula1_txt), 0, 'L');

    // --- TABLA PRINCIPAL DE PRODUCTOS / SERVICIOS ---
    // Posición Y dinámica post-cláusula 1 para evitar solapamientos
    $y_tabla = max(39.0, $pdf->GetY() + 1.5);
    $pdf->SetXY(14, $y_tabla);
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetFillColor(235, 238, 242);
    $pdf->SetFont('Arial', 'B', 7);
    
    // Encabezados de Columnas calibrados (Ancho total: 16 + 18 + 92 + 31 + 31 = 188mm)
    $pdf->Cell(16, 5, $utf("CANT."), 1, 0, 'C', true);
    $pdf->Cell(18, 5, $utf("UNIDAD"), 1, 0, 'C', true);
    $pdf->Cell(92, 5, $utf("DESCRIPCIÓN DE LOS BIENES Y/O SERVICIOS"), 1, 0, 'L', true);
    $pdf->Cell(31, 5, $utf("MONTO UNITARIO"), 1, 0, 'R', true);
    $pdf->Cell(31, 5, $utf("MONTO TOTAL"), 1, 1, 'R', true);

    $pdf->SetFont('Arial', '', 7.5);
    $total_items_acumulado = 0;
    $filas_min = 4;
    $fila_actual = 0;

    foreach ($items as $it) {
        $fila_actual++;
        $cant = floatval($it['cantidad']);
        $p_unit = floatval($it['precio_unitario']);
        $sub = $cant * $p_unit;
        $total_items_acumulado += $sub;

        $desc = (string)$it['descripcion'];
        if (!empty($it['id_producto_cm'])) {
            $desc .= " (ID CM: " . $it['id_producto_cm'] . ")";
        }

        // Medir extensión del texto para calcular altura dinámica de la fila sin desbordar a otras columnas
        $pdf->SetFont('Arial', '', 7.5);
        $texto_desc = " " . trim($desc);
        $ancho_txt = $pdf->GetStringWidth($texto_desc);
        $lineas = max(1, (int)ceil($ancho_txt / 88.0));
        $lineas = min(3, $lineas); // Máximo 3 líneas para preservar diseño de 1 carilla
        $line_h = 3.8;
        $row_h  = max(4.8, $lineas * $line_h);

        $x_fila = 14;
        $y_fila = $pdf->GetY();

        // Borde exterior de la fila completa
        $pdf->Rect($x_fila, $y_fila, 188, $row_h);

        // Cantidad
        $pdf->SetXY($x_fila, $y_fila);
        $pdf->Cell(16, $row_h, number_format($cant, 0, ',', '.'), 'R', 0, 'C');

        // Unidad
        $pdf->Cell(18, $row_h, $safe_text(mb_strtoupper($it['unidad_medida'] ?: 'UNID', 'UTF-8'), 8), 'R', 0, 'C');

        // Descripción: MultiCell exactamente en 92mm sin solapar precio
        $pdf->SetXY($x_fila + 34, $y_fila + max(0, ($row_h - ($lineas * $line_h)) / 2));
        $pdf->MultiCell(92, $line_h, $safe_text($desc, 130), 0, 'L');

        // Monto Unitario y Monto Total
        $pdf->SetXY($x_fila + 34 + 92, $y_fila);
        $pdf->Cell(31, $row_h, "$ " . number_format($p_unit, 0, ',', '.'), 'LR', 0, 'R');
        $pdf->Cell(31, $row_h, "$ " . number_format($sub, 0, ',', '.'), 'L', 0, 'R');

        // Posicionar cursor para la siguiente fila
        $pdf->SetXY($x_fila, $y_fila + $row_h);
    }

    // Rellenar filas vacías si hay menos de 4 para preservar la estructura visual del Modelo Lebu
    while ($fila_actual < $filas_min) {
        $fila_actual++;
        $pdf->SetX(14);
        $pdf->Cell(16, 4.8, "", 1, 0, 'C');
        $pdf->Cell(18, 4.8, "", 1, 0, 'C');
        $pdf->Cell(92, 4.8, "", 1, 0, 'L');
        $pdf->Cell(31, 4.8, "", 1, 0, 'R');
        $pdf->Cell(31, 4.8, "", 1, 1, 'R');
    }

    // --- CÁLCULO DE TOTALES ---
    // Perfectamente alineados con las columnas MONTO UNITARIO (31mm) y MONTO TOTAL (31mm)
    $monto_final = floatval($exp['monto_definitivo'] ?: ($total_items_acumulado ?: $exp['monto_estimado']));
    $iva = round($monto_final - ($monto_final / 1.19));
    $neto = $monto_final - $iva;

    $pdf->SetX(14);
    $pdf->SetFont('Arial', 'B', 7.5);
    $pdf->Cell(126, 4.5, "", 0, 0);
    $pdf->Cell(31, 4.5, $utf("MONTO NETO:"), 1, 0, 'R', true);
    $pdf->SetFont('Arial', '', 7.5);
    $pdf->Cell(31, 4.5, "$ " . number_format($neto, 0, ',', '.'), 1, 1, 'R');

    $pdf->SetX(14);
    $pdf->SetFont('Arial', 'B', 7.5);
    $pdf->Cell(126, 4.5, "", 0, 0);
    $pdf->Cell(31, 4.5, $utf("I.V.A. (19%):"), 1, 0, 'R', true);
    $pdf->SetFont('Arial', '', 7.5);
    $pdf->Cell(31, 4.5, "$ " . number_format($iva, 0, ',', '.'), 1, 1, 'R');

    $pdf->SetX(14);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(126, 5, "", 0, 0);
    $pdf->Cell(31, 5, $utf("TOTAL:"), 1, 0, 'R', true);
    $pdf->SetFont('Arial', 'B', 8.5);
    $pdf->Cell(31, 5, "$ " . number_format($monto_final, 0, ',', '.'), 1, 1, 'R');

    // --- RECUADROS INSTITUCIONALES DE INFORMACIÓN DE RESPALDO (Modelo Lebu N° 758) ---
    $pdf->SetDrawColor(160, 165, 175);
    $pdf->SetLineWidth(0.25);
    $y_cajas = $pdf->GetY() + 2.5;

    // 1. RECUADRO DATOS PROVEEDOR (Cuadrícula simétrica 188mm)
    $pdf->SetFillColor(248, 250, 252);
    $pdf->Rect(14, $y_cajas, 188, 11, 'DF');
    
    // Título lateral bloque 1
    $pdf->SetXY(16, $y_cajas + 1.2);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(34, 4, $utf("DATOS PROVEEDOR:"), 0, 0, 'L');

    // Fila 1: Razón Social (abarca todo el ancho de datos)
    $pdf->SetXY(52, $y_cajas + 1.2);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(25, 4, $utf("RAZÓN SOCIAL:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 7);
    $razon_prov = $exp['razon_social'] ?: 'PENDIENTE DE ADJUDICACIÓN';
    $pdf->Cell(125, 4, $safe_text($razon_prov, 82), 0, 1, 'L');

    // Fila 2: RUT y Dirección alineados en 2 columnas fijas (X=52 y X=126)
    $pdf->SetXY(52, $y_cajas + 5.8);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(25, 4, $utf("RUT:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(49, 4, $safe_text($exp['rut_proveedor'] ?: '-', 24), 0, 0, 'L');

    $pdf->SetXY(126, $y_cajas + 5.8);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(26, 4, $utf("DIRECCIÓN:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(50, 4, $safe_text($exp['direccion_proveedor'] ?: '-', 38), 0, 1, 'L');

    // 2. RECUADRO IMPUTACIÓN PRESUPUESTARIA (Cuadrícula simétrica 188mm)
    $y_caja2 = $y_cajas + 13.0;
    $pdf->SetFillColor(248, 250, 252);
    $pdf->Rect(14, $y_caja2, 188, 11, 'DF');

    // Título lateral bloque 2
    $pdf->SetXY(16, $y_caja2 + 1.2);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(34, 4, $utf("IMPUTACIÓN PRESUP.:"), 0, 0, 'L');

    // Fila 1: Cuenta N° y Centro de Costo (SOLO ID REGISTRADO)
    $pdf->SetXY(52, $y_caja2 + 1.2);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(25, 4, $utf("CUENTA N°:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(49, 4, $safe_text($cuenta_str, 24), 0, 0, 'L');

    $pdf->SetXY(126, $y_caja2 + 1.2);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(26, 4, $utf("CENTRO COSTO:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(50, 4, $safe_text($cc_str, 20), 0, 1, 'L');

    // Chequeo de autorizaciones inter-CC
    $aut_txt = "N° -";
    if (function_exists('obtener_autorizaciones_expediente')) {
        $auts = obtener_autorizaciones_expediente($pdo, $expediente_id);
        $ext_auts = array_filter($auts, function($a) { return $a['tipo_autorizacion'] === 'CENTRO_COSTO_EXTERNO'; });
        if (!empty($ext_auts)) {
            $parts = [];
            foreach ($ext_auts as $ea) {
                $parts[] = ($ea['cc_nombre'] ?: $ea['cc_codigo']) . " ($ " . number_format($ea['monto_imputado'], 0, ',', '.') . ")";
            }
            $aut_txt = "AUT. EXT: " . implode(" | ", $parts);
        }
    }

    // Fila 2: Área Gestión y Complementaria
    $pdf->SetXY(52, $y_caja2 + 5.8);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(25, 4, $utf("ÁREA GESTIÓN:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(49, 4, $safe_text($ag_str, 24), 0, 0, 'L');

    $pdf->SetXY(126, $y_caja2 + 5.8);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(26, 4, $utf("COMPLEMENTARIA:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 6.5);
    $pdf->Cell(50, 4, $safe_text($aut_txt, 35), 0, 1, 'L');

    // 3. RECUADRO PLAN DE COMPRAS Y MODALIDADES (Cuadrícula simétrica 188mm)
    $y_caja3 = $y_caja2 + 13.0;
    $pdf->SetFillColor(248, 250, 252);
    $pdf->Rect(14, $y_caja3, 188, 16, 'DF');

    // Título lateral bloque 3
    $pdf->SetXY(16, $y_caja3 + 1.2);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(34, 4, $utf("PLAN DE COMPRAS:"), 0, 0, 'L');

    // Fila 1: Proyecto (SOLO ID REGISTRADO) e Ítem N°
    $proy_id_str = !empty($exp['plan_compras_proyecto']) ? $exp['plan_compras_proyecto'] : '-';
    $pdf->SetXY(52, $y_caja3 + 1.2);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(25, 4, $utf("PROYECTO ID:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(49, 4, $safe_text($proy_id_str, 24), 0, 0, 'L');

    $pdf->SetXY(126, $y_caja3 + 1.2);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(26, 4, $utf("ÍTEM N°:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(50, 4, $safe_text($exp['plan_compras_item'] ?: '-', 20), 0, 1, 'L');

    // Fila 2: Contrato Suministros ID y Convenio Marco OC
    $pdf->SetXY(52, $y_caja3 + 5.8);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(25, 4, $utf("C. SUMINISTROS:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(49, 4, $safe_text($exp['id_contrato_suministro'] ?: '-', 24), 0, 0, 'L');

    $pdf->SetXY(126, $y_caja3 + 5.8);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(26, 4, $utf("CONV. MARCO OC:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 7);
    $conv_marco = $exp['conv_marco_oc'] ?: ($exp['orden_compra_numero'] ?: '-');
    $pdf->Cell(50, 4, $safe_text($conv_marco, 28), 0, 1, 'L');

    // Fila 3: Compra Ágil ID y Decreto Alcaldicio N°
    $pdf->SetXY(52, $y_caja3 + 10.4);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(25, 4, $utf("COMPRA ÁGIL ID:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(49, 4, $safe_text($exp['id_compra_agil'] ?: '-', 24), 0, 0, 'L');

    $pdf->SetXY(126, $y_caja3 + 10.4);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(26, 4, $utf("DECRETO ALC. N°:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(50, 4, $safe_text($exp['decreto_alcaldicio_numero'] ?: '-', 28), 0, 1, 'L');

    // --- CLÁUSULA 2: DESTINO DE LOS BIENES / SERVICIOS ---
    $y_clausula2 = $y_caja3 + 18.0;
    $pdf->SetXY(14, $y_clausula2);
    $pdf->SetFont('Arial', 'B', 7.5);
    $pdf->Cell(188, 4, $utf($cfg_clausula2_txt), 0, 1, 'L');

    // Recuadro y texto del motivo de compra
    $pdf->SetXY(14, $y_clausula2 + 4.2);
    $motivo = $exp['motivo_compra'] ?: ($exp['titulo_compra'] ?? '-');
    // Truncado protector máximo para asegurar que el texto no rebase la página
    if (mb_strlen($motivo, 'UTF-8') > 360) {
        $motivo = mb_substr($motivo, 0, 357, 'UTF-8') . '...';
    }
    $pdf->SetFont('Arial', '', 7.5);
    $pdf->MultiCell(188, 3.5, $utf($motivo), 1, 'L');
    $y_despues_motivo = $pdf->GetY();

    // --- LÍNEAS DE BASE PARA LAS 3 FIRMAS DIGITALES (FIRMAGOB) ---
    // Cálculo dinámico para evitar solapamientos si el motivo es extenso
    $tope_max_y = $alto_pagina_mm - 16.0;
    $y_firmas_line = max($cfg_firmas_linea_y, $y_despues_motivo + 20.0);
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

    // Subtítulos institucionales bajo cada línea de firma (orientación previa a FirmaGob)
    $pdf->SetFont('Arial', 'B', 6);
    $pdf->SetTextColor(70, 80, 95);

    $pdf->SetXY(14, $y_firmas_line + 1.2);
    $pdf->Cell(58, 2.8, $safe_text($cfg_firma1_tit, 42), 0, 1, 'C');
    $pdf->SetXY(14, $y_firmas_line + 3.8);
    $pdf->SetFont('Arial', '', 5.5);
    $pdf->Cell(58, 2.5, $safe_text($cfg_firma1_sub, 48), 0, 1, 'C');

    $pdf->SetFont('Arial', 'B', 6);
    $pdf->SetXY(79, $y_firmas_line + 1.2);
    $pdf->Cell(58, 2.8, $safe_text($cfg_firma2_tit, 42), 0, 1, 'C');
    $pdf->SetXY(79, $y_firmas_line + 3.8);
    $pdf->SetFont('Arial', '', 5.5);
    $pdf->Cell(58, 2.5, $safe_text($cfg_firma2_sub, 48), 0, 1, 'C');

    $pdf->SetFont('Arial', 'B', 6);
    $pdf->SetXY(144, $y_firmas_line + 1.2);
    $pdf->Cell(58, 2.8, $safe_text($cfg_firma3_tit, 42), 0, 1, 'C');
    $pdf->SetXY(144, $y_firmas_line + 3.8);
    $pdf->SetFont('Arial', '', 5.5);
    $pdf->Cell(58, 2.5, $safe_text($cfg_firma3_sub, 48), 0, 1, 'C');

    // Pie de página oficial
    $y_pie = min($alto_pagina_mm - 7.0, $y_firmas_line + 7.5);
    $pdf->SetXY(14, $y_pie);
    $pdf->SetFont('Arial', '', 6.5);
    $pdf->SetTextColor(110, 110, 110);
    $pdf->Cell(188, 3, $utf($cfg_pie_legal_txt), 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);

    // Guardar archivo binario
    $pdf->Output('F', $ruta_absoluta);

    // Registrar / Actualizar en expedientes_documentos como documento OPI único oficial
    registrar_o_actualizar_opi_documento($pdo, $expediente_id, $exp['usuario_creador_id'], $ruta_relativa, $nombre_archivo);

    return $ruta_relativa;
}

/**
 * Registra o actualiza el documento oficial único de la OPI en expedientes_documentos.
 * Garantiza que siempre exista una sola versión oficial de la OPI a lo largo del flujo de firmas.
 */
function registrar_o_actualizar_opi_documento($pdo, $expediente_id, $usuario_id, $ruta_relativa, $nombre_original) {
    $stmtCheck = $pdo->prepare("SELECT id, ruta_archivo FROM expedientes_documentos WHERE expediente_id = ? AND tipo_doc = 'OPI_FIRMADA_PDF' ORDER BY id ASC");
    $stmtCheck->execute([$expediente_id]);
    $existentes = $stmtCheck->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($existentes)) {
        $primer = $existentes[0];
        $antigua_ruta = $primer['ruta_archivo'];

        // Si la ruta anterior es distinta y el archivo físico existe, eliminar el borrador anterior
        if ($antigua_ruta && $antigua_ruta !== $ruta_relativa) {
            $archivo_viejo_abs = __DIR__ . '/' . $antigua_ruta;
            if (file_exists($archivo_viejo_abs) && is_file($archivo_viejo_abs)) {
                @unlink($archivo_viejo_abs);
            }
        }

        // Actualizar el registro único
        $pdo->prepare("UPDATE expedientes_documentos SET subido_por_id = ?, ruta_archivo = ?, nombre_original = ?, fecha_subida = NOW() WHERE id = ?")
            ->execute([$usuario_id, $ruta_relativa, $nombre_original, $primer['id']]);

        // Si existieran duplicados previos por historial antiguo, limpiarlos
        for ($i = 1; $i < count($existentes); $i++) {
            $dup_ruta = $existentes[$i]['ruta_archivo'];
            if ($dup_ruta && $dup_ruta !== $ruta_relativa) {
                $dup_abs = __DIR__ . '/' . $dup_ruta;
                if (file_exists($dup_abs) && is_file($dup_abs)) {
                    @unlink($dup_abs);
                }
            }
            $pdo->prepare("DELETE FROM expedientes_documentos WHERE id = ?")->execute([$existentes[$i]['id']]);
        }
    } else {
        $pdo->prepare("INSERT INTO expedientes_documentos (expediente_id, subido_por_id, tipo_doc, ruta_archivo, nombre_original) VALUES (?, ?, 'OPI_FIRMADA_PDF', ?, ?)")
            ->execute([$expediente_id, $usuario_id, $ruta_relativa, $nombre_original]);
    }
}


