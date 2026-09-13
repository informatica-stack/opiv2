<?php
// admin_controller.php - Adaptado a Flujos V5.0 (OPI Correlativa Oficial)
require_once __DIR__ . '/config.php';

// 1. SEGURIDAD
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

// Solo el Administrador Municipal o el Administrador del Sistema pueden entrar aquí
$rol = $_SESSION['user_rol'] ?? '';
if ($rol !== 'ADMIN_MUNICIPAL' && $rol !== 'SYSADMIN') {
    die("Acceso Denegado. Módulo exclusivo para Administración Municipal.");
}

$mensaje = '';
$tipo_mensaje = '';
$vista = $_GET['view'] ?? 'lista';

// --- ACCIONES POST ---
// --- ACCIONES POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        $id = isset($_POST['expediente_id']) ? (int)$_POST['expediente_id'] : null;
        $transicion_id = $_POST['transicion_id'] ?? null;
        $accion = $_POST['accion'] ?? '';

        if ($id) {
            $stmtEst = $pdo->prepare("SELECT e.estado_actual FROM expedientes e WHERE e.id = ?");
            $stmtEst->execute([$id]);
            $stAct = $stmtEst->fetchColumn();
            if (!in_array($stAct, ['EN_AUTORIZACION_COTIZACION', 'EN_APROBACION_ADMINISTRADOR'])) {
                throw new Exception("El expediente ya fue procesado y no se encuentra disponible para aprobación en Administración Municipal.");
            }
        }

        $trans = null;
        if ($transicion_id) {
            $stmtT = $pdo->prepare("SELECT * FROM flujos_definicion WHERE id = ?");
            $stmtT->execute([$transicion_id]);
            $trans = $stmtT->fetch();
            if ($trans) {
                $accion = strtolower($trans['accion_codigo']); // 'aprobar', 'devolver', 'rechazar'
            }
        }

        // A. RECHAZAR SOLICITUD ANTES DE FIRMAR
        if ($accion === 'rechazar') {
            $motivo = trim($_POST['motivo'] ?? $_POST['motivo_rechazo'] ?? '');
            if(empty($motivo)) throw new Exception("Debe indicar el motivo del rechazo.");

            if ($transicion_id) {
                $nuevo_destino = ejecutar_transicion_por_id($pdo, $id, $_SESSION['user_id'], $transicion_id, $motivo);
            } else {
                rechazar_flujo($pdo, $id, $_SESSION['user_id'], $motivo);
            }

            $mensaje = "Expediente rechazado y cerrado.";
            $tipo_mensaje = "error";
            $vista = 'lista';
        }

        // B. DEVOLVER PARA CORRECCIÓN
        if ($accion === 'devolver') {
            $motivo = trim($_POST['motivo'] ?? $_POST['motivo_rechazo'] ?? '');
            if(empty($motivo)) throw new Exception("Debe indicar qué se debe corregir.");

            if ($transicion_id) {
                $nuevo_destino = ejecutar_transicion_por_id($pdo, $id, $_SESSION['user_id'], $transicion_id, "Devuelto por Administración: " . $motivo);
            } else {
                devolver_flujo($pdo, $id, $_SESSION['user_id'], "Devuelto por Administración: " . $motivo);
            }

            $mensaje = "Expediente devuelto para corrección.";
            $tipo_mensaje = "warning";
            $vista = 'lista';
        }

        // C. APROBAR Y FIRMAR
        if ($accion === 'firmar' || $accion === 'aprobar' || $accion === 'firmar_firmagob' || $accion === 'subir_pdf_manual') {
            
            $stmtEst = $pdo->prepare("SELECT estado_actual, codigo_interno FROM expedientes WHERE id = ?");
            $stmtEst->execute([$id]);
            $exp_row = $stmtEst->fetch();
            $estado_actual_exp = $exp_row['estado_actual'] ?? '';

            if ($estado_actual_exp === 'EN_AUTORIZACION_COTIZACION') {
                // Autorización de Cotización limpia (sin OPI ni PDF)
                $comentario = "Cotización autorizada por Administración Municipal.";
                if ($transicion_id) {
                    $nuevo_destino = ejecutar_transicion_por_id($pdo, $id, $_SESSION['user_id'], $transicion_id, $comentario);
                } else {
                    $nuevo_destino = avanzar_flujo($pdo, $id, $_SESSION['user_id'], $comentario);
                }
                $stmtNd = $pdo->prepare("SELECT nombre FROM estados_tramite WHERE codigo = ?");
                $stmtNd->execute([$nuevo_destino]);
                $nombre_dest = $stmtNd->fetchColumn();

                $mensaje = "Cotización autorizada exitosamente. El trámite avanzó a: $nombre_dest.";
                $tipo_mensaje = "success";
                $vista = 'lista';
            } else {
                // Firma y Emisión de OPI Final (Fase 3: Firma 3/3)
                $anio = date('Y');
                $dir = __DIR__ . "/uploads/$anio/exp_$id/";
                if (!file_exists($dir)) mkdir($dir, 0777, true);
                
                $nombre_final = "OPI_FIRMADA_FINAL_" . time() . ".pdf";
                $ruta_abs = $dir . $nombre_final;
                $ruta_db = "uploads/$anio/exp_$id/" . $nombre_final;

                $firmante = firmagob_obtener_firmante_activo($pdo, 'ADMIN_MUNICIPAL', $_SESSION['user_id']);
                $run_firmante = $firmante['rut'] ?? $_SESSION['user_rut'];

                if ($accion === 'subir_pdf_manual' || (!empty($_FILES['pdf_firmado']['name']) && $accion === 'aprobar')) {
                    $file_input = !empty($_FILES['pdf_firmado_manual']['name']) ? $_FILES['pdf_firmado_manual'] : $_FILES['pdf_firmado'];
                    $ext = validar_subida_archivo($file_input, null, ['pdf']);
                    if (!move_uploaded_file($file_input['tmp_name'], $ruta_abs)) {
                        throw new Exception("Error al mover el archivo al servidor.");
                    }
                    $id_solicitud = null;
                    $chk_orig = null;
                    $chk_signed = hash_file('sha256', $ruta_abs);
                    $tipo_firma = 'MANUAL_DOCDIGITAL';
                } else {
                    $otp = $_POST['otp_code'] ?? null;

                    // Buscar PDF de entrada (OPI_FIRMADA_2 u OPI_FIRMADA_1)
                    $stmtDoc = $pdo->prepare("SELECT ruta_archivo FROM expedientes_documentos WHERE expediente_id = ? AND tipo_doc = 'OPI_FIRMADA_PDF' ORDER BY id DESC LIMIT 1");
                    $stmtDoc->execute([$id]);
                    $doc_db = $stmtDoc->fetchColumn();

                    $ruta_base = ($doc_db && file_exists(__DIR__ . '/' . $doc_db)) ? __DIR__ . '/' . $doc_db : __DIR__ . '/' . generar_pdf_base_opi($pdo, $id);

                    $resFirma = firmagob_firmar_archivo($ruta_base, $run_firmante, "OPI Final #" . ($exp_row['codigo_interno'] ?? $id), $otp, 'ADMIN_MUNICIPAL');
                    file_put_contents($ruta_abs, $resFirma['content_binary']);

                    $id_solicitud = $resFirma['id_solicitud'];
                    $chk_orig = $resFirma['checksum_original'];
                    $chk_signed = $resFirma['checksum_signed'];
                    $tipo_firma = (FIRMAGOB_MODO === 'DESATENDIDA') ? 'FIRMAGOB_DESATENDIDA' : 'FIRMAGOB_ATENDIDA';
                }

                // LÓGICA DE GENERACIÓN DE FOLIO OFICIAL CORRELATIVO
                $stmtF = $pdo->prepare("SELECT folio_opi FROM expedientes WHERE id = ?");
                $stmtF->execute([$id]);
                $folio_actual = $stmtF->fetchColumn();

                if (!$folio_actual) {
                    $intentos = 0;
                    while ($intentos < 5) {
                        try {
                            $stmtMax = $pdo->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(folio_opi, '-', -1) AS UNSIGNED)) FROM expedientes WHERE folio_opi LIKE ?");
                            $stmtMax->execute(["OPI-$anio-%"]);
                            $max_num = $stmtMax->fetchColumn();
                            
                            $siguiente = $max_num ? $max_num + 1 : 1;
                            $folio_opi = "OPI-" . $anio . "-" . str_pad($siguiente, 4, '0', STR_PAD_LEFT);

                            $stmt = $pdo->prepare("UPDATE expedientes SET folio_opi = ?, fecha_aprobacion_opi = NOW() WHERE id = ?");
                            $stmt->execute([$folio_opi, $id]);
                            break;
                        } catch (PDOException $e) {
                            if ($e->getCode() == '23000' || $e->errorInfo[1] == 1062) {
                                $intentos++;
                                if ($intentos >= 5) throw new Exception("Error de concurrencia al asignar OPI: " . $e->getMessage());
                                usleep(100000);
                            } else {
                                throw $e;
                            }
                        }
                    }
                } else {
                    $folio_opi = $folio_actual;
                    $stmt = $pdo->prepare("UPDATE expedientes SET fecha_aprobacion_opi = NOW() WHERE id = ? AND fecha_aprobacion_opi IS NULL");
                    $stmt->execute([$id]);
                }

                // Registrar Documento
                $pdo->prepare("INSERT INTO expedientes_documentos (expediente_id, subido_por_id, tipo_doc, ruta_archivo, nombre_original) VALUES (?, ?, 'OPI_FIRMADA_PDF', ?, ?)")
                    ->execute([$id, $_SESSION['user_id'], $ruta_db, $nombre_final]);

                // Registrar Firma
                $pdo->prepare("INSERT INTO expedientes_firmas (expediente_id, usuario_firmante_id, autoridad_id, nombre_firmante, rut_firmante, cargo_firmante, etapa_firma, firmagob_solicitud_id, checksum_original, checksum_signed, tipo_firma, ip_origen, nombre_archivo_firmado) VALUES (?, ?, ?, ?, ?, ?, 'ADMIN_MUNICIPAL', ?, ?, ?, ?, ?, ?)")
                    ->execute([
                        $id,
                        $_SESSION['user_id'],
                        $firmante['id'] ?? null,
                        $firmante['nombre'] ?? $_SESSION['user_nombre'] ?? 'Administrador Municipal',
                        $run_firmante,
                        $firmante['cargo'] ?? 'ADMINISTRADOR MUNICIPAL',
                        $id_solicitud,
                        $chk_orig,
                        $chk_signed,
                        $tipo_firma,
                        $_SERVER['REMOTE_ADDR'] ?? null,
                        $nombre_final
                    ]);

                // Avanzar el Flujo
                if ($transicion_id) {
                    $nuevo_destino = ejecutar_transicion_por_id($pdo, $id, $_SESSION['user_id'], $transicion_id, "OPI Oficial Generada y Firmada Digitalmente (Folio: $folio_opi).");
                } else {
                    $nuevo_destino = avanzar_flujo($pdo, $id, $_SESSION['user_id'], "OPI Oficial Generada y Firmada Digitalmente (Folio: $folio_opi).");
                }
                $stmtNd = $pdo->prepare("SELECT nombre FROM estados_tramite WHERE codigo = ?");
                $stmtNd->execute([$nuevo_destino]);
                $nombre_dest = $stmtNd->fetchColumn();

                $mensaje = "OPI #$folio_opi emitida y firmada oficialmente con éxito (3/3). Enviado a: $nombre_dest.";
                $tipo_mensaje = "success";
                $vista = 'lista';
            }
        }

        $pdo->commit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// --- CONSULTAS GET ---

