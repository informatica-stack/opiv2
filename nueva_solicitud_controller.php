<?php
// nueva_solicitud_controller.php - Lógica de Negocio (V5.0 - Plan de Compras Integrado)
require_once __DIR__ . '/config.php';

// 1. SEGURIDAD
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];
$unidad_id = $_SESSION['user_unidad'];
$es_jefe = $_SESSION['es_jefe'] ?? 0;
$mensaje = '';

// VARIABLES PARA RETENER EL ESTADO DEL FORMULARIO
$post_titulo_compra = '';
$post_motivo = '';
$post_tipo_compra = '';
$post_prioridad = '';
$post_rango_utm = '';
$post_proveedor_id = '';
$post_id_contrato_suministro = '';
$post_plan_proyecto = '';
$post_plan_item = '';
$post_monto_disponible_neto = '';
$post_tipo_impuesto = 'NETO';
$items_old = [];
$criterios_old = [];

// 2. CARGA DE DATOS MAESTROS
try {
    // Detectar Centro de Costo
    $centro_costo = null;
    $current = $unidad_id;
    while ($current) {
        $stmt = $pdo->prepare("SELECT id, padre_id, centro_costo_id, nombre FROM unidades WHERE id = ?");
        $stmt->execute([$current]);
        $u = $stmt->fetch();
        if ($u && $u['centro_costo_id']) {
            $stmtCC = $pdo->prepare("SELECT * FROM centros_costo WHERE id = ?");
            $stmtCC->execute([$u['centro_costo_id']]);
            $centro_costo = $stmtCC->fetch();
            break;
        }
        $current = $u['padre_id'] ?? null;
    }

    $cuentas_disponibles = [];
    if ($centro_costo) {
        $stmtC = $pdo->prepare("
            SELECT pa.id, cm.codigo, cm.nombre, ag.codigo as ag_codigo 
            FROM presupuestos_asignados pa
            JOIN cuentas_maestras cm ON pa.cuenta_maestra_id = cm.id
            LEFT JOIN areas_gestion ag ON pa.area_gestion_id = ag.id
            WHERE pa.centro_costo_id = ?
            ORDER BY cm.codigo ASC
        ");
        $stmtC->execute([$centro_costo['id']]);
        $cuentas_disponibles = $stmtC->fetchAll(PDO::FETCH_ASSOC);
    }

    $tipos_compra = $pdo->query("SELECT * FROM tipos_compra WHERE activo = 1 ORDER BY nombre ASC")->fetchAll();
    $prioridades = $pdo->query("SELECT * FROM prioridades WHERE activo = 1")->fetchAll();
    $rangos_utm = $pdo->query("SELECT * FROM rangos_utm WHERE activo = 1 ORDER BY min_utm ASC")->fetchAll();
    
    // Proveedores Frecuentes del Usuario
    $mis_proveedores = $pdo->query("
        SELECT p.* FROM proveedores p
        INNER JOIN expedientes e ON e.proveedor_adjudicado_id = p.id
        WHERE e.usuario_creador_id = $user_id
        GROUP BY p.id ORDER BY p.razon_social ASC
    ")->fetchAll();

    // Todos los proveedores
    $otros_proveedores = $pdo->query("SELECT * FROM proveedores ORDER BY razon_social ASC")->fetchAll();

    $mapa_tipos = [];
    $mapa_requiere_cotizacion = [];
    foreach($tipos_compra as $tc) {
        $mapa_tipos[$tc['id']] = $tc['codigo'];
        $mapa_requiere_cotizacion[$tc['id']] = (int)$tc['requiere_cotizacion'];
    }

} catch (Exception $e) {
    die("Error de sistema: " . $e->getMessage());
}

// 3. PROCESAR GUARDADO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    
    $post_titulo_compra = trim($_POST['titulo_compra'] ?? '');
    $post_motivo = trim($_POST['motivo'] ?? '');
    $post_tipo_compra = $_POST['tipo_compra_id'] ?? '';
    $post_prioridad = $_POST['prioridad_id'] ?? '';
    $post_rango_utm = $_POST['rango_utm_id'] ?? '';
    $post_id_contrato_suministro = trim($_POST['id_contrato_suministro'] ?? '');
    $post_plan_proyecto = trim($_POST['plan_compras_proyecto'] ?? '');
    $post_plan_item = trim($_POST['plan_compras_item'] ?? '');
    $post_monto_disponible_neto = trim($_POST['monto_disponible_neto'] ?? '');
    $post_tipo_impuesto = $_POST['tipo_impuesto'] ?? 'NETO';
    
    // Manejo de Proveedor Nuevo vs Existente
    if (isset($_POST['proveedor_id']) && $_POST['proveedor_id'] === 'NUEVO') {
        $rut_nuevo = trim($_POST['nuevo_prov_rut'] ?? '');
        $nom_nuevo = trim($_POST['nuevo_prov_nombre'] ?? '');
        $dir_nuevo = trim($_POST['nuevo_prov_direccion'] ?? '');
        if ($rut_nuevo && $nom_nuevo) {
            $post_motivo .= "\n\n[NUEVO PROVEEDOR SOLICITADO: $nom_nuevo (RUT: $rut_nuevo, Dirección: " . ($dir_nuevo ? $dir_nuevo : 'N/A') . ")]";
        }
        $post_proveedor_id = null;
    } else {
        $post_proveedor_id = !empty($_POST['proveedor_id']) ? $_POST['proveedor_id'] : null;
    }
    
    $desc = $_POST['desc'] ?? [];
    $id_cm = $_POST['id_producto_cm'] ?? [];
    $uni = $_POST['uni'] ?? [];
    $cant = $_POST['cant'] ?? [];
    $prec = $_POST['prec'] ?? [];
    $cuenta_ids = $_POST['cuenta_id'] ?? [];

    for ($i = 0; $i < count($desc); $i++) {
        $items_old[] = ['desc' => $desc[$i] ?? '', 'id_cm' => $id_cm[$i] ?? '', 'uni' => $uni[$i] ?? 'UNIDAD', 'cant' => $cant[$i] ?? 1, 'prec' => $prec[$i] ?? 0, 'cuenta_id' => $cuenta_ids[$i] ?? ''];
    }

    if (isset($_POST['crit_desc'])) {
        foreach ($_POST['crit_desc'] as $i => $cdesc) {
            $criterios_old[] = ['desc' => $cdesc, 'porc' => $_POST['crit_porc'][$i] ?? 0];
        }
    }

    try {
        if (!$centro_costo) throw new Exception("Sin presupuesto asignado a su unidad.");
        if (empty($desc) || empty($desc[0])) throw new Exception("Debe ingresar al menos un ítem.");

        $pdo->beginTransaction();
        
        $tipo_compra_id = $_POST['tipo_compra_id'];
        $codigo_tc = $mapa_tipos[$tipo_compra_id] ?? '';
        
        $stmtFlujo = $pdo->prepare("SELECT estado_destino FROM flujos_definicion WHERE tipo_compra_id = ? AND estado_actual = 'BORRADOR'");
        $stmtFlujo->execute([$tipo_compra_id]);
        $estado_destino = $stmtFlujo->fetchColumn();

        if (!$estado_destino) throw new Exception("Error: No hay un flujo inicial configurado para este tipo de compra.");

        if ($es_jefe == 1 && $estado_destino === 'EN_REVISION_JEFATURA') {
            $stmtFlujo2 = $pdo->prepare("SELECT estado_destino FROM flujos_definicion WHERE tipo_compra_id = ? AND estado_actual = ?");
            $stmtFlujo2->execute([$tipo_compra_id, $estado_destino]);
            if ($siguiente = $stmtFlujo2->fetchColumn()) $estado_destino = $siguiente;
        }

        $stmtTC = $pdo->prepare("SELECT requiere_cotizacion FROM tipos_compra WHERE id = ?");
        $stmtTC->execute([$tipo_compra_id]);
        $requiere_cot = (int) $stmtTC->fetchColumn();

        $iva_pct = 1.19;
        $total_est_bruto = 0;

        if ($requiere_cot) {
            $monto_raw = str_replace('.', '', $_POST['monto_disponible_neto'] ?? '0');
            $monto_disp_neto = floatval($monto_raw);
            $total_est_bruto = $monto_disp_neto * $iva_pct;
        } else {
            foreach ($cant as $i => $c) {
                $p_unit = floatval($prec[$i]);
                $p_unit *= $iva_pct; // Forzar cálculo en neto
                $total_est_bruto += (floatval($c) * $p_unit);
            }
        }

        // LIMPIEZA DE CAMPOS DE CABECERA
        if (in_array($codigo_tc, ['AGIL', 'COMPRA_AGIL', 'LICITACION'])) {
            $post_proveedor_id = null; 
        }
        if ($codigo_tc !== 'CONTRATO_SUMINISTRO') {
            $post_id_contrato_suministro = null;
        }

        if ($codigo_tc === 'LICITACION') {
            $suma_porcentajes = 0;
            if (isset($_POST['crit_porc'])) {
                foreach ($_POST['crit_porc'] as $porc) $suma_porcentajes += floatval($porc);
            }
            if ($suma_porcentajes != 100) throw new Exception("Los criterios de evaluación deben sumar exactamente 100%.");
        }

        $anio_actual = date('Y');
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM expedientes WHERE codigo_interno LIKE ?");
        $stmtCount->execute(["REQ-$anio_actual-%"]);
        $cantidad_actual = (int) $stmtCount->fetchColumn();
        
        while(true) {
            $codigo = "REQ-" . $anio_actual . "-" . str_pad($cantidad_actual + 1, 4, '0', STR_PAD_LEFT);
            $chk = $pdo->prepare("SELECT id FROM expedientes WHERE codigo_interno = ?");
            $chk->execute([$codigo]);
            if(!$chk->fetch()) break; 
            $cantidad_actual++;
        }

        // INSERTAR EXPEDIENTE
        $stmt = $pdo->prepare("INSERT INTO expedientes (codigo_interno, titulo_compra, usuario_creador_id, unidad_origen_id, centro_costo_id, tipo_compra_id, prioridad_id, rango_utm_id, proveedor_adjudicado_id, id_contrato_suministro, plan_compras_proyecto, plan_compras_item, estado_actual, monto_estimado, motivo_compra) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$codigo, $post_titulo_compra, $user_id, $unidad_id, $centro_costo['id'], $tipo_compra_id, $post_prioridad, $post_rango_utm, $post_proveedor_id, $post_id_contrato_suministro, $post_plan_proyecto, $post_plan_item, $estado_destino, $total_est_bruto, $post_motivo]);
        $exp_id = $pdo->lastInsertId();

        // Subir Ficha de Proveedor si es nuevo
        if (isset($_FILES['ficha_proveedor']) && $_FILES['ficha_proveedor']['error'] === UPLOAD_ERR_OK) {
            $ext_ficha = validar_subida_archivo($_FILES['ficha_proveedor']);
            if ($ext_ficha) {
                $dir = __DIR__ . "/uploads/$anio_actual/exp_$exp_id/";
                if (!file_exists($dir)) {
                    if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
                        throw new Exception("Error de permisos: No se pudo crear el directorio de destino en '$dir'. Verifique los permisos de /app/uploads.");
                    }
                }
                $nombre_final_ficha = "ficha_prov_" . time() . "." . $ext_ficha;
                if (move_uploaded_file($_FILES['ficha_proveedor']['tmp_name'], $dir . $nombre_final_ficha)) {
                    $ruta_ficha = "uploads/$anio_actual/exp_$exp_id/" . $nombre_final_ficha;
                    $pdo->prepare("INSERT INTO expedientes_documentos (expediente_id, subido_por_id, tipo_doc, ruta_archivo, nombre_original) VALUES (?, ?, 'FICHA_PROVEEDOR', ?, ?)")
                        ->execute([$exp_id, $user_id, $ruta_ficha, $_FILES['ficha_proveedor']['name']]);
                }
            }
        }

        // INSERTAR ITEMS
        $stmtItem = $pdo->prepare("INSERT INTO expedientes_items (expediente_id, descripcion, id_producto_cm, unidad_medida, cantidad, precio_unitario, presupuesto_asignado_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($desc as $i => $d) {
            if ($requiere_cot) {
                $p_final = 0;
            } else {
                $p_ingresado = floatval($prec[$i]);
                $p_final = $p_ingresado * $iva_pct; // Forzado a neto
            }
            if ($codigo_tc === 'CONVENIO_MARCO') {
                $val_cm = trim($id_cm[$i] ?? '');
                if ($val_cm === '' || !ctype_digit($val_cm)) {
                    throw new Exception("El ID Convenio Marco para el ítem '" . htmlspecialchars($d) . "' debe ser exclusivamente numérico.");
                }
            } else {
                $val_cm = null;
            }
            
            $stmtItem->execute([$exp_id, $d, $val_cm, $uni[$i], floatval($cant[$i]), $p_final, $cuenta_ids[$i]]);
        }

        // Insertar Criterios
        if ($codigo_tc === 'LICITACION' && isset($_POST['crit_num'])) {
            $stmtCrit = $pdo->prepare("INSERT INTO expedientes_criterios (expediente_id, numero_criterio, descripcion, porcentaje) VALUES (?, ?, ?, ?)");
            foreach ($_POST['crit_num'] as $i => $num) {
                $stmtCrit->execute([$exp_id, $num, $_POST['crit_desc'][$i], floatval($_POST['crit_porc'][$i])]);
            }
        }

        // Subir Archivos
        if (isset($_FILES['archivos_adjuntos']) && !empty($_FILES['archivos_adjuntos']['name'][0])) {
            $total_files = count($_FILES['archivos_adjuntos']['name']);
            $dir = __DIR__ . "/uploads/$anio_actual/exp_$exp_id/";
            
            // Validar todos los archivos antes de procesar alguno
            for ($i = 0; $i < $total_files; $i++) {
                if ($_FILES['archivos_adjuntos']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                    validar_subida_archivo($_FILES['archivos_adjuntos'], $i);
                }
            }

            if (!file_exists($dir)) {
                if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
                    throw new Exception("Error de permisos: No se pudo crear la carpeta del expediente en '$dir'. Verifique los permisos de /app/uploads.");
                }
            }
            
            for ($i = 0; $i < $total_files; $i++) {
                if ($_FILES['archivos_adjuntos']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['archivos_adjuntos']['name'][$i], PATHINFO_EXTENSION));
                    $nombre_original = $_FILES['archivos_adjuntos']['name'][$i];
                    $nombre_final = "adj_" . time() . "_$i." . $ext;
                    
                    if(move_uploaded_file($_FILES['archivos_adjuntos']['tmp_name'][$i], $dir . $nombre_final)){
                        $ruta = "uploads/$anio_actual/exp_$exp_id/" . $nombre_final;
                        $pdo->prepare("INSERT INTO expedientes_documentos (expediente_id, subido_por_id, tipo_doc, ruta_archivo, nombre_original) VALUES (?, ?, 'OTRO', ?, ?)")->execute([$exp_id, $user_id, $ruta, $nombre_original]);
                    }
                }
            }
        }

        // Historial
        $comentario_hist = "Solicitud ingresada al sistema. Proyecto: $post_plan_proyecto, Ítem: $post_plan_item";
        $pdo->prepare("INSERT INTO expedientes_historial (expediente_id, usuario_id, accion, estado_anterior, estado_nuevo, comentario) VALUES (?, ?, 'CREAR', 'BORRADOR', ?, ?)")->execute([$exp_id, $user_id, $estado_destino, $comentario_hist]);

        $pdo->commit();
        header("Location: mis_solicitudes.php?msg=Solicitud+creada+exitosamente&type=success");
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $mensaje = $e->getMessage();
    }
}
?>