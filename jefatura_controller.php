<?php
// jefatura_controller.php - Lógica de Negocio (V5.0 - Homologado con mis_solicitudes.php)
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$es_jefe = $_SESSION['es_jefe'] ?? 0;
$rol = $_SESSION['user_rol'] ?? '';

if ($es_jefe != 1 && $rol !== 'JEFE_UNIDAD' && $rol !== 'ADMIN_MUNICIPAL' && $rol !== 'SYSADMIN') {
    die("Acceso Denegado. Este módulo es exclusivo para Jefaturas de Unidad.");
}

$unidad_id = $_SESSION['user_unidad'] ?? 0;
$user_id = $_SESSION['user_id'];
$mensaje = '';
$tipo_mensaje = '';

$vista = $_GET['view'] ?? 'pendientes'; // 'pendientes', 'procesadas', 'todas', 'revisar'

// PARÁMETROS DE FILTRO (GET)
$f_q      = trim($_GET['f_q'] ?? '');
$f_tipo   = trim($_GET['f_tipo'] ?? '');
$f_estado = trim($_GET['f_estado'] ?? '');
$f_desde  = trim($_GET['f_desde'] ?? '');
$f_hasta  = trim($_GET['f_hasta'] ?? '');

// =====================================================================
// MANEJO DE ACCIONES (POST) - MOTOR DE FLUJOS DINÁMICO
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        $id = isset($_POST['expediente_id']) ? (int)$_POST['expediente_id'] : null;

        // Obtener estado y validar pertenencia
        $sqlCheck = "SELECT id, estado_actual, codigo_interno FROM expedientes WHERE id = ?";
        if ($rol !== 'ADMIN_MUNICIPAL') { $sqlCheck .= " AND unidad_origen_id = $unidad_id"; }
        
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$id]);
        $exp_actual = $stmtCheck->fetch();
        if (!$exp_actual) throw new Exception("El expediente no se encuentra disponible para visación o firma por la Jefatura.");

        $accion = $_POST['accion'] ?? '';
        $transicion_id = $_POST['transicion_id'] ?? null;
        $stAct = $exp_actual['estado_actual'];

        // A. FIRMA DIGITAL FIRMAGOB (1/3) O SUBIDA MANUAL EN ESTADO EN_FIRMA_JEFATURA
        if ($accion === 'firmar_firmagob' || $accion === 'subir_pdf_manual' || ($stAct === 'EN_FIRMA_JEFATURA' && ($accion === 'aprobar' || $accion === 'firmar'))) {
            $anio = date('Y');
            $dir = __DIR__ . "/uploads/$anio/exp_$id/";
            if (!file_exists($dir)) mkdir($dir, 0777, true);

            $nombre_firmado = "OPI_FIRMADA_1_" . time() . ".pdf";
            $ruta_firmado_abs = $dir . $nombre_firmado;
            $ruta_firmado_rel = "uploads/$anio/exp_$id/" . $nombre_firmado;

            $firmante = firmagob_obtener_firmante_activo($pdo, 'JEFE_UNIDAD', $user_id);
            $run_firmante = $firmante['rut'] ?? $_SESSION['user_rut'];

            if ($accion === 'subir_pdf_manual') {
                if (empty($_FILES['pdf_firmado_manual']['name'])) {
                    throw new Exception("Debe seleccionar el archivo PDF firmado manualmente.");
                }
                $ext = validar_subida_archivo($_FILES['pdf_firmado_manual'], null, ['pdf']);
                if (!move_uploaded_file($_FILES['pdf_firmado_manual']['tmp_name'], $ruta_firmado_abs)) {
                    throw new Exception("Error al guardar el archivo PDF firmado.");
                }
                $id_solicitud = null;
                $chk_orig = null;
                $chk_signed = hash_file('sha256', $ruta_firmado_abs);
                $tipo_firma = 'MANUAL_DOCDIGITAL';
            } else {
                // Firma Digital con FirmaGob
                $otp = $_POST['otp_code'] ?? null;
                
                // Buscar PDF Base de entrada
                $ruta_base = null;
                $stmtDoc = $pdo->prepare("SELECT ruta_archivo FROM expedientes_documentos WHERE expediente_id = ? AND tipo_doc = 'OPI_FIRMADA_PDF' ORDER BY id DESC LIMIT 1");
                $stmtDoc->execute([$id]);
                $doc_db = $stmtDoc->fetchColumn();
                
                if ($doc_db && file_exists(__DIR__ . '/' . $doc_db)) {
                    $ruta_base = __DIR__ . '/' . $doc_db;
                } else {
                    $ruta_rel_creada = generar_pdf_base_opi($pdo, $id);
                    $ruta_base = __DIR__ . '/' . $ruta_rel_creada;
                }

                $resFirma = firmagob_firmar_archivo($ruta_base, $run_firmante, "OPI " . $exp_actual['codigo_interno'] . " (Firma Jefatura 1/3)", $otp, 'JEFATURA');
                file_put_contents($ruta_firmado_abs, $resFirma['content_binary']);

                $id_solicitud = $resFirma['id_solicitud'];
                $chk_orig = $resFirma['checksum_original'];
                $chk_signed = $resFirma['checksum_signed'];
                $tipo_firma = (FIRMAGOB_MODO === 'DESATENDIDA') ? 'FIRMAGOB_DESATENDIDA' : 'FIRMAGOB_ATENDIDA';
            }

            // Registrar / Actualizar Documento OPI único oficial
            registrar_o_actualizar_opi_documento($pdo, $id, $user_id, $ruta_firmado_rel, $nombre_firmado);

            $pdo->prepare("INSERT INTO expedientes_firmas (expediente_id, usuario_firmante_id, autoridad_id, nombre_firmante, rut_firmante, cargo_firmante, etapa_firma, firmagob_solicitud_id, checksum_original, checksum_signed, tipo_firma, ip_origen, nombre_archivo_firmado) VALUES (?, ?, ?, ?, ?, ?, 'JEFATURA', ?, ?, ?, ?, ?, ?)")
                ->execute([
                    $id,
                    $user_id,
                    $firmante['id'] ?? null,
                    $firmante['nombre'] ?? $_SESSION['user_nombre'] ?? 'Jefe de Unidad',
                    $run_firmante,
                    $firmante['cargo'] ?? 'JEFE DE UNIDAD',
                    $id_solicitud,
                    $chk_orig,
                    $chk_signed,
                    $tipo_firma,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $nombre_firmado
                ]);

            if ($transicion_id) {
                $nuevo_destino = ejecutar_transicion_por_id($pdo, $id, $user_id, $transicion_id, "OPI firmada digitalmente por Jefatura (1/3).");
            } else {
                $nuevo_destino = avanzar_flujo($pdo, $id, $user_id, "OPI firmada digitalmente por Jefatura (1/3).");
            }

            $stmtNd = $pdo->prepare("SELECT nombre FROM estados_tramite WHERE codigo = ?");
            $stmtNd->execute([$nuevo_destino]);
            $nombre_dest = $stmtNd->fetchColumn();

            $mensaje = "OPI firmada digitalmente con éxito (1/3). Trámite avanzado a: $nombre_dest.";
            $tipo_mensaje = "success";
            $vista = 'pendientes';

        } elseif ($transicion_id) {
            $motivo = trim($_POST['motivo_rechazo'] ?? '');
            
            $stmtT = $pdo->prepare("SELECT * FROM flujos_definicion WHERE id = ?");
            $stmtT->execute([$transicion_id]);
            $trans = $stmtT->fetch();
            
            if ($trans && ($trans['accion_codigo'] === 'DEVOLVER' || $trans['accion_codigo'] === 'RECHAZAR' || $trans['requiere_comentario']) && empty($motivo)) {
                throw new Exception("Debe ingresar un motivo o comentario para ejecutar esta acción.");
            }

            $nuevo_destino = ejecutar_transicion_por_id($pdo, $id, $user_id, $transicion_id, $motivo);
            $stmtNd = $pdo->prepare("SELECT nombre FROM estados_tramite WHERE codigo = ?");
            $stmtNd->execute([$nuevo_destino]);
            $nombre_dest = $stmtNd->fetchColumn();
            
            $mensaje = "Acción '" . htmlspecialchars($trans['accion_label']) . "' procesada correctamente. Solicitud enviada a: $nombre_dest.";
            $tipo_mensaje = ($trans['accion_codigo'] === 'APROBAR') ? 'success' : (($trans['accion_codigo'] === 'DEVOLVER') ? 'warning' : 'error');

        } elseif ($accion === 'aprobar') {
            $nuevo_destino = avanzar_flujo($pdo, $id, $user_id, "V°B° Jefatura completado.");
            $stmtNd = $pdo->prepare("SELECT nombre FROM estados_tramite WHERE codigo = ?");
            $stmtNd->execute([$nuevo_destino]);
            $nombre_dest = $stmtNd->fetchColumn();
            $mensaje = "Solicitud aprobada correctamente. Avanzó a: $nombre_dest.";
            $tipo_mensaje = "success";
        } elseif ($accion === 'devolver') {
            $motivo = trim($_POST['motivo_rechazo'] ?? '');
            if (empty($motivo)) throw new Exception("Debe ingresar un motivo para la devolución.");
            devolver_flujo($pdo, $id, $user_id, "Devuelto para corrección: " . $motivo);
            $mensaje = "Solicitud devuelta al creador para corrección.";
            $tipo_mensaje = "warning";
        } elseif ($accion === 'rechazar') {
            $motivo = trim($_POST['motivo_rechazo'] ?? '');
            if (empty($motivo)) throw new Exception("Debe ingresar un motivo para el rechazo.");
            rechazar_flujo($pdo, $id, $user_id, "Rechazado definitivamente: " . $motivo);
            $mensaje = "Solicitud rechazada y cerrada.";
            $tipo_mensaje = "error";
        }

        $pdo->commit();
        $vista = 'pendientes';

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// =====================================================================
// CONSULTAS GET (CARGA DE VISTAS)
// =====================================================================