if ($vista === 'lista') {
    $sql_base = "
        SELECT e.*, u.nombre_completo as solicitante, cc.nombre as centro_costo, p.razon_social as proveedor, et.nombre as estado_nombre, tc.nombre as tipo_compra_nom
        FROM expedientes e
        JOIN usuarios u ON e.usuario_creador_id = u.id
        JOIN centros_costo cc ON e.centro_costo_id = cc.id
        JOIN tipos_compra tc ON e.tipo_compra_id = tc.id
        JOIN estados_tramite et ON e.estado_actual = et.codigo
        LEFT JOIN proveedores p ON e.proveedor_adjudicado_id = p.id
    ";
    
    $pendientes_cotizacion = $pdo->query($sql_base . " WHERE e.estado_actual = 'EN_AUTORIZACION_COTIZACION' ORDER BY e.prioridad_id DESC, e.created_at ASC")->fetchAll();
    $pendientes_opi = $pdo->query($sql_base . " WHERE e.estado_actual = 'EN_APROBACION_ADMINISTRADOR' ORDER BY e.prioridad_id DESC, e.created_at ASC")->fetchAll();

    $count_cotizacion = count($pendientes_cotizacion);
    $count_opi = count($pendientes_opi);
    $pendientes = array_merge($pendientes_cotizacion, $pendientes_opi);
}

