<?php
// finanzas_controller.php - Controlador del Módulo de Finanzas (V1.0)
require_once __DIR__ . '/config.php';

// 1. SEGURIDAD Y SESIÓN
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

// SOLO ROL FINANZAS O ADMIN O SYSADMIN
$rol = $_SESSION['user_rol'] ?? '';
if ($rol !== 'FINANZAS' && $rol !== 'ADMIN_MUNICIPAL' && $rol !== 'SYSADMIN') {
    die("Acceso Denegado. Módulo exclusivo de Dirección de Administración y Finanzas (CDP).");
}

$user_id = $_SESSION['user_id'];
$mensaje = ''; $tipo_mensaje = '';
$vista = $_GET['view'] ?? 'pendientes'; // 'pendientes', 'procesados', 'revisar'

// =====================================================================
// MANEJO DE ACCIONES (POST) - MOTOR DE FLUJOS DINÁMICO
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $exp_id = isset($_POST['expediente_id']) ? (int)$_POST['expediente_id'] : null;
        $accion = $_POST['accion'] ?? '';
        $transicion_id = $_POST['transicion_id'] ?? null;
        
        $pdo->beginTransaction();

        $stmtEst = $pdo->prepare("SELECT estado_actual FROM expedientes WHERE id = ?");
        $stmtEst->execute([$exp_id]);
        $estado_actual = $stmtEst->fetchColumn();
        if ($estado_actual !== 'ESPERANDO_CDP_FINANZAS' && $estado_actual !== 'ESPERANDO_CDP_FINANZAS_FINAL') {
            throw new Exception("El expediente no se encuentra en estado de espera de CDP.");
        }

        // Si se recibe transicion_id, determinar la acción lógica
        $trans = null;
        if ($transicion_id) {
            $stmtT = $pdo->prepare("SELECT * FROM flujos_definicion WHERE id = ?");
            $stmtT->execute([$transicion_id]);
            $trans = $stmtT->fetch();
            if ($trans) {
                $accion = strtolower($trans['accion_codigo']); // 'aprobar', 'devolver', 'rechazar'
            }
        }

        // A. SUBIR CDP Y APROBAR (ENVIAR A ADMINISTRADOR)
        if ($accion === 'aprobar') {
            if (empty($_FILES['archivo_cdp']['name'])) {
                throw new Exception("Debe adjuntar obligatoriamente el Certificado de Disponibilidad Presupuestaria (CDP).");
            }

            // Validar y subir archivo
            $ext = strtolower(pathinfo($_FILES['archivo_cdp']['name'], PATHINFO_EXTENSION));
            if($ext !== 'pdf') throw new Exception("El archivo del CDP debe ser un PDF.");
            
            $anio = date('Y');
            $dir = __DIR__ . "/uploads/$anio/exp_$exp_id/";
            if (!file_exists($dir)) mkdir($dir, 0777, true);
            
            $name = "CDP_FIRMADO_FINANZAS_" . time() . ".pdf";
            $ruta = "uploads/$anio/exp_$exp_id/$name";
            
            if (!move_uploaded_file($_FILES['archivo_cdp']['tmp_name'], $dir . $name)) {
                throw new Exception("Error al guardar el archivo en el servidor.");
            }

            // Registrar Documento
            $pdo->prepare("INSERT INTO expedientes_documentos (expediente_id, subido_por_id, tipo_doc, ruta_archivo, nombre_original) VALUES (?, ?, 'CDP_FIRMADO_FINANZAS', ?, ?)")
                ->execute([$exp_id, $user_id, $ruta, $_FILES['archivo_cdp']['name']]);

            $comentario = "Certificado de Disponibilidad Presupuestaria (CDP) cargado exitosamente desde SMC por Finanzas.";

            if ($transicion_id) {
                $nuevo_estado = ejecutar_transicion_por_id($pdo, $exp_id, $user_id, $transicion_id, $comentario);
            } else {
                $nuevo_estado = avanzar_flujo($pdo, $exp_id, $user_id, $comentario);
            }

            $stmtNd = $pdo->prepare("SELECT nombre FROM estados_tramite WHERE codigo = ?");
            $stmtNd->execute([$nuevo_estado]);
            $nombre_dest = $stmtNd->fetchColumn();
            $mensaje = "CDP adjuntado con éxito. Requerimiento devuelto a: $nombre_dest.";
            $tipo_mensaje = "success";
            $vista = 'pendientes';
        } 
        
        // B. DEVOLVER / RECHAZAR
        elseif (in_array($accion, ['rechazar', 'devolver'])) {
            $motivo = trim($_POST['motivo_rechazo']);
            if (empty($motivo)) throw new Exception("Debe ingresar un motivo para la devolución u observación.");

            if ($transicion_id) {
                $nuevo_estado = ejecutar_transicion_por_id($pdo, $exp_id, $user_id, $transicion_id, $motivo);
                $stmtNd = $pdo->prepare("SELECT nombre FROM estados_tramite WHERE codigo = ?");
                $stmtNd->execute([$nuevo_estado]);
                $nombre_dest = $stmtNd->fetchColumn();
                $mensaje = "Acción procesada correctamente. Requerimiento enviado a: $nombre_dest.";
            } else {
                if ($accion === 'rechazar') {
                    rechazar_flujo($pdo, $exp_id, $user_id, "Rechazado por Finanzas: " . $motivo);
                    $mensaje = "Solicitud rechazada definitivamente por Finanzas.";
                } else {
                    devolver_flujo($pdo, $exp_id, $user_id, "Devuelto por Finanzas: " . $motivo);
                    $mensaje = "Solicitud devuelta para corrección.";
                }
            }
            $tipo_mensaje = ($accion === 'devolver') ? 'warning' : 'error';
            $vista = 'pendientes';
        }

        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// =====================================================================
