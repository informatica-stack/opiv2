<?php
// cdp.php - Generador de Certificado de Disponibilidad Presupuestaria (Norma CGR IN4/2026)
require_once __DIR__ . '/config.php';

// 1. SEGURIDAD Y SESIÓN
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { die("Acceso denegado."); }

$rol = $_SESSION['user_rol'] ?? '';
if ($rol !== 'PRESUPUESTO' && $rol !== 'ADMIN_MUNICIPAL' && $rol !== 'FINANZAS' && $rol !== 'SYSADMIN') {
    die("Acceso Denegado. Solo personal de Finanzas/Presupuesto puede generar este documento.");
}

// 1b. OBTENER DATOS DEL FIRMANTE ACTIVO
$stmtFirmante = $pdo->prepare("SELECT nombre_completo, cargo FROM usuarios WHERE id = ?");
$stmtFirmante->execute([$_SESSION['user_id']]);
$firmante = $stmtFirmante->fetch();
$firmante_nombre = $firmante['nombre_completo'] ?? $_SESSION['user_name'];
$firmante_cargo = $firmante['cargo'] ?? 'Visador Técnico de Finanzas';

$id = $_GET['id'] ?? null;
if (!$id) die("No se especificó un requerimiento válido.");

// 2. OBTENER DATOS DEL EXPEDIENTE
$stmt = $pdo->prepare("
    SELECT e.*, u.nombre_completo as solicitante 
    FROM expedientes e 
    JOIN usuarios u ON e.usuario_creador_id = u.id 
    WHERE e.id = ?
");
$stmt->execute([$id]);
$expediente = $stmt->fetch();
if (!$expediente) die("Expediente no encontrado.");

$es_fase_inicial = ($expediente['estado_actual'] === 'EN_VALIDACION_PRESUPUESTARIA');

// 3. OBTENER ITEMS Y CALCULAR TOTALES POR CUENTA (Ajuste Micro)
$stmtItems = $pdo->prepare("
    SELECT ei.*, cm.codigo as cuenta_codigo 
    FROM expedientes_items ei
    JOIN presupuestos_asignados pa ON ei.presupuesto_asignado_id = pa.id
    JOIN cuentas_maestras cm ON pa.cuenta_maestra_id = cm.id
    WHERE ei.expediente_id = ?
");
$stmtItems->execute([$id]);
$items = $stmtItems->fetchAll();

$totales_por_cuenta = [];
foreach($items as $it) {
    $costo_linea = $it['cantidad'] * $it['precio_unitario'];
    
    // Si estamos en fase final y hubo diferencia en la adjudicación, calculamos el valor real
    if (!$es_fase_inicial && $expediente['monto_definitivo'] > 0) {
        $total_original = $expediente['monto_estimado'];
        $total_nuevo = $expediente['monto_definitivo'];
        $diferencia = $total_nuevo - $total_original;
        $proporcion = ($total_original > 0) ? ($costo_linea / $total_original) : 0;
        $ajuste_requerido = $diferencia * $proporcion;
        $costo_mostrar = $costo_linea + $ajuste_requerido;
    } else {
        $costo_mostrar = $costo_linea;
    }

    $cta = $it['cuenta_codigo'];
    if(!isset($totales_por_cuenta[$cta])) {
        $totales_por_cuenta[$cta] = 0;
    }
    $totales_por_cuenta[$cta] += $costo_mostrar;
}

// 4. GENERAR TEXTO DE INFORMACIÓN ADICIONAL AUTOMÁTICA
$info_adicional = "Respaldo Interno: Folio OPI " . ($expediente['folio_opi'] ?? 'En Trámite') . "\n";
if(!empty($expediente['id_compra_agil'])) $info_adicional .= "ID Compra Ágil: " . $expediente['id_compra_agil'] . "\n";
if(!empty($expediente['id_licitacion'])) $info_adicional .= "ID Licitación MP: " . $expediente['id_licitacion'] . "\n";
if(!empty($expediente['id_contrato_suministro'])) $info_adicional .= "Suministro: " . $expediente['id_contrato_suministro'] . "\n";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CDP - <?= $expediente['codigo_interno'] ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="no-print fixed top-0 left-0 w-full bg-slate-800 text-white p-4 shadow-md flex justify-between items-center z-50">
        <div>
            <h1 class="font-bold text-lg font-sans">Generador de CDP Oficial</h1>
            <p class="text-xs text-slate-300 font-sans">Certificado para impresión o exportación a PDF.</p>
        </div>
        <div class="flex gap-3">
            <button onclick="window.close()" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 rounded-lg text-sm font-bold font-sans transition">Cerrar</button>
            <button onclick="window.print()" class="px-6 py-2 bg-blue-600 hover:bg-blue-500 rounded-lg text-sm font-bold font-sans shadow-lg transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Imprimir / Guardar PDF
            </button>
        </div>
    </div>

    <div class="a4-wrapper mt-24">
        
        <div class="text-center mb-10">
            <h2 class="text-[14pt] font-bold underline mb-2">CERTIFICADO DE DISPONIBILIDAD PRESUPUESTARIA</h2>
            <div class="flex justify-center items-center gap-2 text-[12pt] font-bold">
                N° <input type="text" class="input-cgr w-32 text-center font-bold">
            </div>
        </div>

        <div class="space-y-2 mb-8 text-[12pt]">
            <p><b>NOMBRE DE LA ENTIDAD (Servicio):</b> MUNICIPALIDAD DE LEBU</p>
            <p><b>IDENTIFICADOR CODIFICADOR DEL ESTADO (ID):</b> ID PE-MUN-00335</p>
            <p class="flex items-center gap-2">
                <b>FECHA DE EMISIÓN:</b> 
                <input type="text" class="input-cgr w-40" value="<?= date('d/m/Y') ?>">
            </p>
        </div>

        <p class="text-[12pt] mb-6 text-justify leading-relaxed">
            Quien suscribe certifica que cuenta con recursos para financiar el 
            <input type="text" class="input-cgr inline-block w-full border-b border-black text-center font-bold" value="<?= htmlspecialchars($expediente['titulo_compra']) ?>"> 
            que indica, según el siguiente detalle:
        </p>

        <div id="cuadros-financieros">
            <?php foreach($totales_por_cuenta as $cta => $monto_acto): ?>
            <table class="table-cgr">
                <tbody>
                    <tr>
                        <td>Imputación Presupuestaria</td>
                        <td class="font-bold text-center"><?= $cta ?></td>
                    </tr>
                    <tr>
                        <td>Año ejercicio presupuestario</td>
                        <td><input type="text" class="input-cgr text-center" value="<?= date('Y') ?>"></td>
                    </tr>
                    <tr>
                        <td>Monto comprometido por el acto administrativo</td>
                        <td class="flex items-center gap-1">
                            <span>$</span>
                            <input type="text" readonly class="input-cgr text-right font-bold" value="<?= number_format($monto_acto, 0, ',', '.') ?>">
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php endforeach; ?>
        </div>

        <div class="mt-8">
            <p class="font-bold text-[12pt] mb-2">Información adicional (opcional):</p>
            <textarea class="input-cgr w-full h-24 p-2 text-[11pt]" rows="4"><?= trim($info_adicional) ?></textarea>
        </div>

        <div class="mt-32 text-center">
            <p class="mb-2">_______________________________________________________________</p>
            <input type="text" class="input-cgr text-center font-bold block w-full mb-1 text-slate-800 uppercase" value="<?= htmlspecialchars($firmante_nombre . ' - ' . $firmante_cargo) ?>">
            <p class="text-[10pt] text-gray-500 uppercase tracking-widest mt-1">Firma Física o Electrónica Avanzada</p>
        </div>

    </div>


</body>
</html>