if ($vista === 'revisar' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("
        SELECT e.*, u.nombre_completo as solicitante, un.nombre as unidad, cc.nombre as centro_costo, 
               p.razon_social as proveedor_nombre, p.rut as proveedor_rut, tc.nombre as tipo_compra_nom, tc.codigo as tipo_compra_cod, et.nombre as estado_nombre, et.rol_responsable
        FROM expedientes e
        JOIN usuarios u ON e.usuario_creador_id = u.id
        JOIN unidades un ON e.unidad_origen_id = un.id
        JOIN centros_costo cc ON e.centro_costo_id = cc.id
        JOIN tipos_compra tc ON e.tipo_compra_id = tc.id
        JOIN estados_tramite et ON e.estado_actual = et.codigo
        LEFT JOIN proveedores p ON e.proveedor_adjudicado_id = p.id
        WHERE e.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $exp = $stmt->fetch();
    $expediente = $exp;

    if (!$exp) die("Expediente no encontrado.");

    $es_accionable = in_array($exp['estado_actual'], ['EN_AUTORIZACION_COTIZACION', 'EN_APROBACION_ADMINISTRADOR']);

    $stmtI = $pdo->prepare("
        SELECT ei.*, cm.nombre as cuenta_nombre, cm.codigo as cuenta_codigo 
        FROM expedientes_items ei
        LEFT JOIN presupuestos_asignados pa ON ei.presupuesto_asignado_id = pa.id
        LEFT JOIN cuentas_maestras cm ON pa.cuenta_maestra_id = cm.id
        WHERE ei.expediente_id = ?
    ");
    $stmtI->execute([$_GET['id']]);
    $items = $stmtI->fetchAll();

    $stmtCrit = $pdo->prepare("SELECT * FROM expedientes_criterios WHERE expediente_id = ? ORDER BY numero_criterio ASC");
    $stmtCrit->execute([$_GET['id']]);
    $criterios = $stmtCrit->fetchAll(PDO::FETCH_ASSOC);

    $stmtD = $pdo->prepare("SELECT * FROM expedientes_documentos WHERE expediente_id = ? ORDER BY fecha_subida DESC");
    $stmtD->execute([$_GET['id']]);
    $docs = $stmtD->fetchAll();
}

function money($v) {
    if ($v === null || $v === '') return '$ 0';
    return '$ ' . number_format((float)$v, 0, ',', '.');
}
?>