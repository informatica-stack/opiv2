<?php
// modal_adjudicacion_controller.php - Lógica separada para Adjudicación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'adjudicar') {
    try {
        $exp_id = $_POST['expediente_id'];
        $proveedor_seleccionado = $_POST['proveedor_id'] ?? '';
        $item_precios = $_POST['item_precio'] ?? [];
        $tipo_impuesto = 'NETO'; // Forzado a neto

        // Validar que se adjuntó el Acta de Adjudicación
        if (empty($_FILES['acta_adjudicacion']['name'])) {
            throw new Exception("Debe adjuntar el Acta de Adjudicación obligatoriamente.");
        }

        // Validar que todos los precios ingresados sean positivos
        foreach ($item_precios as $item_id => $precio_str) {
            $precio_ingresado = floatval(str_replace('.', '', $precio_str));
            if ($precio_ingresado <= 0) {
                throw new Exception("Debe ingresar un valor mayor a cero para todos los ítems.");
            }
        }

        // Obtener el expediente y validar su estado y monto disponible estimado
        $stmtExp = $pdo->prepare("SELECT id, codigo_interno, monto_estimado FROM expedientes WHERE id = ? AND usuario_creador_id = ? AND estado_actual = 'EN_EVALUACION_OFERTAS'");
        $stmtExp->execute([$exp_id, $user_id]);
        $expediente = $stmtExp->fetch(PDO::FETCH_ASSOC);
        if (!$expediente) {
            throw new Exception("El expediente no está disponible para selección de proveedor.");
        }

        $pdo->beginTransaction();

        $prov_id = null;
        $nombre_prov_historial = "";

        // Lógica Proveedor Nuevo o Existente
        if ($proveedor_seleccionado === 'NUEVO') {
            $rut = trim($_POST['rut_proveedor']);
            $razon_social = trim($_POST['nombre_proveedor']);
            $direccion = trim($_POST['direccion_proveedor']);

            if (empty($_FILES['ficha_proveedor']['name'])) {
                throw new Exception("Debe adjuntar la Ficha del Proveedor obligatoriamente.");
            }

            $stmtProv = $pdo->prepare("SELECT id FROM proveedores WHERE rut = ?");
            $stmtProv->execute([$rut]);
            $prov_id = $stmtProv->fetchColumn();

            if (!$prov_id) {
                try {
                    $stmtInsertP = $pdo->prepare("INSERT INTO proveedores (rut, razon_social, direccion) VALUES (?, ?, ?)");
                    $stmtInsertP->execute([$rut, $razon_social, $direccion]);
                } catch (Exception $e) {
                    $stmtInsertP = $pdo->prepare("INSERT INTO proveedores (rut, razon_social) VALUES (?, ?)");
                    $stmtInsertP->execute([$rut, $razon_social]);
                }
                $prov_id = $pdo->lastInsertId();
            }

            $nombre_prov_historial = "$razon_social ($rut)";

            $ext = validar_subida_archivo($_FILES['ficha_proveedor']);
            $file = $_FILES['ficha_proveedor'];
            $anio_actual = date('Y');
            $dir = __DIR__ . "/uploads/$anio_actual/exp_$exp_id/";
            if (!file_exists($dir)) mkdir($dir, 0777, true);
            
            $nombre_final = "ficha_prov_" . time() . "." . $ext;
            if(move_uploaded_file($file['tmp_name'], $dir . $nombre_final)){
                $ruta = "uploads/$anio_actual/exp_$exp_id/" . $nombre_final;
                $pdo->prepare("INSERT INTO expedientes_documentos (expediente_id, subido_por_id, tipo_doc, ruta_archivo, nombre_original) VALUES (?, ?, 'FICHA_PROVEEDOR', ?, ?)")->execute([$exp_id, $user_id, $ruta, $file['name']]);
            }

        } else {
            $prov_id = $proveedor_seleccionado;
            $stmtNombre = $pdo->prepare("SELECT razon_social, rut FROM proveedores WHERE id = ?");
            $stmtNombre->execute([$prov_id]);
            $dataProv = $stmtNombre->fetch();
            $nombre_prov_historial = $dataProv['razon_social'] . " (" . $dataProv['rut'] . ")";
        }

        // Procesar Ítems aplicando IVA (19%)
        $monto_final_calculado = 0;
        $iva_pct = 1.19;

        foreach ($item_precios as $item_id => $precio_str) {
            $precio_ingresado = floatval(str_replace('.', '', $precio_str));
            $nuevo_precio_unitario = $precio_ingresado * $iva_pct; // Forzado a neto
            
            $stmtQty = $pdo->prepare("SELECT cantidad FROM expedientes_items WHERE id = ? AND expediente_id = ?");
            $stmtQty->execute([$item_id, $exp_id]);
            $cantidad = $stmtQty->fetchColumn();

            if ($cantidad !== false) {
                $subtotal_linea = $cantidad * $nuevo_precio_unitario;
                $monto_final_calculado += $subtotal_linea;
                
                $pdo->prepare("UPDATE expedientes_items SET precio_unitario = ? WHERE id = ?")->execute([$nuevo_precio_unitario, $item_id]);
            }
        }

        // Validar que el monto total adjudicado no supere el monto disponible estimado previamente declarado
        if ($monto_final_calculado > floatval($expediente['monto_estimado'])) {
            throw new Exception("El monto total adjudicado ($" . number_format($monto_final_calculado, 0, ',', '.') . " Bruto) supera el monto disponible estimado previamente declarado ($" . number_format($expediente['monto_estimado'], 0, ',', '.') . " Bruto).");
        }

        // Guardar el Acta de Adjudicación
        $ext_acta = validar_subida_archivo($_FILES['acta_adjudicacion']);
        $file_acta = $_FILES['acta_adjudicacion'];
        $anio_actual = date('Y');
        $dir = __DIR__ . "/uploads/$anio_actual/exp_$exp_id/";
        if (!file_exists($dir)) mkdir($dir, 0777, true);
        
        $nombre_final_acta = "acta_adj_" . time() . "." . $ext_acta;
        if(move_uploaded_file($file_acta['tmp_name'], $dir . $nombre_final_acta)){
            $ruta_acta = "uploads/$anio_actual/exp_$exp_id/" . $nombre_final_acta;
            $pdo->prepare("INSERT INTO expedientes_documentos (expediente_id, subido_por_id, tipo_doc, ruta_archivo, nombre_original) VALUES (?, ?, 'OTRO', ?, ?)")->execute([$exp_id, $user_id, $ruta_acta, $file_acta['name']]);
        } else {
            throw new Exception("No se pudo guardar el archivo del Acta de Adjudicación.");
        }

        // Actualizar Expediente
        $stmtUpd = $pdo->prepare("UPDATE expedientes SET proveedor_adjudicado_id = ?, monto_definitivo = ?, fecha_adjudicacion = NOW() WHERE id = ?");
        $stmtUpd->execute([$prov_id, $monto_final_calculado, $exp_id]);

        avanzar_flujo($pdo, $exp_id, $user_id, "Proveedor seleccionado: $nombre_prov_historial. Acta de Adjudicación adjunta. Montos actualizados.");

        $pdo->commit();
        $mensaje = "Selección registrada correctamente. Enviado a Presupuesto para visación final.";
        $tipo_mensaje = "success";

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}
?>