<?php
// editar_solicitud_controller.php - Lógica de Negocio (V5.0 - Sincronizado con Nueva Solicitud y Plan Compras)
require_once __DIR__ . '/config.php';

// 1. SEGURIDAD
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];
$es_jefe = $_SESSION['es_jefe'] ?? 0;
$id = $_GET['id'] ?? ($_POST['id'] ?? null);
$mensaje = '';

// 2. CARGAR DATOS INICIALES
if ($id) {
    // Verificar propiedad (incluyendo subrogante activo) y estado editable
    $user_ids = [$user_id];
    if (!empty($_SESSION['es_subrogante']) && !empty($_SESSION['subrogado_id'])) {
        $user_ids[] = (int)$_SESSION['subrogado_id'];
    }
    $in_clause = implode(',', array_map('intval', $user_ids));

    $stmt = $pdo->prepare("
        SELECT e.*, cc.nombre as cc_nombre 
        FROM expedientes e 
        LEFT JOIN centros_costo cc ON e.centro_costo_id = cc.id
        WHERE e.id = ? AND e.usuario_creador_id IN ($in_clause) AND e.estado_actual IN ('BORRADOR', 'EN_CORRECCION', 'EN_REVISION_JEFATURA')
    ");
    $stmt->execute([$id]);
    $exp = $stmt->fetch();

    if (!$exp) {
        die("Solicitud no encontrada o no editable. <a href='mis_solicitudes.php' class='text-blue-600 underline'>Volver</a>");
    }

    // Cargar Items desde la DB
    $stmtItems = $pdo->prepare("SELECT * FROM expedientes_items WHERE expediente_id = ?");
    $stmtItems->execute([$id]);
    $db_items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    // Cargar Criterios
    $stmtCriterios = $pdo->prepare("SELECT * FROM expedientes_criterios WHERE expediente_id = ? ORDER BY numero_criterio ASC");
    $stmtCriterios->execute([$id]);
    $db_criterios = $stmtCriterios->fetchAll(PDO::FETCH_ASSOC);

    // Cargar Documentos previos
    $stmtDocs = $pdo->prepare("SELECT * FROM expedientes_documentos WHERE expediente_id = ? ORDER BY fecha_subida DESC");
    $stmtDocs->execute([$id]);
    $documentos_existentes = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

    // Cargar Historial (motivo devolución)
    $stmtHist = $pdo->prepare("SELECT * FROM expedientes_historial WHERE expediente_id = ? ORDER BY fecha_accion DESC LIMIT 3");
    $stmtHist->execute([$id]);
    $historial = $stmtHist->fetchAll();

    // Cargar Cuentas del Centro de Costo de la solicitud
    $stmtC = $pdo->prepare("
        SELECT pa.id, cm.codigo, cm.nombre, ag.codigo as ag_codigo 
        FROM presupuestos_asignados pa
        JOIN cuentas_maestras cm ON pa.cuenta_maestra_id = cm.id
        LEFT JOIN areas_gestion ag ON pa.area_gestion_id = ag.id
        WHERE pa.centro_costo_id = ?
        ORDER BY cm.codigo ASC
    ");
    $stmtC->execute([$exp['centro_costo_id']]);
    $cuentas_disponibles = $stmtC->fetchAll(PDO::FETCH_ASSOC);

    $tipos_compra = $pdo->query("SELECT * FROM tipos_compra WHERE activo = 1 ORDER BY nombre ASC")->fetchAll();
    $prioridades = $pdo->query("SELECT * FROM prioridades WHERE activo = 1")->fetchAll();
    $rangos_utm = $pdo->query("SELECT * FROM rangos_utm WHERE activo = 1 ORDER BY min_utm ASC")->fetchAll();
    // Proveedores Frecuentes del Usuario
    $stmtMisProv = $pdo->prepare("
        SELECT p.* FROM proveedores p
        INNER JOIN expedientes e ON e.proveedor_adjudicado_id = p.id
        WHERE e.usuario_creador_id = ?
        GROUP BY p.id ORDER BY p.razon_social ASC
    ");
    $stmtMisProv->execute([$user_id]);
    $mis_proveedores = $stmtMisProv->fetchAll();

    // Todos los proveedores
    $otros_proveedores = $pdo->query("SELECT * FROM proveedores ORDER BY razon_social ASC")->fetchAll();

    $mapa_tipos = [];
    $mapa_requiere_cotizacion = [];
    foreach($tipos_compra as $tc) {
        $mapa_tipos[$tc['id']] = $tc['codigo'];
        $mapa_requiere_cotizacion[$tc['id']] = (int)$tc['requiere_cotizacion'];
    }

} else {
    die("ID no especificado.");
}

// 3. VARIABLES PARA EL FORMULARIO (Carga inicial desde la BD)
$post_titulo_compra = $exp['titulo_compra'] ?? '';
$post_motivo = $exp['motivo_compra'] ?? '';
$post_tipo_compra = $exp['tipo_compra_id'] ?? '';
$post_prioridad = $exp['prioridad_id'] ?? '';
$post_rango_utm = $exp['rango_utm_id'] ?? '';
$post_proveedor_id = $exp['proveedor_adjudicado_id'] ?? '';
$post_id_contrato_suministro = $exp['id_contrato_suministro'] ?? '';
$post_plan_proyecto = $exp['plan_compras_proyecto'] ?? '';
$post_plan_item = $exp['plan_compras_item'] ?? '';
$post_tipo_impuesto = 'NETO'; // Default UI

$requiere_cot_inicial = 0;
$stmtTCI = $pdo->prepare("SELECT requiere_cotizacion FROM tipos_compra WHERE id = ?");
$stmtTCI->execute([$post_tipo_compra]);
$requiere_cot_inicial = (int) $stmtTCI->fetchColumn();

$post_monto_disponible_neto = '';
if ($requiere_cot_inicial) {
    $post_monto_disponible_neto = round($exp['monto_estimado'] / 1.19);
}

$items_old = [];
$criterios_old = [];

// Mapear DB items para JS
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    foreach($db_items as $it) {
        $prec_neto = round(floatval($it['precio_unitario']) / 1.19, 2);
        $items_old[] = [
            'desc' => $it['descripcion'],
            'id_cm' => $it['id_producto_cm'] ?? '',
            'uni' => $it['unidad_medida'],
            'cant' => $it['cantidad'],
            'prec' => $prec_neto, // Convertido de bruto (BD) a neto para que al re-guardar * 1.19 quede exacto
            'cuenta_id' => $it['presupuesto_asignado_id']
        ];
    }
    foreach($db_criterios as $cr) {
        $criterios_old[] = [
            'desc' => $cr['descripcion'],
            'porc' => $cr['porcentaje']
        ];
    }
}

// 4. PROCESAR ACTUALIZACIÓN (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        die("Token de seguridad CSRF no válido o sesión expirada.");
    }
    
    // Rescatar POST
    $post_titulo_compra = trim($_POST['titulo_compra'] ?? '');
    $post_motivo = trim($_POST['motivo'] ?? '');
    $post_tipo_compra = $_POST['tipo_compra_id'] ?? '';
    $post_prioridad = $_POST['prioridad_id'] ?? '';
    $post_rango_utm = $_POST['rango_utm_id'] ?? '';
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
    $post_id_contrato_suministro = trim($_POST['id_contrato_suministro'] ?? '');
    $post_plan_proyecto = trim($_POST['plan_compras_proyecto'] ?? '');
    $post_plan_item = trim($_POST['plan_compras_item'] ?? '');
    $post_tipo_impuesto = $_POST['tipo_impuesto'] ?? 'NETO';
    
    $desc = $_POST['desc'] ?? [];
    $id_cm = $_POST['id_producto_cm'] ?? [];
    $uni = $_POST['uni'] ?? [];
    $cant = $_POST['cant'] ?? [];
    $prec = $_POST['prec'] ?? [];
    $cuenta_ids = $_POST['cuenta_id'] ?? [];

    $items_old = [];
    for ($i = 0; $i < count($desc); $i++) {
        $items_old[] = ['desc'=>$desc[$i]??'','id_cm'=>$id_cm[$i]??'','uni'=>$uni[$i]??'UNIDAD','cant'=>$cant[$i]??1,'prec'=>$prec[$i]??0,'cuenta_id'=>$cuenta_ids[$i]??''];
    }

    $criterios_old = [];
    if (isset($_POST['crit_desc'])) {
        foreach ($_POST['crit_desc'] as $i => $cdesc) {
            $criterios_old[] = ['desc' => $cdesc, 'porc' => $_POST['crit_porc'][$i] ?? 0];
        }
    }

    try {
        if (empty($desc) || empty($desc[0])) throw new Exception("Debe ingresar al menos un ítem.");

        $pdo->beginTransaction();

        $tipo_compra_id = $_POST['tipo_compra_id'];
        $codigo_tc = $mapa_tipos[$tipo_compra_id] ?? '';
        
        // --- CALCULAR NUEVO ESTADO DE DESTINO ---
        $stmtFlujo = $pdo->prepare("SELECT estado_destino FROM flujos_definicion WHERE tipo_compra_id = ? AND estado_actual = 'BORRADOR'");
        $stmtFlujo->execute([$tipo_compra_id]);
        $estado_destino = $stmtFlujo->fetchColumn();

        if (!$estado_destino) throw new Exception("Error: No hay flujo definido para este tipo de compra.");

        // Salto Jefe
        if ($es_jefe == 1 && $estado_destino === 'EN_REVISION_JEFATURA') {
            $stmtF2 = $pdo->prepare("SELECT estado_destino FROM flujos_definicion WHERE tipo_compra_id = ? AND estado_actual = ?");
            $stmtF2->execute([$tipo_compra_id, $estado_destino]);
            if ($sig = $stmtF2->fetchColumn()) $estado_destino = $sig;
        }

        // --- VALIDACIONES POR TIPO ---
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

        // --- CALCULAR TOTAL ---
        $stmtTC = $pdo->prepare("SELECT requiere_cotizacion FROM tipos_compra WHERE id = ?");
        $stmtTC->execute([$tipo_compra_id]);
        $requiere_cot = (int) $stmtTC->fetchColumn();

        $iva_pct = 1.19;
        $total_est_bruto = 0;

        if ($requiere_cot) {
            $monto_disp_neto = floatval($_POST['monto_disponible_neto'] ?? 0);
            $total_est_bruto = $monto_disp_neto * $iva_pct;
        } else {
            foreach ($cant as $i => $c) {
                $p_unit = floatval($prec[$i]);
                $p_unit *= $iva_pct; // Forzar cálculo en neto
                $total_est_bruto += (floatval($c) * $p_unit);
            }
        }

        // --- UPDATE CABECERA ---
        $stmtUpd = $pdo->prepare("UPDATE expedientes SET titulo_compra=?, motivo_compra=?, tipo_compra_id=?, prioridad_id=?, rango_utm_id=?, proveedor_adjudicado_id=?, id_contrato_suministro=?, plan_compras_proyecto=?, plan_compras_item=?, monto_estimado=?, estado_actual=? WHERE id=?");
        $stmtUpd->execute([$post_titulo_compra, $post_motivo, $tipo_compra_id, $post_prioridad, $post_rango_utm, $post_proveedor_id, $post_id_contrato_suministro, $post_plan_proyecto, $post_plan_item, $total_est_bruto, $estado_destino, $id]);

        // Subir Ficha de Proveedor si es nuevo
        if (isset($_FILES['ficha_proveedor']) && $_FILES['ficha_proveedor']['error'] === UPLOAD_ERR_OK) {
            $ext_ficha = validar_subida_archivo($_FILES['ficha_proveedor']);
            if ($ext_ficha) {
                $anio_actual = date('Y');
                $dir = __DIR__ . "/uploads/$anio_actual/exp_$id/";
                if (!file_exists($dir)) {
                    if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
                        throw new Exception("Error de permisos: No se pudo crear el directorio de destino en '$dir'. Verifique los permisos de /app/uploads.");
                    }
                }
                $nombre_final_ficha = "ficha_prov_" . time() . "." . $ext_ficha;
                if (move_uploaded_file($_FILES['ficha_proveedor']['tmp_name'], $dir . $nombre_final_ficha)) {
                    $ruta_ficha = "uploads/$anio_actual/exp_$id/" . $nombre_final_ficha;
                    $pdo->prepare("INSERT INTO expedientes_documentos (expediente_id, subido_por_id, tipo_doc, ruta_archivo, nombre_original) VALUES (?, ?, 'FICHA_PROVEEDOR', ?, ?)")
                        ->execute([$id, $user_id, $ruta_ficha, $_FILES['ficha_proveedor']['name']]);
                }
            }
        }

        // --- RE-INSERTAR ITEMS ---
        $pdo->prepare("DELETE FROM expedientes_items WHERE expediente_id = ?")->execute([$id]);
        $stmtItem = $pdo->prepare("INSERT INTO expedientes_items (expediente_id, descripcion, id_producto_cm, unidad_medida, cantidad, precio_unitario, presupuesto_asignado_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($desc as $i => $d) {
            if ($requiere_cot) {
                $p_final = 0;
            } else {
                $p_final = floatval($prec[$i]) * $iva_pct; // Forzado a neto
            }
            $val_cm = ($codigo_tc === 'CONVENIO_MARCO') ? trim($id_cm[$i] ?? '') : null; 
            $stmtItem->execute([$id, $d, $val_cm, $uni[$i], floatval($cant[$i]), $p_final, $cuenta_ids[$i]]);
        }

        // --- RE-INSERTAR CRITERIOS ---
        $pdo->prepare("DELETE FROM expedientes_criterios WHERE expediente_id = ?")->execute([$id]);
        if ($codigo_tc === 'LICITACION' && isset($_POST['crit_num'])) {
            $stmtCrit = $pdo->prepare("INSERT INTO expedientes_criterios (expediente_id, numero_criterio, descripcion, porcentaje) VALUES (?, ?, ?, ?)");
            foreach ($_POST['crit_num'] as $i => $num) {
                $stmtCrit->execute([$id, $num, $_POST['crit_desc'][$i], floatval($_POST['crit_porc'][$i])]);
            }
        }

        // --- SUBIR NUEVOS ARCHIVOS (Correcciones) ---
        if (isset($_FILES['archivo_adjunto']) && $_FILES['archivo_adjunto']['error'] !== UPLOAD_ERR_NO_FILE) {
            $ext = validar_subida_archivo($_FILES['archivo_adjunto']);
            if ($ext) {
                $file = $_FILES['archivo_adjunto'];
                $anio = date('Y');
                $dir = __DIR__ . "/uploads/$anio/exp_$id/";
                if (!file_exists($dir)) {
                    if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
                        throw new Exception("Error de permisos: No se pudo crear el directorio de destino en '$dir'. Verifique los permisos de /app/uploads.");
                    }
                }
                
                $nombre_final = "adj_corregido_" . time() . "." . $ext;
                if(move_uploaded_file($file['tmp_name'], $dir . $nombre_final)){
                    $ruta = "uploads/$anio/exp_$id/" . $nombre_final;
                    $pdo->prepare("INSERT INTO expedientes_documentos (expediente_id, subido_por_id, tipo_doc, ruta_archivo, nombre_original) VALUES (?, ?, 'OTRO', ?, ?)")->execute([$id, $user_id, $ruta, $file['name']]);
                }
            }
        }

        // --- HISTORIAL ---
        $comentario_hist = "Solicitud corregida y re-enviada al flujo. Proyecto: $post_plan_proyecto";
        $pdo->prepare("INSERT INTO expedientes_historial (expediente_id, usuario_id, accion, estado_anterior, estado_nuevo, comentario) VALUES (?, ?, 'CORREGIR', ?, ?, ?)")
            ->execute([$id, $user_id, $exp['estado_actual'], $estado_destino, $comentario_hist]);

        $pdo->commit();
        header("Location: mis_solicitudes.php?msg=Solicitud+corregida+y+enviada&type=success");
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $mensaje = $e->getMessage();
    }
}
?>