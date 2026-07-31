<?php
// generar_opi.php - Vista de Impresión (Borrador para firma)
require_once __DIR__ . '/config.php';
if (!isset($_GET['id'])) die("Falta ID");

// Cargar Datos (Similar al controller pero simplificado)
$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT e.*, u.nombre_completo, un.nombre as unidad, p.razon_social, p.rut as rut_prov, p.direccion as dir_prov FROM expedientes e JOIN usuarios u ON e.usuario_creador_id=u.id JOIN unidades un ON e.unidad_origen_id=un.id LEFT JOIN proveedores p ON e.proveedor_adjudicado_id=p.id WHERE e.id=?");
$stmt->execute([$id]);
$e = $stmt->fetch();

$stmtI = $pdo->prepare("SELECT * FROM expedientes_items WHERE expediente_id=?");
$stmtI->execute([$id]);
$items = $stmtI->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-gray-100 p-8 min-h-screen flex justify-center">

    <div class="bg-white w-[21cm] min-h-[29.7cm] p-10 shadow-lg relative">
        
        <button onclick="window.print()" class="no-print absolute top-4 right-4 bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 font-sans text-sm font-bold">
            🖨️ Descargar/Imprimir PDF
        </button>

        <div class="flex justify-between items-start border-b-2 border-black pb-4 mb-6">
            <div class="flex items-center gap-4">
                <img src="logo.png" alt="Logo" class="h-16 w-auto grayscale">
                <div>
                    <h1 class="text-xl font-bold uppercase">Ilustre Municipalidad</h1>
                    <p class="text-sm">Departamento de Adquisiciones</p>
                </div>
            </div>
            <div class="text-right">
                <h2 class="text-2xl font-bold border border-black px-4 py-2">ORDEN DE PEDIDO</h2>
                <p class="text-sm mt-1 font-bold">Ref: <?= $e['codigo_interno'] ?></p>
                <p class="text-xs mt-1">Fecha: <?= date('d/m/Y') ?></p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-8 mb-6 text-sm">
            <div class="border p-4">
                <h3 class="font-bold border-b mb-2 uppercase bg-gray-100">Datos del Proveedor</h3>
                <p><strong>Razón Social:</strong> <?= htmlspecialchars($e['razon_social']) ?></p>
                <p><strong>RUT:</strong> <?= htmlspecialchars($e['rut_prov']) ?></p>
                <p><strong>Dirección:</strong> <?= htmlspecialchars($e['dir_prov'] ?? 'N/A') ?></p>
            </div>
            <div class="border p-4">
                <h3 class="font-bold border-b mb-2 uppercase bg-gray-100">Datos de Solicitud</h3>
                <p><strong>Unidad:</strong> <?= htmlspecialchars($e['unidad']) ?></p>
                <p><strong>Solicitante:</strong> <?= htmlspecialchars($e['nombre_completo']) ?></p>
                <p><strong>Motivo:</strong> <?= htmlspecialchars($e['motivo_compra']) ?></p>
            </div>
        </div>

        <table class="w-full text-sm mb-8 border-collapse border border-black">
            <thead class="bg-gray-200">
                <tr>
                    <th class="border border-black p-2 text-left">Descripción</th>
                    <th class="border border-black p-2 w-20 text-center">Unidad</th>
                    <th class="border border-black p-2 w-16 text-center">Cant.</th>
                    <th class="border border-black p-2 w-28 text-right">P. Unitario</th>
                    <th class="border border-black p-2 w-28 text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php $total = 0; foreach($items as $i): 
                    $sub = $i['cantidad'] * $i['precio_unitario']; $total += $sub; ?>
                <tr>
                    <td class="border border-black p-2"><?= htmlspecialchars($i['descripcion']) ?></td>
                    <td class="border border-black p-2 text-center"><?= $i['unidad_medida'] ?></td>
                    <td class="border border-black p-2 text-center"><?= $i['cantidad'] ?></td>
                    <td class="border border-black p-2 text-right">$ <?= number_format($i['precio_unitario'],0,',','.') ?></td>
                    <td class="border border-black p-2 text-right">$ <?= number_format($sub,0,',','.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="border border-black p-2 text-right font-bold uppercase">Total a Pagar</td>
                    <td class="border border-black p-2 text-right font-bold text-lg">$ <?= number_format($total,0,',','.') ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="mt-20 grid grid-cols-2 gap-10 text-center">
            <div>
                <div class="border-t border-black w-2/3 mx-auto pt-2">
                    <p class="font-bold">Jefe de Adquisiciones</p>
                    <p class="text-xs">V°B° Revisión Técnica</p>
                </div>
            </div>
            <div>
                <div class="border-t border-black w-2/3 mx-auto pt-2">
                    <p class="font-bold">Administrador Municipal</p>
                    <p class="text-xs">Autorización Final de Compra</p>
                </div>
            </div>
        </div>

        <div class="absolute bottom-10 left-10 text-xs text-gray-500">
            Documento generado electrónicamente por Sistema de órdenes de pedido interno.
        </div>

    </div>
</body>
</html>