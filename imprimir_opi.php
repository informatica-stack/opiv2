<?php
// imprimir_opi.php - Generador de Formato Institucional OPI (Replica Oficial Modelo N° 758 Lebu)
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) die("Acceso denegado.");

$id = $_GET['id'] ?? null;
if (!$id) die("ID no especificado.");

$auto_download = isset($_GET['auto_download']) && $_GET['auto_download'] == '1';

// Obtener datos generales de la solicitud
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
$stmt->execute([$id]);
$exp = $stmt->fetch();
if (!$exp) die("Expediente no encontrado.");

// Obtener los ítems
$items = $pdo->query("
    SELECT ei.*, cm.codigo as cuenta_codigo, ag.codigo as ag_codigo 
    FROM expedientes_items ei 
    LEFT JOIN presupuestos_asignados pa ON ei.presupuesto_asignado_id = pa.id 
    LEFT JOIN cuentas_maestras cm ON pa.cuenta_maestra_id = cm.id 
    LEFT JOIN areas_gestion ag ON pa.area_gestion_id = ag.id
    WHERE ei.expediente_id = $id
")->fetchAll();

// 1. Creador / Solicitante
$stmtCreador = $pdo->prepare("
    SELECT u.nombre_completo, e.created_at
    FROM expedientes e
    JOIN usuarios u ON e.usuario_creador_id = u.id
    WHERE e.id = ?
");
$stmtCreador->execute([$id]);
$creador = $stmtCreador->fetch();

// 2. Firma Jefatura (Director)
$stmtJefe = $pdo->prepare("
    SELECT u.nombre_completo, eh.fecha_accion 
    FROM expedientes_historial eh 
    JOIN usuarios u ON eh.usuario_id = u.id 
    WHERE eh.expediente_id = ? AND (eh.estado_anterior = 'EN_REVISION_JEFATURA' OR eh.estado_nuevo = 'EN_VALIDACION_PRESUPUESTARIA') AND eh.accion = 'APROBAR' 
    ORDER BY eh.id DESC LIMIT 1
");
$stmtJefe->execute([$id]);
$firma_jefe = $stmtJefe->fetch();

// 3. Firma Presupuesto
$stmtPresupuesto = $pdo->prepare("
    SELECT u.nombre_completo, eh.fecha_accion 
    FROM expedientes_historial eh 
    JOIN usuarios u ON eh.usuario_id = u.id 
    WHERE eh.expediente_id = ? AND (eh.estado_anterior LIKE 'EN_VALIDACION_PRESUPUESTARIA%' OR eh.accion = 'SOLICITAR_CDP') AND eh.accion IN ('APROBAR', 'SOLICITAR_CDP') 
    ORDER BY eh.id DESC LIMIT 1
");
$stmtPresupuesto->execute([$id]);
$firma_presupuesto = $stmtPresupuesto->fetch();

// 4. Firma Administrador Municipal
$stmtAdmin = $pdo->prepare("
    SELECT u.nombre_completo, eh.fecha_accion 
    FROM expedientes_historial eh 
    JOIN usuarios u ON eh.usuario_id = u.id 
    WHERE eh.expediente_id = ? AND (eh.estado_anterior = 'EN_APROBACION_ADMINISTRADOR' OR eh.estado_nuevo = 'EN_EMISION_OC') AND eh.accion = 'APROBAR' 
    ORDER BY eh.id DESC LIMIT 1
");
$stmtAdmin->execute([$id]);
$firma_admin = $stmtAdmin->fetch();

if (!$firma_admin) {
    $stmtUserAdmin = $pdo->prepare("SELECT u.nombre_completo FROM usuarios u JOIN roles r ON u.rol_id = r.id WHERE r.nombre = 'ADMIN_MUNICIPAL' AND u.activo = 1 LIMIT 1");
    $stmtUserAdmin->execute();
    $nameAdminDefault = $stmtUserAdmin->fetchColumn() ?: ($_SESSION['user_nombre'] ?? 'ADMINISTRADOR MUNICIPAL');
    $firma_admin = [
        'nombre_completo' => $nameAdminDefault,
        'fecha_accion' => null
    ];
}

// FORMATO Y CÁLCULOS
$es_licitacion = ($exp['tipo_compra_cod'] === 'LICITACION');
$es_compra_agil = in_array($exp['tipo_compra_cod'], ['AGIL', 'COMPRA_AGIL']);

$monto_usar = (isset($exp['monto_definitivo']) && $exp['monto_definitivo'] > 0) ? $exp['monto_definitivo'] : $exp['monto_estimado'];
$iva = round($monto_usar - ($monto_usar / 1.19));
$neto = $monto_usar - $iva;

$titulo_doc = "ORDEN DE PEDIDO INTERNO";

// Asignación y reserva anticipada de Folio OPI Oficial si aún no ha sido asignado
if (empty($exp['folio_opi'])) {
    $anio = date('Y');
    $intentos = 0;
    while ($intentos < 5) {
        try {
            $stmtMax = $pdo->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(folio_opi, '-', -1) AS UNSIGNED)) FROM expedientes WHERE folio_opi LIKE ?");
            $stmtMax->execute(["OPI-$anio-%"]);
            $max_num = $stmtMax->fetchColumn();
            
            $siguiente = $max_num ? $max_num + 1 : 1;
            $folio_opi = "OPI-" . $anio . "-" . str_pad($siguiente, 4, '0', STR_PAD_LEFT);

            $stmtUpd = $pdo->prepare("UPDATE expedientes SET folio_opi = ?, fecha_aprobacion_opi = IFNULL(fecha_aprobacion_opi, NOW()) WHERE id = ? AND folio_opi IS NULL");
            $stmtUpd->execute([$folio_opi, $id]);
            $exp['folio_opi'] = $folio_opi;
            break;
        } catch (PDOException $e) {
            if ($e->getCode() == '23000' || $e->errorInfo[1] == 1062) {
                $intentos++;
                usleep(100000);
            } else {
                break;
            }
        }
    }
}

$folio_mostrar = !empty($exp['folio_opi']) ? $exp['folio_opi'] : ($exp['codigo_interno'] ?? "N° " . $exp['id']);

function fecha_espanol($fecha_db) {
    if (!$fecha_db) return date('d') . ' de ' . meses_es(date('n')) . ' de ' . date('Y');
    $ts = strtotime($fecha_db);
    return date('j', $ts) . ' de ' . meses_es(date('n', $ts)) . ' de ' . date('Y', $ts);
}

function meses_es($m) {
    $meses = [1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'];
    return $meses[(int)$m] ?? '';
}

$fecha_formateada = fecha_espanol($exp['fecha_aprobacion_opi'] ?? $exp['created_at']);

$cuentas_usadas = array_unique(array_filter(array_column($items, 'cuenta_codigo')));
$ags_usadas = array_unique(array_filter(array_column($items, 'ag_codigo')));
$id_cm_usados = array_unique(array_filter(array_column($items, 'id_producto_cm')));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>OPI N° <?= htmlspecialchars($folio_mostrar) ?> - Municipalidad de Lebu</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm 15mm 15mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
            line-height: 1.3;
        }
        .no-print {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #1e293b;
            color: #fff;
            padding: 10px 20px;
            margin-bottom: 15px;
            border-radius: 6px;
        }
        .no-print button {
            background: #3b82f6;
            color: #fff;
            border: none;
            padding: 8px 16px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
        }
        .no-print button:hover {
            background: #2563eb;
        }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; }
        }

        /* HEADER ESTRUCTURAL */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .header-table td {
            vertical-align: top;
        }
        .logo-dept {
            font-size: 9px;
            font-weight: bold;
            line-height: 1.2;
            text-transform: uppercase;
        }
        .doc-title {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .doc-folio {
            text-align: right;
            font-size: 12px;
            font-weight: bold;
        }
        .doc-fecha {
            text-align: right;
            font-size: 11px;
            margin-top: 4px;
        }

        /* DE / A BOX */
        .de-a-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 11px;
        }
        .de-a-table td {
            padding: 2px 0;
            vertical-align: top;
        }
        .fw-bold { font-weight: bold; }
        .text-uppercase { text-transform: uppercase; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* TABLA PRINCIPAL DE PRODUCTOS */
        .table-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0px;
        }
        .table-items th, .table-items td {
            border: 1px solid #000;
            padding: 5px 6px;
            font-size: 10px;
        }
        .table-items th {
            background-color: #f1f5f9;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
        }

        /* TOTALES BOX */
        .totales-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .totales-table td {
            border: 1px solid #000;
            padding: 4px 8px;
            font-size: 10.5px;
        }

        /* CAJAS DE INFORMACIÓN DE RESPALDO */
        .box-info {
            border: 1px solid #000;
            padding: 6px 10px;
            margin-bottom: 8px;
            font-size: 10px;
            background: #fff;
        }
        .box-info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .box-info-table td {
            padding: 2px 4px;
            vertical-align: middle;
        }

        /* SECCIÓN FIRMAS */
        .firmas-section {
            width: 100%;
            margin-top: 25px;
        }
        .firma-box {
            text-align: center;
            vertical-align: bottom;
            padding: 0 10px;
        }
        .firma-nombre {
            font-weight: bold;
            font-size: 10.5px;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .firma-cargo {
            font-weight: bold;
            font-size: 9.5px;
            text-transform: uppercase;
            color: #111;
        }
        .firma-timestamp {
            font-size: 8.5px;
            color: #444;
            margin-top: 3px;
        }
    </style>
</head>
<body>

    <div class="no-print">
        <div>
            <strong>Orden de Pedido Interno (OPI N° <?= htmlspecialchars($folio_mostrar) ?>)</strong>
            <span style="font-size: 11px; opacity: 0.8; display: block;">Documento Oficial - Municipalidad de Lebu</span>
        </div>
        <button onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
    </div>

    <!-- ENCABEZADO OFICIAL -->
    <table class="header-table">
        <tr>
            <td style="width: 35%;">
                <div class="logo-dept">
                    República de Chile<br>
                    <strong>MUNICIPALIDAD DE LEBU</strong><br>
                    <?= mb_strtoupper($exp['unidad']) ?>
                </div>
            </td>
            <td style="width: 35%; text-align: center;">
                <div class="doc-title"><?= $titulo_doc ?></div>
            </td>
            <td style="width: 30%;">
                <div class="doc-folio">N° &nbsp; <?= htmlspecialchars($folio_mostrar) ?></div>
                <div class="doc-fecha">Lebu, <?= $fecha_formateada ?></div>
            </td>
        </tr>
    </table>

    <!-- SECCIÓN DE / A -->
    <table class="de-a-table">
        <tr>
            <td style="width: 45px;" class="fw-bold">DE:</td>
            <td class="fw-bold text-uppercase"><?= mb_strtoupper($exp['unidad']) ?></td>
        </tr>
        <tr>
            <td class="fw-bold">A:</td>
            <td>
                <div class="fw-bold">DIRECCIÓN DE ADMINISTRACION Y FINANZAS</div>
                <div class="fw-bold">UNIDAD DE ADQUISICIONES</div>
            </td>
        </tr>
    </table>

    <!-- TEXTO INTRODUCTORIO -->
    <div style="margin-bottom: 8px; font-size: 10.5px;">
        <strong>1.</strong> Agradeceré a Usted, tenga a bien efectuar la adquisición de los siguientes bienes y/o servicios, que a continuación se detallan:
    </div>

    <!-- TABLA DE PRODUCTOS -->
    <table class="table-items">
        <thead>
            <tr>
                <th style="width: 8%;">CANTIDAD</th>
                <th style="width: 12%;">UNIDAD DE MEDIDA</th>
                <th style="width: 50%;">DESCRIPCIÓN DE LOS BIENES Y/O SERVICIOS</th>
                <th style="width: 15%;">MONTO UNITARIO</th>
                <th style="width: 15%;">MONTO TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items as $it): 
                $total_item = $it['cantidad'] * $it['precio_unitario'];
            ?>
            <tr>
                <td class="text-center fw-bold"><?= floatval($it['cantidad']) ?></td>
                <td class="text-center text-uppercase"><?= mb_strtoupper($it['unidad_medida']) ?></td>
                <td>
                    <?= nl2br(htmlspecialchars($it['descripcion'])) ?>
                    <?php if(!empty($it['id_producto_cm'])): ?>
                        <br><span style="font-size: 8.5px; color: #555;">(ID CM: <?= htmlspecialchars($it['id_producto_cm']) ?>)</span>
                    <?php endif; ?>
                </td>
                <td class="text-right">$ <?= number_format($it['precio_unitario'], 0, ',', '.') ?></td>
                <td class="text-right fw-bold">$ <?= number_format($total_item, 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
            
            <?php 
            $filas_vacias = max(0, 4 - count($items));
            for($i=0; $i < $filas_vacias; $i++): 
            ?>
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <!-- TOTALES -->
    <table class="totales-table">
        <tr>
            <td style="border-top: none; border-left: none; border-bottom: none; width: 62%;">&nbsp;</td>
            <td class="fw-bold text-right" style="width: 23%; background-color: #f8fafc;">MONTO NETO</td>
            <td class="text-right fw-bold font-monospace" style="width: 15%;">$ <?= number_format($neto, 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td style="border: none;">&nbsp;</td>
            <td class="fw-bold text-right" style="background-color: #f8fafc;">I.V.A. 19%</td>
            <td class="text-right font-monospace">$ <?= number_format($iva, 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td style="border: none;">&nbsp;</td>
            <td class="fw-bold text-right" style="background-color: #f1f5f9;">TOTAL</td>
            <td class="text-right fw-bold font-monospace" style="font-size: 11px; background-color: #f1f5f9;">$ <?= number_format($monto_usar, 0, ',', '.') ?></td>
        </tr>
    </table>

    <!-- DATOS PROVEEDOR -->
    <div class="box-info">
        <table class="box-info-table">
            <tr>
                <td style="width: 120px;" class="fw-bold">DATOS PROVEEDOR</td>
                <td style="width: 100px;" class="fw-bold">RAZÓN SOCIAL:</td>
                <td class="fw-bold text-uppercase"><?= mb_strtoupper($exp['razon_social'] ?? 'PENDIENTE DE ADJUDICACIÓN') ?></td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td class="fw-bold">RUT:</td>
                <td class="font-monospace"><?= htmlspecialchars($exp['rut_proveedor'] ?? '-') ?></td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td class="fw-bold">DIRECCIÓN:</td>
                <td class="text-uppercase"><?= mb_strtoupper($exp['direccion_proveedor'] ?? '-') ?></td>
            </tr>
        </table>
    </div>

    <!-- IMPUTACIÓN PRESUPUESTARIA (CUENTA) -->
    <div class="box-info">
        <table class="box-info-table">
            <tr>
                <td style="width: 65px;" class="fw-bold">CUENTA</td>
                <td style="width: 120px;" class="fw-bold">PRESUPUESTARIA:</td>
                <td>N° &nbsp;<span class="fw-bold font-monospace"><?= empty($cuentas_usadas) ? '-' : implode(", ", $cuentas_usadas) ?></span></td>
                <td style="width: 110px;" class="fw-bold">AREA DE GESTIÓN:</td>
                <td><span class="fw-bold font-monospace"><?= empty($ags_usadas) ? '01' : implode(", ", $ags_usadas) ?></span></td>
                <td style="width: 110px;" class="fw-bold">CENTRO COSTOS:</td>
                <td><span class="fw-bold font-monospace"><?= htmlspecialchars($exp['centro_costo_id'] ?? '-') ?> &nbsp; (<?= mb_strtoupper($exp['centro_costo']) ?>)</span></td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td class="fw-bold">COMPLEMENTARIA:</td>
                <td colspan="5">N° &nbsp; -</td>
            </tr>
        </table>
    </div>

    <!-- PLAN DE COMPRAS Y MODALIDAD -->
    <div class="box-info">
        <table class="box-info-table">
            <tr>
                <td style="width: 130px;" class="fw-bold">PLAN DE COMPRAS</td>
                <td style="width: 80px;" class="fw-bold">PROYECTO:</td>
                <td><span class="font-monospace"><?= htmlspecialchars($exp['plan_compras_proyecto'] ?? '-') ?></span></td>
                <td style="width: 60px;" class="fw-bold">ÍTEM:</td>
                <td><span class="font-monospace"><?= htmlspecialchars($exp['plan_compras_item'] ?? '-') ?></span></td>
            </tr>
            <tr>
                <td class="fw-bold">C. SUMINISTROS ID:</td>
                <td colspan="2"><span class="font-monospace"><?= htmlspecialchars($exp['id_contrato_suministro'] ?? '-') ?></span></td>
                <td class="fw-bold">CONV. MARCO O°C:</td>
                <td><span class="font-monospace"><?= htmlspecialchars(!empty($exp['conv_marco_oc']) ? $exp['conv_marco_oc'] : (!empty($exp['orden_compra_numero']) ? $exp['orden_compra_numero'] : '-')) ?></span></td>
            </tr>
            <tr>
                <td class="fw-bold">COMPRA ÁGIL ID:</td>
                <td colspan="2"><span class="font-monospace"><?= htmlspecialchars($exp['id_compra_agil'] ?? '-') ?></span></td>
                <td class="fw-bold">DECRETO ALCALDICIO N°:</td>
                <td><span class="font-monospace"><?= htmlspecialchars($exp['decreto_alcaldicio_numero'] ?? '-') ?></span></td>
            </tr>
        </table>
    </div>

    <!-- DESTINO DE BIENES / SERVICIOS -->
    <div style="margin-top: 10px; margin-bottom: 25px; font-size: 10.5px;">
        <strong>2.</strong> Los presentes bienes/servicios serán destinados a:
        <div style="margin-top: 4px; padding-left: 15px;" class="fw-bold text-uppercase">
            <?= mb_strtoupper($exp['motivo_compra'] ?? $exp['titulo_compra']) ?>
        </div>
    </div>

    <!-- SECCIÓN ESTAMPADO DE FIRMAS -->
    <table style="width: 100%; border-collapse: collapse; margin-top: 30px;">
        <!-- FIRMA DERECHA SUPERIOR: SOLICITANTE / CREADOR -->
        <tr>
            <td style="width: 50%;">&nbsp;</td>
            <td style="width: 50%; text-align: center; padding-bottom: 35px;">
                <div class="firma-nombre"><?= mb_strtoupper($creador['nombre_completo'] ?? $exp['solicitante']) ?></div>
                <div class="firma-cargo"><?= mb_strtoupper($exp['unidad']) ?></div>
                <?php if(!empty($creador['created_at'])): ?>
                    <div class="firma-timestamp">Creado el <?= date('d/m/Y \a \l\a\s H:i', strtotime($creador['created_at'])) ?> hrs.</div>
                <?php endif; ?>
            </td>
        </tr>

        <!-- FILA INFERIOR DE VISADORES Y FIRMANTES -->
        <tr>
            <!-- V°B° PRESUPUESTARIO -->
            <td style="width: 50%; text-align: center; vertical-align: bottom;">
                <?php if(!empty($firma_presupuesto)): ?>
                    <div class="firma-nombre"><?= mb_strtoupper($firma_presupuesto['nombre_completo']) ?></div>
                    <div class="firma-cargo">V°B° PRESUPUESTARIO</div>
                    <div class="firma-timestamp">Visado el <?= date('d/m/Y \a \l\a\s H:i', strtotime($firma_presupuesto['fecha_accion'])) ?> hrs.</div>
                <?php else: ?>
                    <div style="border-top: 1px dashed #777; width: 70%; margin: 0 auto 5px auto;"></div>
                    <div class="firma-cargo">V°B° PRESUPUESTARIO</div>
                    <div class="firma-timestamp" style="font-style: italic;">(Pendiente de Visación)</div>
                <?php endif; ?>
            </td>

            <!-- ADMINISTRADOR MUNICIPAL -->
            <td style="width: 50%; text-align: center; vertical-align: bottom;">
                <?php if(!empty($firma_admin['fecha_accion'])): ?>
                    <div class="firma-nombre"><?= mb_strtoupper($firma_admin['nombre_completo']) ?></div>
                    <div class="firma-cargo">ADMINISTRADOR MUNICIPAL</div>
                    <div class="firma-timestamp">Aprobado el <?= date('d/m/Y \a \l\a\s H:i', strtotime($firma_admin['fecha_accion'])) ?> hrs.</div>
                <?php else: ?>
                    <div class="firma-nombre"><?= mb_strtoupper($firma_admin['nombre_completo']) ?></div>
                    <div class="firma-cargo">ADMINISTRADOR MUNICIPAL</div>
                    <div class="firma-timestamp" style="font-style: italic;">(Pendiente de Firma)</div>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <!-- AUTOMATIC DOWNLOAD / PRINT TRIGGER -->
    <?php if ($auto_download): ?>
    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 300);
        };
    </script>
    <?php endif; ?>

</body>
</html>