// CONTADORES PARA LAS PESTAÑAS HEADER
$stmtCountPend = $pdo->prepare(($rol === 'ADMIN_MUNICIPAL') ? "SELECT COUNT(*) FROM expedientes WHERE estado_actual IN ('EN_REVISION_JEFATURA', 'EN_FIRMA_JEFATURA')" : "SELECT COUNT(*) FROM expedientes WHERE unidad_origen_id = ? AND estado_actual IN ('EN_REVISION_JEFATURA', 'EN_FIRMA_JEFATURA')");
if ($rol === 'ADMIN_MUNICIPAL') { $stmtCountPend->execute(); } else { $stmtCountPend->execute([$unidad_id]); }
$count_pendientes = $stmtCountPend->fetchColumn();

$stmtCountProc = $pdo->prepare("SELECT COUNT(DISTINCT e.id) FROM expedientes e JOIN expedientes_historial eh ON e.id = eh.expediente_id WHERE eh.usuario_id = ? AND eh.accion IN ('APROBAR', 'RECHAZAR', 'DEVOLVER', 'FIRMAR_JEFATURA')");
$stmtCountProc->execute([$user_id]);
$count_procesadas = $stmtCountProc->fetchColumn();

$stmtCountTodas = $pdo->prepare(($rol === 'ADMIN_MUNICIPAL') ? "SELECT COUNT(*) FROM expedientes" : "SELECT COUNT(*) FROM expedientes WHERE unidad_origen_id = ?");
if ($rol === 'ADMIN_MUNICIPAL') { $stmtCountTodas->execute(); } else { $stmtCountTodas->execute([$unidad_id]); }
$count_todas = $stmtCountTodas->fetchColumn();