// CONSULTAS GET (CARGA DE VISTAS)
// =====================================================================

$q_buscar = $_GET['q'] ?? '';

// BANDEJA PENDIENTES
if ($vista === 'pendientes') {
    $sql = "
        SELECT e.*, u.nombre_completo as solicitante, un.nombre as unidad, p.nombre as prioridad_nom, p.clase_css, tc.nombre as tipo_compra_nom, et.nombre as estado_nombre 
        FROM expedientes e 
        JOIN usuarios u ON e.usuario_creador_id = u.id 
        JOIN unidades un ON e.unidad_origen_id = un.id 
        JOIN prioridades p ON e.prioridad_id = p.id 
        JOIN tipos_compra tc ON e.tipo_compra_id = tc.id 
        JOIN estados_tramite et ON e.estado_actual = et.codigo
        WHERE e.estado_actual IN ('ESPERANDO_CDP_FINANZAS', 'ESPERANDO_CDP_FINANZAS_FINAL')
    ";
    if ($q_buscar) { $sql .= " AND (e.codigo_interno LIKE '%$q_buscar%' OR e.titulo_compra LIKE '%$q_buscar%')"; }
    $sql .= " ORDER BY p.id DESC, e.created_at ASC";
    $pendientes = $pdo->query($sql)->fetchAll();
}

// BANDEJA PROCESADOS (Historial)
if ($vista === 'procesados') {
    $sql = "
        SELECT e.*, u.nombre_completo as solicitante, un.nombre as unidad, p.nombre as prioridad_nom, p.clase_css, tc.nombre as tipo_compra_nom, et.nombre as estado_nombre 
        FROM expedientes e 
        JOIN usuarios u ON e.usuario_creador_id = u.id 
        JOIN unidades un ON e.unidad_origen_id = un.id 
        JOIN prioridades p ON e.prioridad_id = p.id 
        JOIN tipos_compra tc ON e.tipo_compra_id = tc.id 
        JOIN estados_tramite et ON e.estado_actual = et.codigo
        JOIN expedientes_historial eh ON e.id = eh.expediente_id
        WHERE eh.usuario_id = $user_id AND eh.accion IN ('APROBAR', 'RECHAZAR', 'DEVOLVER') AND eh.estado_anterior IN ('ESPERANDO_CDP_FINANZAS', 'ESPERANDO_CDP_FINANZAS_FINAL')
    ";
    if ($q_buscar) { $sql .= " AND (e.codigo_interno LIKE '%$q_buscar%' OR e.titulo_compra LIKE '%$q_buscar%')"; }
    $sql .= " GROUP BY e.id ORDER BY eh.fecha_accion DESC LIMIT 50";
    $procesados = $pdo->query($sql)->fetchAll();
}

// DETALLE DE REVISIÓN
if ($vista === 'revisar' && isset($_GET['id'])) {
    $stmtHead = $pdo->prepare("
        SELECT e.*, u.nombre_completo as solicitante, un.nombre as unidad, cc.nombre as centro_costo, tc.nombre as tipo_compra_nom, tc.codigo as tipo_compra_cod, p.nombre as prioridad_nom, p.clase_css, et.nombre as estado_nombre, et.rol_responsable, prov.razon_social as proveedor_nombre, prov.rut as proveedor_rut
        FROM expedientes e
        JOIN usuarios u ON e.usuario_creador_id = u.id
        JOIN unidades un ON e.unidad_origen_id = un.id
        JOIN centros_costo cc ON e.centro_costo_id = cc.id
        JOIN tipos_compra tc ON e.tipo_compra_id = tc.id
        JOIN prioridades p ON e.prioridad_id = p.id
        JOIN estados_tramite et ON e.estado_actual = et.codigo
        LEFT JOIN proveedores prov ON e.proveedor_adjudicado_id = prov.id
        WHERE e.id = ?
    ");
    $stmtHead->execute([$_GET['id']]);
    $expediente = $stmtHead->fetch();
    if (!$expediente) die("Expediente no encontrado.");

    $es_accionable = ($expediente['estado_actual'] === 'ESPERANDO_CDP_FINANZAS' || $expediente['estado_actual'] === 'ESPERANDO_CDP_FINANZAS_FINAL');

    $stmtItems = $pdo->prepare("
        SELECT ei.*, cm.codigo as cuenta_codigo, cm.nombre as cuenta_nombre, ag.codigo as ag_codigo
        FROM expedientes_items ei
        JOIN presupuestos_asignados pa ON ei.presupuesto_asignado_id = pa.id
        JOIN cuentas_maestras cm ON pa.cuenta_maestra_id = cm.id
        LEFT JOIN areas_gestion ag ON pa.area_gestion_id = ag.id
        WHERE ei.expediente_id = ?
    ");
    $stmtItems->execute([$_GET['id']]);
    $items = $stmtItems->fetchAll();

    $stmtDocs = $pdo->prepare("SELECT * FROM expedientes_documentos WHERE expediente_id = ? ORDER BY fecha_subida DESC");
    $stmtDocs->execute([$_GET['id']]);
    $docs = $stmtDocs->fetchAll();
}

function money($v) { return '$ ' . number_format($v, 0, ',', '.'); }
?>
