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
    $cc_str = !empty($exp['centro_costo']) ? ($exp['centro_costo_cod'] ? $exp['centro_costo_cod'] . ' - ' : '') . $exp['centro_costo'] : '-';

    // 3. Crear directorio si no existe
    $anio = date('Y');
    $dir = __DIR__ . "/uploads/$anio/exp_$expediente_id/";
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }

    $nombre_archivo = "OPI_BASE_" . time() . ".pdf";
    $ruta_absoluta = $dir . $nombre_archivo;
    $ruta_relativa = "uploads/$anio/exp_$expediente_id/" . $nombre_archivo;

    // 4. Instanciar FPDF (Tamaño Carta: 215.9 x 279.4 mm, márgenes 14mm)
    $pdf = new FPDF('P', 'mm', 'Letter');
    $pdf->SetMargins(14, 10, 14);
    $pdf->SetAutoPageBreak(false);
    $pdf->AddPage();

    $utf = function($text) {
        return iconv('UTF-8', 'windows-1252//TRANSLIT', (string)$text);
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

    $cfg_titulo_doc     = !empty($config_sistema['opi_titulo_documento']) ? $config_sistema['opi_titulo_documento'] : 'ORDEN DE PEDIDO INTERNO';
    $cfg_clausula1_txt  = !empty($config_sistema['opi_clausula1_texto']) ? $config_sistema['opi_clausula1_texto'] : '1. Agradeceré a Usted, tenga a bien efectuar la adquisición de los siguientes bienes y/o servicios:';
    $cfg_clausula2_txt  = !empty($config_sistema['opi_clausula2_texto']) ? $config_sistema['opi_clausula2_texto'] : '2. Los presentes bienes/servicios serán destinados a:';
    $cfg_pie_legal_txt  = !empty($config_sistema['opi_pie_legal']) ? $config_sistema['opi_pie_legal'] : 'Documento Oficial emitido por el Sistema Institucional OPI - Validez legal bajo Ley N° 19.799 de Firma Electrónica';
    $cfg_firmas_linea_y = !empty($config_sistema['opi_firmas_linea_y']) ? floatval($config_sistema['opi_firmas_linea_y']) : 256.0;

    // --- ENCABEZADO INSTITUCIONAL OFICIAL (Modelo N° 758) ---
    if (file_exists(__DIR__ . '/logo.png')) {
        $pdf->Image(__DIR__ . '/logo.png', 14, 8, 22);
    }

    // Columna Izquierda: Institución y Unidad
    $pdf->SetXY(38, 8);
    $pdf->SetFont('Arial', '', 7.5);
    $pdf->Cell(60, 3.5, $utf("República de Chile"), 0, 1, 'L');
    $pdf->SetX(38);
    $pdf->SetFont('Arial', 'B', 8.5);
    $pdf->Cell(60, 4, $utf("MUNICIPALIDAD DE LEBU"), 0, 1, 'L');
    $pdf->SetX(38);
    $pdf->SetFont('Arial', 'B', 7.5);
    $pdf->Cell(60, 3.5, $utf(mb_strtoupper($exp['unidad'] ?? 'DEPARTAMENTO DE ADQUISICIONES')), 0, 1, 'L');

    // Columna Centro: Título Oficial
    $pdf->SetXY(98, 10);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(60, 5, $utf($cfg_titulo_doc), 0, 1, 'C');

    // Columna Derecha: Folio y Fecha
    $pdf->SetXY(158, 8);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(44, 4.5, $utf("N° " . $folio_mostrar), 0, 1, 'R');
    $pdf->SetXY(148, 13);
    $pdf->SetFont('Arial', '', 8);
    $fecha_txt = "Lebu, " . pdf_fecha_espanol($exp['fecha_aprobacion_opi'] ?? $exp['created_at']);
    $pdf->Cell(54, 4, $utf($fecha_txt), 0, 1, 'R');

    // Línea separadora
    $pdf->SetDrawColor(180, 180, 180);
    $pdf->Line(14, 23, 202, 23);

    // --- SECCIÓN DE / A ---
    $pdf->SetXY(14, 25);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(8, 4, $utf("DE:"), 0, 0, 'L');
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(180, 4, $utf(mb_strtoupper($exp['unidad'] ?? '')), 0, 1, 'L');

    $pdf->SetX(14);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(8, 4, $utf("A:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(180, 4, $utf("DIRECCIÓN DE ADMINISTRACIÓN Y FINANZAS - UNIDAD DE ADQUISICIONES"), 0, 1, 'L');

    // --- CLÁUSULA 1: TEXTO INTRODUCTORIO ---
    $pdf->SetXY(14, 34);
    $pdf->SetFont('Arial', '', 7.5);
    $pdf->Cell(188, 4, $utf($cfg_clausula1_txt), 0, 1, 'L');

    // --- TABLA PRINCIPAL DE PRODUCTOS / SERVICIOS ---
    $y_tabla = 39;
    $pdf->SetXY(14, $y_tabla);
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetFillColor(235, 238, 242);
    $pdf->SetFont('Arial', 'B', 7);
    
    // Encabezados de Columnas (Ancho total: 14 + 18 + 96 + 30 + 30 = 188mm)
    $pdf->Cell(14, 5, $utf("CANT."), 1, 0, 'C', true);
    $pdf->Cell(18, 5, $utf("UNIDAD"), 1, 0, 'C', true);
    $pdf->Cell(96, 5, $utf("DESCRIPCIÓN DE LOS BIENES Y/O SERVICIOS"), 1, 0, 'L', true);
    $pdf->Cell(30, 5, $utf("MONTO UNITARIO"), 1, 0, 'R', true);
    $pdf->Cell(30, 5, $utf("MONTO TOTAL"), 1, 1, 'R', true);

    $pdf->SetFont('Arial', '', 7.5);
    $total_items_acumulado = 0;
    $filas_max = 4;
    $fila_actual = 0;

    foreach ($items as $it) {
        $fila_actual++;
        $cant = floatval($it['cantidad']);
        $p_unit = floatval($it['precio_unitario']);
        $sub = $cant * $p_unit;
        $total_items_acumulado += $sub;

        $desc = $it['descripcion'];
        if (!empty($it['id_producto_cm'])) {
            $desc .= " (ID CM: " . $it['id_producto_cm'] . ")";
        }
        if (strlen($desc) > 65) {
            $desc = substr($desc, 0, 62) . '...';
        }

        $pdf->SetX(14);
        $pdf->Cell(14, 4.8, number_format($cant, 0, ',', '.'), 1, 0, 'C');
        $pdf->Cell(18, 4.8, $utf(mb_strtoupper($it['unidad_medida'] ?: 'UNID')), 1, 0, 'C');
        $pdf->Cell(96, 4.8, $utf(" " . $desc), 1, 0, 'L');
        $pdf->Cell(30, 4.8, "$ " . number_format($p_unit, 0, ',', '.'), 1, 0, 'R');
        $pdf->Cell(30, 4.8, "$ " . number_format($sub, 0, ',', '.'), 1, 1, 'R');
    }

    // Rellenar filas vacías para mantener estructura rígida de Modelo N° 758
    while ($fila_actual < $filas_max) {
        $fila_actual++;
        $pdf->SetX(14);
        $pdf->Cell(14, 4.8, "", 1, 0, 'C');
        $pdf->Cell(18, 4.8, "", 1, 0, 'C');
        $pdf->Cell(96, 4.8, "", 1, 0, 'L');
        $pdf->Cell(30, 4.8, "", 1, 0, 'R');
        $pdf->Cell(30, 4.8, "", 1, 1, 'R');
    }

    // --- CÁLCULO DE TOTALES ---
    $monto_final = floatval($exp['monto_definitivo'] ?: ($total_items_acumulado ?: $exp['monto_estimado']));
    $iva = round($monto_final - ($monto_final / 1.19));
    $neto = $monto_final - $iva;

    $pdf->SetX(14);
    $pdf->SetFont('Arial', 'B', 7.5);
    $pdf->Cell(128, 4.5, "", 0, 0);
    $pdf->Cell(30, 4.5, $utf("MONTO NETO:"), 1, 0, 'R', true);
    $pdf->SetFont('Arial', '', 7.5);
    $pdf->Cell(30, 4.5, "$ " . number_format($neto, 0, ',', '.'), 1, 1, 'R');

    $pdf->SetX(14);
    $pdf->SetFont('Arial', 'B', 7.5);
    $pdf->Cell(128, 4.5, "", 0, 0);
    $pdf->Cell(30, 4.5, $utf("I.V.A. (19%):"), 1, 0, 'R', true);
    $pdf->SetFont('Arial', '', 7.5);
    $pdf->Cell(30, 4.5, "$ " . number_format($iva, 0, ',', '.'), 1, 1, 'R');

    $pdf->SetX(14);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(128, 5, "", 0, 0);
    $pdf->Cell(30, 5, $utf("TOTAL:"), 1, 0, 'R', true);
    $pdf->SetFont('Arial', 'B', 8.5);
    $pdf->Cell(30, 5, "$ " . number_format($monto_final, 0, ',', '.'), 1, 1, 'R');

    // --- RECUADROS INSTITUCIONALES DE INFORMACIÓN DE RESPALDO (Modelo Lebu N° 758) ---
    $y_cajas = $pdf->GetY() + 3;

    // 1. RECUADRO DATOS PROVEEDOR
    $pdf->SetXY(14, $y_cajas);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetFillColor(245, 247, 250);
    $pdf->Rect(14, $y_cajas, 188, 12);
    
    $pdf->SetXY(16, $y_cajas + 1.5);
    $pdf->Cell(38, 4, $utf("DATOS PROVEEDOR:"), 0, 0, 'L');
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(25, 4, $utf("RAZÓN SOCIAL:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(120, 4, $utf($exp['razon_social'] ?: 'PENDIENTE DE ADJUDICACIÓN'), 0, 1, 'L');

    $pdf->SetXY(54, $y_cajas + 6);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(12, 4, $utf("RUT:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(45, 4, $utf($exp['rut_proveedor'] ?: '-'), 0, 0, 'L');

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(20, 4, $utf("DIRECCIÓN:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(55, 4, $utf($exp['direccion_proveedor'] ?: '-'), 0, 1, 'L');

    // 2. RECUADRO IMPUTACIÓN PRESUPUESTARIA
    $y_caja2 = $y_cajas + 14;
    $pdf->SetXY(14, $y_caja2);
    $pdf->Rect(14, $y_caja2, 188, 12);

    $pdf->SetXY(16, $y_caja2 + 1.5);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(38, 4, $utf("IMPUTACIÓN PRESUPUESTARIA:"), 0, 0, 'L');
    
    $pdf->Cell(22, 4, $utf("CUENTA N°:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(40, 4, $utf($cuenta_str), 0, 0, 'L');

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(26, 4, $utf("ÁREA GESTIÓN:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(15, 4, $utf($ag_str), 0, 0, 'L');

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(24, 4, $utf("CENTRO COSTO:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(22, 4, $utf($cc_str), 0, 1, 'L');

    // Chequeo de autorizaciones inter-CC
    $aut_txt = "N° -";
    if (function_exists('obtener_autorizaciones_expediente')) {
        $auts = obtener_autorizaciones_expediente($pdo, $expediente_id);
        $ext_auts = array_filter($auts, function($a) { return $a['tipo_autorizacion'] === 'CENTRO_COSTO_EXTERNO'; });
        if (!empty($ext_auts)) {
            $parts = [];
            foreach ($ext_auts as $ea) {
                $parts[] = ($ea['cc_nombre'] ?: $ea['cc_codigo']) . " ($ " . number_format($ea['monto_imputado'], 0, ',', '.') . " - " . $ea['estado'] . ")";
            }
            $aut_txt = "AUT. CC EXT: " . implode(" | ", $parts);
        }
    }

    $pdf->SetXY(54, $y_caja2 + 6);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(32, 4, $utf("COMPLEMENTARIA:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 6.5);
    $pdf->Cell(95, 4, $utf(substr($aut_txt, 0, 75)), 0, 1, 'L');

    // 3. RECUADRO PLAN DE COMPRAS Y MODALIDADES
    $y_caja3 = $y_caja2 + 14;
    $pdf->SetXY(14, $y_caja3);
    $pdf->Rect(14, $y_caja3, 188, 17);

    $pdf->SetXY(16, $y_caja3 + 1.5);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(38, 4, $utf("PLAN DE COMPRAS:"), 0, 0, 'L');
    $pdf->Cell(20, 4, $utf("PROYECTO:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(50, 4, $utf($exp['plan_compras_proyecto'] ?: '-'), 0, 0, 'L');
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(15, 4, $utf("ÍTEM:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(45, 4, $utf($exp['plan_compras_item'] ?: '-'), 0, 1, 'L');

    $pdf->SetXY(16, $y_caja3 + 6.5);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(38, 4, $utf("C. SUMINISTROS ID:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(50, 4, $utf($exp['id_contrato_suministro'] ?: '-'), 0, 0, 'L');
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(30, 4, $utf("CONV. MARCO O°C:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 7);
    $conv_marco = $exp['conv_marco_oc'] ?: ($exp['orden_compra_numero'] ?: '-');
    $pdf->Cell(45, 4, $utf($conv_marco), 0, 1, 'L');

    $pdf->SetXY(16, $y_caja3 + 11.5);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(38, 4, $utf("COMPRA ÁGIL ID:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(50, 4, $utf($exp['id_compra_agil'] ?: '-'), 0, 0, 'L');
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(34, 4, $utf("DECRETO ALCALDICIO N°:"), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(40, 4, $utf($exp['decreto_alcaldicio_numero'] ?: '-'), 0, 1, 'L');

    // --- CLÁUSULA 2: DESTINO DE LOS BIENES / SERVICIOS ---
    $y_clausula2 = $y_caja3 + 19;
    $pdf->SetXY(14, $y_clausula2);
    $pdf->SetFont('Arial', 'B', 7.5);
    $pdf->Cell(188, 4, $utf($cfg_clausula2_txt), 0, 1, 'L');
    $pdf->SetFont('Arial', '', 7.5);
    $pdf->SetXY(14, $y_clausula2 + 4);
    $motivo = $exp['motivo_compra'] ?: ($exp['titulo_compra'] ?? '-');
    $pdf->MultiCell(188, 3.5, $utf($motivo), 1, 'L');

    // --- LÍNEAS DE BASE PARA LAS 3 FIRMAS DIGITALES (FIRMAGOB) ---
    // Diseño limpio: Sin cajas cerradas ni texto redundante interno.
    // La estampa oficial de FirmaGob se estampa directamente sobre cada línea base horizontal.
    $y_firmas_line = $cfg_firmas_linea_y;
    $pdf->SetDrawColor(120, 120, 120);
    $pdf->SetLineWidth(0.3);

    // Línea 1: Jefatura Unidad Solicitante (Izquierda: X=14 a 72, Ancho=58mm)
    $pdf->Line(14, $y_firmas_line, 72, $y_firmas_line);

    // Línea 2: Dirección de Finanzas / Presupuesto (Centro: X=79 a 137, Ancho=58mm)
    $pdf->Line(79, $y_firmas_line, 137, $y_firmas_line);

    // Línea 3: Administrador Municipal (Derecha: X=144 a 202, Ancho=58mm)
    $pdf->Line(144, $y_firmas_line, 202, $y_firmas_line);

    // Pie de página oficial
    $pdf->SetXY(14, $y_firmas_line + 4);
    $pdf->SetFont('Arial', '', 6.5);
    $pdf->SetTextColor(110, 110, 110);
    $pdf->Cell(188, 3, $utf($cfg_pie_legal_txt), 0, 1, 'C');

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