if ($vista === 'revisar' && isset($_GET['id'])) {
    
    $sql = "
        SELECT e.*, u.nombre_completo as solicitante, un.nombre as unidad, cc.nombre as centro_costo, p.nombre as prioridad_nom, p.clase_css, tc.nombre as tipo_compra_nom, tc.codigo as tipo_compra_cod, prov.razon_social as proveedor_nombre, prov.rut as proveedor_rut, et.nombre as estado_nombre, et.rol_responsable
        FROM expedientes e 
        JOIN usuarios u ON e.usuario_creador_id = u.id 
        JOIN unidades un ON e.unidad_origen_id = un.id
        JOIN centros_costo cc ON e.centro_costo_id = cc.id 
        JOIN prioridades p ON e.prioridad_id = p.id 
        JOIN tipos_compra tc ON e.tipo_compra_id = tc.id 
        JOIN estados_tramite et ON e.estado_actual = et.codigo
        LEFT JOIN proveedores prov ON e.proveedor_adjudicado_id = prov.id
        WHERE e.id = ?
    ";
    if ($rol !== 'ADMIN_MUNICIPAL') { $sql .= " AND e.unidad_origen_id = $unidad_id"; }
    $stmt = $pdo->prepare($sql); 
    $stmt->execute([$_GET['id']]); 
    $exp = $stmt->fetch();
    $expediente = $exp;
    
    $es_accionable = in_array($exp['estado_actual'], ['EN_REVISION_JEFATURA', 'EN_FIRMA_JEFATURA']);
    
    // Ítems con su cuenta presupuestaria
    $stmtItems = $pdo->prepare("
        SELECT ei.*, cm.codigo as cuenta_codigo 
        FROM expedientes_items ei 
        LEFT JOIN presupuestos_asignados pa ON ei.presupuesto_asignado_id = pa.id 
        LEFT JOIN cuentas_maestras cm ON pa.cuenta_maestra_id = cm.id 
        WHERE ei.expediente_id = ?
    "); 
    $stmtItems->execute([$_GET['id']]); 
    $items = $stmtItems->fetchAll();

    $stmtCrit = $pdo->prepare("SELECT * FROM expedientes_criterios WHERE expediente_id = ? ORDER BY numero_criterio ASC");
    $stmtCrit->execute([$_GET['id']]);
    $criterios = $stmtCrit->fetchAll();
    
    $stmtDocs = $pdo->prepare("SELECT * FROM expedientes_documentos WHERE expediente_id = ? ORDER BY fecha_subida DESC");
    $stmtDocs->execute([$_GET['id']]);
    $docs = $stmtDocs->fetchAll();
} else {
    // VISTA TABLA: LISTADO CON TABS Y FILTROS
    $page = max(1, (int)($_GET['p'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $where = [];
    $params = [];

    if ($rol !== 'ADMIN_MUNICIPAL') {
        $where[] = "e.unidad_origen_id = :uid_origen";
        $params[':uid_origen'] = $unidad_id;
    }

    if ($vista === 'procesadas') {
        $where[] = "EXISTS (SELECT 1 FROM expedientes_historial eh WHERE eh.expediente_id = e.id AND eh.usuario_id = :hist_uid AND eh.accion IN ('APROBAR', 'RECHAZAR', 'DEVOLVER'))";
        $params[':hist_uid'] = $user_id;
    } elseif ($vista === 'todas') {
        // Sin condición adicional de estado
    } else {
        // 'pendientes' por defecto
        $where[] = "e.estado_actual = 'EN_REVISION_JEFATURA'";
        $vista = 'pendientes';
    }

    if ($f_q) {
        $where[] = "(e.codigo_interno LIKE :q OR e.motivo_compra LIKE :q OR e.titulo_compra LIKE :q)";
        $params[':q'] = "%$f_q%";
    }
    if ($f_tipo) { $where[] = "e.tipo_compra_id = :tipo"; $params[':tipo'] = $f_tipo; }
    if ($f_estado) { $where[] = "e.estado_actual = :est"; $params[':est'] = $f_estado; }
    if ($f_desde) { $where[] = "DATE(e.created_at) >= :desde"; $params[':desde'] = $f_desde; }
    if ($f_hasta) { $where[] = "DATE(e.created_at) <= :hasta"; $params[':hasta'] = $f_hasta; }

    $where_sql = implode(" AND ", $where);
    if ($where_sql) $where_sql = "WHERE " . $where_sql;

    $stmtTotal = $pdo->prepare("SELECT COUNT(DISTINCT e.id) FROM expedientes e $where_sql");
    $stmtTotal->execute($params);
    $total_records = $stmtTotal->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    $sqlLista = "
        SELECT 
            e.*, 
            u.nombre_completo as solicitante,
            un.nombre as unidad_nombre,
            tc.nombre as tipo_compra_nom,
            p.nombre as prioridad_nombre,
            p.clase_css as prioridad_css,
            cc.nombre as cc_nombre,
            et.nombre as estado_nombre,
            (SELECT GROUP_CONCAT(CONCAT(ruta_archivo, '::', IFNULL(nombre_original, 'Adjunto'), '::', tipo_doc, '::', DATE_FORMAT(fecha_subida, '%d/%m/%Y %H:%i')) SEPARATOR '||') 
             FROM expedientes_documentos ed WHERE ed.expediente_id = e.id) as docs_adjuntos
        FROM expedientes e
        JOIN usuarios u ON e.usuario_creador_id = u.id
        JOIN unidades un ON e.unidad_origen_id = un.id
        JOIN tipos_compra tc ON e.tipo_compra_id = tc.id
        JOIN prioridades p ON e.prioridad_id = p.id
        JOIN centros_costo cc ON e.centro_costo_id = cc.id
        JOIN estados_tramite et ON e.estado_actual = et.codigo
        $where_sql
        ORDER BY e.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    $stmtL = $pdo->prepare($sqlLista);
    $stmtL->execute($params);
    $solicitudes = $stmtL->fetchAll();

    foreach ($solicitudes as &$row) {
        $stmtItems = $pdo->prepare("SELECT id, descripcion, cantidad, precio_unitario, unidad_medida FROM expedientes_items WHERE expediente_id = ?");
        $stmtItems->execute([$row['id']]);
        $row['items_detalle'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($row);
}

$tipos_compra_filtro = $pdo->query("SELECT id, nombre FROM tipos_compra WHERE activo=1 ORDER BY nombre")->fetchAll();
$estados_filtro = $pdo->query("SELECT codigo, nombre FROM estados_tramite ORDER BY nombre")->fetchAll();

function color_estado($estado_codigo) {
    if (in_array($estado_codigo, ['BORRADOR', 'EN_REVISION_JEFATURA'])) return 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle';
    if (in_array($estado_codigo, ['RECHAZADO', 'ANULADO'])) return 'bg-danger-subtle text-danger-emphasis text-decoration-line-through border border-danger-subtle';
    if ($estado_codigo === 'FINALIZADO') return 'bg-success-subtle text-success-emphasis fw-bold border border-success-subtle';
    if (in_array($estado_codigo, ['EN_COTIZACION_ADQ', 'EN_GESTION_ADQUISICIONES'])) return 'bg-info-subtle text-info-emphasis fw-bold border border-info-subtle';
    if ($estado_codigo === 'EN_EVALUACION_OFERTAS') return 'bg-warning-subtle text-warning-emphasis fw-bold border border-warning-subtle';
    if ($estado_codigo === 'EN_CORRECCION') return 'bg-danger-subtle text-danger-emphasis fw-bold border border-danger-subtle'; 
    return 'bg-primary-subtle text-primary-emphasis border border-primary-subtle';
}

function money($v) {
    if ($v === null || $v === '') return '$ 0';
    return '$ ' . number_format((float)$v, 0, ',', '.');
}

$query_string = $_GET; unset($query_string['p']);
$base_url = '?' . http_build_query($query_string) . '&p=';
?>