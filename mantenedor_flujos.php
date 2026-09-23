<?php
// mantenedor_flujos.php - Panel de Administración y Configuración de Flujos OPIv2 (V7.0)
require_once __DIR__ . '/config.php';

// 1. SEGURIDAD Y CONTROL DE ACCESO
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$rol = $_SESSION['user_rol'] ?? '';
if ($rol !== 'SYSADMIN' && $rol !== 'ADMIN_MUNICIPAL') {
    die("Acceso Denegado. Solo la Administración Municipal y Administradores de Sistema pueden configurar los flujos de compra.");
}

$mensaje = ''; 
$tipo_mensaje = '';
$tipo_seleccionado_id = isset($_GET['tipo']) ? (int)$_GET['tipo'] : null;

// --- DEFINICIÓN DE PLANTILLAS OFICIALES OPIv2 (FirmaGob v18 + Secretaría de Gobierno Digital) ---
$plantillas_oficiales = [
    'COMPETITIVA' => [
        'nombre' => 'Flujo Con Cotización y Evaluación de Ofertas',
        'descripcion' => 'Requerido para Compra Ágil y Licitación Pública. Incluye cotización en portal, evaluación de ofertas por requirente, adjudicación y la cadena completa de 3 firmas FirmaGob.',
        'reglas' => [
            ['BORRADOR', 'APROBAR', 'Enviar a Jefatura', 'EN_REVISION_JEFATURA', 0, 0],
            ['EN_REVISION_JEFATURA', 'APROBAR', 'Aprobar Requerimiento', 'EN_VALIDACION_PRESUPUESTARIA', 0, 0],
            ['EN_REVISION_JEFATURA', 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 1, 0],
            ['EN_REVISION_JEFATURA', 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 1, 0],
            ['EN_VALIDACION_PRESUPUESTARIA', 'APROBAR', 'Visar Saldo Estimado', 'EN_AUTORIZACION_COTIZACION', 0, 0],
            ['EN_VALIDACION_PRESUPUESTARIA', 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 1, 0],
            ['EN_VALIDACION_PRESUPUESTARIA', 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 1, 0],
            ['EN_AUTORIZACION_COTIZACION', 'APROBAR', 'Autorizar Inicio de Cotización', 'EN_GESTION_ADQUISICIONES', 0, 0],
            ['EN_AUTORIZACION_COTIZACION', 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 1, 0],
            ['EN_AUTORIZACION_COTIZACION', 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 1, 0],
            ['EN_GESTION_ADQUISICIONES', 'APROBAR', 'Enviar Ofertas a Evaluación del Solicitante', 'EN_EVALUACION_OFERTAS', 0, 0],
            ['EN_GESTION_ADQUISICIONES', 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 1, 0],
            ['EN_GESTION_ADQUISICIONES', 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 1, 0],
            ['EN_EVALUACION_OFERTAS', 'ADJUDICAR', 'Adjudicar Oferta y Compilar OPI', 'EN_FIRMA_JEFATURA', 0, 0],
            ['EN_EVALUACION_OFERTAS', 'DEVOLVER', 'Devolver a Adquisiciones para Recotizar', 'EN_GESTION_ADQUISICIONES', 1, 0],
            ['EN_EVALUACION_OFERTAS', 'RECHAZAR', 'Desestimar Ofertas y Cerrar', 'RECHAZADO', 1, 0],
            ['EN_FIRMA_JEFATURA', 'FIRMAR_JEFATURA', 'Firmar OPI V°B° Jefatura (1/3)', 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 0, 0],
            ['EN_FIRMA_JEFATURA', 'DEVOLVER', 'Devolver a Solicitante para Reevaluar', 'EN_EVALUACION_OFERTAS', 1, 0],
            ['EN_FIRMA_JEFATURA', 'RECHAZAR', 'Rechazar Adjudicación', 'RECHAZADO', 1, 0],
            ['EN_VALIDACION_PRESUPUESTARIA_FINAL', 'FIRMAR_PRESUPUESTO', 'Firmar OPI V°B° Presupuesto (2/3) y Emitir CDP', 'ESPERANDO_CDP_FINANZAS_FINAL', 0, 0],
            ['EN_VALIDACION_PRESUPUESTARIA_FINAL', 'DEVOLVER', 'Devolver para Corrección', 'EN_EVALUACION_OFERTAS', 1, 0],
            ['EN_VALIDACION_PRESUPUESTARIA_FINAL', 'RECHAZAR', 'Rechazar Imputación', 'RECHAZADO', 1, 0],
            ['ESPERANDO_CDP_FINANZAS_FINAL', 'FIRMAR_CDP_FINANZAS', 'Firmar CDP Oficial (Finanzas)', 'EN_APROBACION_ADMINISTRADOR', 0, 0],
            ['ESPERANDO_CDP_FINANZAS_FINAL', 'DEVOLVER', 'Devolver a Presupuesto', 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 1, 0],
            ['ESPERANDO_CDP_FINANZAS_FINAL', 'RECHAZAR', 'Rechazar CDP', 'RECHAZADO', 1, 0],
            ['EN_APROBACION_ADMINISTRADOR', 'FIRMAR_ADMIN', 'Firmar y Emitir OPI Definitiva (3/3)', 'EN_EMISION_OC', 0, 0],
            ['EN_APROBACION_ADMINISTRADOR', 'DEVOLVER', 'Devolver para Corrección', 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 1, 0],
            ['EN_APROBACION_ADMINISTRADOR', 'RECHAZAR', 'Rechazar OPI', 'RECHAZADO', 1, 0],
            ['EN_EMISION_OC', 'APROBAR', 'Registrar OC y Esperar Aceptación', 'ESPERANDO_ACEPTACION_OC', 0, 0],
            ['EN_EMISION_OC', 'DEVOLVER', 'Devolver a Administrador', 'EN_APROBACION_ADMINISTRADOR', 1, 0],
            ['ESPERANDO_ACEPTACION_OC', 'APROBAR', 'OC Aceptada en Portal', 'FINALIZADO', 0, 0],
            ['ESPERANDO_ACEPTACION_OC', 'DEVOLVER', 'Rechazo de OC - Devolver a Evaluación', 'EN_EVALUACION_OFERTAS', 1, 0]
        ]
    ],
    'DIRECTA' => [
        'nombre' => 'Flujo Proveedor Adjudicado / Catálogo Directo',
        'descripcion' => 'Requerido para Convenio Marco, Trato Directo y Contratos de Suministro. No cotiza en portal; pasa directamente de Adquisiciones a la cadena de 3 firmas FirmaGob.',
        'reglas' => [
            ['BORRADOR', 'APROBAR', 'Enviar a Jefatura', 'EN_REVISION_JEFATURA', 0, 0],
            ['EN_REVISION_JEFATURA', 'APROBAR', 'Aprobar Requerimiento', 'EN_VALIDACION_PRESUPUESTARIA', 0, 0],
            ['EN_REVISION_JEFATURA', 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 1, 0],
            ['EN_REVISION_JEFATURA', 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 1, 0],
            ['EN_VALIDACION_PRESUPUESTARIA', 'APROBAR', 'Visar Saldo y Enviar a Adquisiciones', 'EN_GESTION_ADQUISICIONES', 0, 0],
            ['EN_VALIDACION_PRESUPUESTARIA', 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 1, 0],
            ['EN_VALIDACION_PRESUPUESTARIA', 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 1, 0],
            ['EN_GESTION_ADQUISICIONES', 'APROBAR', 'Seleccionar Catálogo y Enviar a Firma Jefatura', 'EN_FIRMA_JEFATURA', 0, 0],
            ['EN_GESTION_ADQUISICIONES', 'DEVOLVER', 'Devolver para Corrección', 'EN_CORRECCION', 1, 0],
            ['EN_GESTION_ADQUISICIONES', 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 1, 0],
            ['EN_FIRMA_JEFATURA', 'FIRMAR_JEFATURA', 'Firmar OPI V°B° Jefatura (1/3)', 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 0, 0],
            ['EN_FIRMA_JEFATURA', 'DEVOLVER', 'Devolver para Corrección', 'EN_GESTION_ADQUISICIONES', 1, 0],
            ['EN_FIRMA_JEFATURA', 'RECHAZAR', 'Rechazar Solicitud', 'RECHAZADO', 1, 0],
            ['EN_VALIDACION_PRESUPUESTARIA_FINAL', 'FIRMAR_PRESUPUESTO', 'Firmar OPI V°B° Presupuesto (2/3) y Emitir CDP', 'ESPERANDO_CDP_FINANZAS_FINAL', 0, 0],
            ['EN_VALIDACION_PRESUPUESTARIA_FINAL', 'DEVOLVER', 'Devolver para Corrección', 'EN_GESTION_ADQUISICIONES', 1, 0],
            ['EN_VALIDACION_PRESUPUESTARIA_FINAL', 'RECHAZAR', 'Rechazar Imputación', 'RECHAZADO', 1, 0],
            ['ESPERANDO_CDP_FINANZAS_FINAL', 'FIRMAR_CDP_FINANZAS', 'Firmar CDP Oficial (Finanzas)', 'EN_APROBACION_ADMINISTRADOR', 0, 0],
            ['ESPERANDO_CDP_FINANZAS_FINAL', 'DEVOLVER', 'Devolver a Presupuesto', 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 1, 0],
            ['ESPERANDO_CDP_FINANZAS_FINAL', 'RECHAZAR', 'Rechazar CDP', 'RECHAZADO', 1, 0],
            ['EN_APROBACION_ADMINISTRADOR', 'FIRMAR_ADMIN', 'Firmar y Emitir OPI Definitiva (3/3)', 'EN_EMISION_OC', 0, 0],
            ['EN_APROBACION_ADMINISTRADOR', 'DEVOLVER', 'Devolver para Corrección', 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 1, 0],
            ['EN_APROBACION_ADMINISTRADOR', 'RECHAZAR', 'Rechazar OPI', 'RECHAZADO', 1, 0],
            ['EN_EMISION_OC', 'APROBAR', 'Registrar OC y Esperar Aceptación', 'ESPERANDO_ACEPTACION_OC', 0, 0],
            ['EN_EMISION_OC', 'DEVOLVER', 'Devolver a Administrador', 'EN_APROBACION_ADMINISTRADOR', 1, 0],
            ['ESPERANDO_ACEPTACION_OC', 'APROBAR', 'OC Aceptada en Portal', 'FINALIZADO', 0, 0],
            ['ESPERANDO_ACEPTACION_OC', 'DEVOLVER', 'Rechazo de OC - Devolver para Corrección', 'EN_CORRECCION', 1, 0]
        ]
    ]
];

// --- ACCIONES POST ---
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        $accion = $_POST['accion'] ?? '';
        $tipo_compra_id = (int)($_POST['tipo_compra_id'] ?? 0);

        if ($accion === 'aplicar_plantilla') {
            $plantilla_key = $_POST['plantilla'] ?? '';
            if (!isset($plantillas_oficiales[$plantilla_key])) {
                throw new Exception("Plantilla seleccionada no válida.");
            }
            if ($tipo_compra_id <= 0) {
                throw new Exception("Tipo de compra no válido.");
            }

            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM flujos_definicion WHERE tipo_compra_id = ?")->execute([$tipo_compra_id]);

            $stmtIns = $pdo->prepare("
                INSERT INTO flujos_definicion 
                (tipo_compra_id, estado_actual, accion_codigo, accion_label, estado_destino, requiere_comentario, requiere_archivo)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($plantillas_oficiales[$plantilla_key]['reglas'] as $r) {
                $stmtIns->execute([$tipo_compra_id, $r[0], $r[1], $r[2], $r[3], $r[4], $r[5]]);
            }

            $pdo->commit();
            $mensaje = "Plantilla '" . $plantillas_oficiales[$plantilla_key]['nombre'] . "' instalada exitosamente (" . count($plantillas_oficiales[$plantilla_key]['reglas']) . " reglas cargadas).";
            $tipo_mensaje = "success";
            $tipo_seleccionado_id = $tipo_compra_id;
        }

        if ($accion === 'restaurar_oficial') {
            if ($tipo_compra_id <= 0) throw new Exception("Tipo de compra no válido.");

            // Identificar si requiere cotización
            $stmtTC = $pdo->prepare("SELECT requiere_cotizacion FROM tipos_compra WHERE id = ?");
            $stmtTC->execute([$tipo_compra_id]);
            $reqCot = (int)$stmtTC->fetchColumn();

            $pKey = ($reqCot == 1 || in_array($tipo_compra_id, [3, 6])) ? 'COMPETITIVA' : 'DIRECTA';

            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM flujos_definicion WHERE tipo_compra_id = ?")->execute([$tipo_compra_id]);

            $stmtIns = $pdo->prepare("
                INSERT INTO flujos_definicion 
                (tipo_compra_id, estado_actual, accion_codigo, accion_label, estado_destino, requiere_comentario, requiere_archivo)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($plantillas_oficiales[$pKey]['reglas'] as $r) {
                $stmtIns->execute([$tipo_compra_id, $r[0], $r[1], $r[2], $r[3], $r[4], $r[5]]);
            }

            $pdo->commit();
            $mensaje = "Flujo oficial de fábrica restablecido exitosamente para este tipo de compra.";
            $tipo_mensaje = "success";
            $tipo_seleccionado_id = $tipo_compra_id;
        }

        if ($accion === 'clonar_flujo') {
            $origen_id = (int)($_POST['origen_tipo_id'] ?? 0);
            $destino_id = (int)($_POST['destino_tipo_id'] ?? 0);

            if ($origen_id <= 0 || $destino_id <= 0 || $origen_id === $destino_id) {
                throw new Exception("El flujo origen y destino deben ser válidos y distintos.");
            }

            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM flujos_definicion WHERE tipo_compra_id = ?")->execute([$destino_id]);

            $stmtCopy = $pdo->prepare("
                INSERT INTO flujos_definicion (tipo_compra_id, estado_actual, accion_codigo, accion_label, estado_destino, requiere_comentario, requiere_archivo, monto_min_utm, monto_max_utm)
                SELECT ?, estado_actual, accion_codigo, accion_label, estado_destino, requiere_comentario, requiere_archivo, monto_min_utm, monto_max_utm
                FROM flujos_definicion WHERE tipo_compra_id = ?
            ");
            $stmtCopy->execute([$destino_id, $origen_id]);
            $pdo->commit();

            $mensaje = "Reglas de flujo clonadas correctamente hacia el tipo de compra destino.";
            $tipo_mensaje = "success";
            $tipo_seleccionado_id = $destino_id;
        }

        if ($accion === 'agregar_transicion') {
            $origen = trim($_POST['estado_actual'] ?? '');
            $destino = trim($_POST['estado_destino'] ?? '');
            $accion_codigo = trim($_POST['accion_codigo'] ?? '');
            $accion_label = trim($_POST['accion_label'] ?? '');
            $requiere_comentario = !empty($_POST['requiere_comentario']) ? 1 : 0;
            $requiere_archivo = !empty($_POST['requiere_archivo']) ? 1 : 0;
            $monto_min = (!empty($_POST['monto_min_utm']) && is_numeric($_POST['monto_min_utm'])) ? floatval($_POST['monto_min_utm']) : null;
            $monto_max = (!empty($_POST['monto_max_utm']) && is_numeric($_POST['monto_max_utm'])) ? floatval($_POST['monto_max_utm']) : null;

            if (empty($origen) || empty($destino)) throw new Exception("Debe especificar estado origen y destino.");
            if ($origen === $destino) throw new Exception("El estado origen y destino no pueden ser idénticos.");
            if (empty($accion_label)) throw new Exception("La etiqueta visible de la acción es obligatoria.");

            $stmt = $pdo->prepare("
                INSERT INTO flujos_definicion 
                (tipo_compra_id, estado_actual, accion_codigo, accion_label, estado_destino, requiere_comentario, requiere_archivo, monto_min_utm, monto_max_utm)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$tipo_compra_id, $origen, $accion_codigo, $accion_label, $destino, $requiere_comentario, $requiere_archivo, $monto_min, $monto_max]);

            $mensaje = "Transición creada exitosamente.";
            $tipo_mensaje = "success";
            $tipo_seleccionado_id = $tipo_compra_id;
        }

        if ($accion === 'editar_transicion') {
            $transicion_id = (int)($_POST['transicion_id'] ?? 0);
            $origen = trim($_POST['estado_actual'] ?? '');
            $destino = trim($_POST['estado_destino'] ?? '');
            $accion_codigo = trim($_POST['accion_codigo'] ?? '');
            $accion_label = trim($_POST['accion_label'] ?? '');
            $requiere_comentario = !empty($_POST['requiere_comentario']) ? 1 : 0;
            $requiere_archivo = !empty($_POST['requiere_archivo']) ? 1 : 0;
            $monto_min = (!empty($_POST['monto_min_utm']) && is_numeric($_POST['monto_min_utm'])) ? floatval($_POST['monto_min_utm']) : null;
            $monto_max = (!empty($_POST['monto_max_utm']) && is_numeric($_POST['monto_max_utm'])) ? floatval($_POST['monto_max_utm']) : null;

            if ($transicion_id <= 0) throw new Exception("Identificador de transición no válido.");
            if ($origen === $destino) throw new Exception("El estado origen y destino no pueden ser idénticos.");
            if (empty($accion_label)) throw new Exception("La etiqueta de la acción es obligatoria.");

            $stmt = $pdo->prepare("
                UPDATE flujos_definicion 
                SET estado_actual = ?, 
                    accion_codigo = ?, 
                    accion_label = ?, 
                    estado_destino = ?, 
                    requiere_comentario = ?, 
                    requiere_archivo = ?, 
                    monto_min_utm = ?, 
                    monto_max_utm = ?
                WHERE id = ? AND tipo_compra_id = ?
            ");
            $stmt->execute([$origen, $accion_codigo, $accion_label, $destino, $requiere_comentario, $requiere_archivo, $monto_min, $monto_max, $transicion_id, $tipo_compra_id]);

            $mensaje = "Transición actualizada correctamente.";
            $tipo_mensaje = "success";
            $tipo_seleccionado_id = $tipo_compra_id;
        }

        if ($accion === 'eliminar_transicion') {
            $transicion_id = (int)($_POST['transicion_id'] ?? 0);
            if ($transicion_id > 0) {
                $pdo->prepare("DELETE FROM flujos_definicion WHERE id = ?")->execute([$transicion_id]);
                $mensaje = "Transición eliminada del flujo.";
                $tipo_mensaje = "success";
            }
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $mensaje = $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// --- CARGA DE DATOS MAESTROS ---
$tipos_compra = $pdo->query("SELECT * FROM tipos_compra WHERE activo = 1 ORDER BY id ASC")->fetchAll();
$estados_raw = $pdo->query("SELECT * FROM estados_tramite ORDER BY rol_responsable ASC, nombre ASC")->fetchAll();

$mapa_estados = [];
$estados_agrupados = [];
foreach ($estados_raw as $e) {
    $mapa_estados[$e['codigo']] = $e;
    $estados_agrupados[$e['rol_responsable']][] = $e;
}

if (!$tipo_seleccionado_id && !empty($tipos_compra)) {
    $tipo_seleccionado_id = (int)$tipos_compra[0]['id'];
}

// Cargar flujo actual
$flujos_raw = [];
$tipo_actual = null;
if ($tipo_seleccionado_id) {
    foreach ($tipos_compra as $tc) {
        if ($tc['id'] == $tipo_seleccionado_id) {
            $tipo_actual = $tc;
            break;
        }
    }
    $stmtF = $pdo->prepare("SELECT * FROM flujos_definicion WHERE tipo_compra_id = ? ORDER BY id ASC");
    $stmtF->execute([$tipo_seleccionado_id]);
    $flujos_raw = $stmtF->fetchAll();
}

// Construir la Ruta Feliz Principal (Timeline lineal de avances secuenciales)
$ruta_feliz = [];
$paso_actual = 'BORRADOR';
$visitados = [];
$max_steps = 15;

while ($paso_actual && !in_array($paso_actual, $visitados) && count($visitados) < $max_steps) {
    $visitados[] = $paso_actual;
    $info_est = $mapa_estados[$paso_actual] ?? [
        'codigo' => $paso_actual, 
        'nombre' => $paso_actual, 
        'rol_responsable' => 'SISTEMA'
    ];
    $ruta_feliz[] = $info_est;

    if ($paso_actual === 'FINALIZADO') break;

    // Buscar la transición principal hacia adelante
    $siguiente_est = null;
    foreach ($flujos_raw as $f) {
        if ($f['estado_actual'] === $paso_actual) {
            // Filtrar acciones principales de avance
            if (in_array($f['accion_codigo'], ['APROBAR', 'ADJUDICAR', 'FIRMAR_JEFATURA', 'FIRMAR_PRESUPUESTO', 'FIRMAR_CDP_FINANZAS', 'FIRMAR_ADMIN'])) {
                if (!in_array($f['estado_destino'], ['EN_CORRECCION', 'RECHAZADO', 'ANULADO'])) {
                    $siguiente_est = $f['estado_destino'];
                    break;
                }
            }
        }
    }
    $paso_actual = $siguiente_est;
}

// Contadores de reglas
$total_reglas = count($flujos_raw);
$total_avances = 0;
$total_devoluciones = 0;
$total_rechazos = 0;
$total_firmas = 0;

foreach ($flujos_raw as $f) {
    if (strpos($f['accion_codigo'], 'FIRMAR') !== false) {
        $total_firmas++;
        $total_avances++;
    } elseif ($f['accion_codigo'] === 'DEVOLVER') {
        $total_devoluciones++;
    } elseif ($f['accion_codigo'] === 'RECHAZAR') {
        $total_rechazos++;
    } else {
        $total_avances++;
    }
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <?php 
    $titulo_pagina = "Mantenedor de Flujos y Reglas de Compra";
    include __DIR__ . '/head.php'; 
    ?>
    <style>
        .timeline-step-badge {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
        }
        .flow-pill-nav .nav-link {
            border-radius: 8px;
            font-weight: 600;
            color: #475569;
            padding: 8px 16px;
            transition: all 0.15s ease;
        }
        .flow-pill-nav .nav-link.active {
            background-color: #0f172a;
            color: #fff;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .badge-role {
            font-size: 10px;
            letter-spacing: 0.5px;
            padding: 3px 8px;
        }
    </style>
</head>
<body class="bg-light text-slate-800 font-sans d-flex flex-column min-vh-100">

    <?php include __DIR__ . '/nav.php'; ?>

    <div class="container mt-4 px-3 px-md-4">

        <!-- CABECERA -->
        <div class="row align-items-center mb-4 g-3">
            <div class="col-12 col-md">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="mis_solicitudes.php" class="text-decoration-none">Inicio</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Administración de Flujos</li>
                    </ol>
                </nav>
                <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                    <i class="bi bi-diagram-3-fill text-primary"></i>
                    Configuración de Flujos y Firmas FirmaGob
                </h1>
                <p class="text-muted small mb-0">Gestión de etapas, visaciones presupuestarias y cadena de 3 firmas electrónicas por modalidad de compra.</p>
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="button" class="btn btn-outline-danger btn-sm px-3 shadow-sm fw-bold d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalRestaurarOficial">
                    <i class="bi bi-arrow-counterclockwise"></i> Restaurar Flujo de Fábrica
                </button>
                <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm fw-bold d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalNuevaTransicion">
                    <i class="bi bi-plus-circle-fill"></i> Nueva Transición Manual
                </button>
            </div>
        </div>

        <!-- ALERTAS -->
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?= htmlspecialchars($tipo_mensaje) ?> alert-dismissible fade show d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
                <i class="bi bi-<?= $tipo_mensaje === 'danger' ? 'exclamation-octagon-fill' : 'check-circle-fill' ?> fs-5"></i>
                <div class="small fw-semibold"><?= htmlspecialchars($mensaje) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- SELECTOR DE MODALIDAD DE COMPRA -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                    <div>
                        <span class="text-uppercase text-secondary fw-bold small" style="font-size: 11px;">Modalidades de Compra Activas:</span>
                    </div>
                    <ul class="nav nav-pills flow-pill-nav gap-2 flex-wrap mb-0">
                        <?php foreach ($tipos_compra as $tc): ?>
                            <li class="nav-item">
                                <a href="?tipo=<?= $tc['id'] ?>" class="nav-link <?= $tc['id'] == $tipo_seleccionado_id ? 'active' : 'bg-light' ?>">
                                    <?php if ($tc['id'] == 6): ?>
                                        <i class="bi bi-lightning-charge-fill text-warning me-1"></i>
                                    <?php elseif ($tc['id'] == 4): ?>
                                        <i class="bi bi-grid-fill text-info me-1"></i>
                                    <?php elseif ($tc['id'] == 3): ?>
                                        <i class="bi bi-file-earmark-text-fill text-primary me-1"></i>
                                    <?php elseif ($tc['id'] == 5): ?>
                                        <i class="bi bi-box-seam-fill text-success me-1"></i>
                                    <?php else: ?>
                                        <i class="bi bi-handshake-fill text-secondary me-1"></i>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($tc['nombre']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <?php if ($tipo_actual): ?>
            <!-- TARJETA INFORMATIVA Y ACCIONES RÁPIDAS -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-6">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary-subtle text-primary p-3 rounded-4 fs-3">
                                    <i class="bi bi-diagram-3"></i>
                                </div>
                                <div>
                                    <h4 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($tipo_actual['nombre']) ?></h4>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="badge bg-secondary font-monospace"><?= htmlspecialchars($tipo_actual['codigo']) ?></span>
                                        <?php if ($tipo_actual['requiere_cotizacion']): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Requiere Cotización / Ofertas</span>
                                        <?php else: ?>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle">Proveedor Directo / Catálogo</span>
                                        <?php endif; ?>
                                        <?php if ($tipo_actual['limite_utm']): ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Tope Legal: <?= $tipo_actual['limite_utm'] ?> UTM</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KPIs de Reglas -->
                        <div class="col-lg-6">
                            <div class="row g-2 text-center">
                                <div class="col-3">
                                    <div class="p-2 border rounded-3 bg-light">
                                        <div class="fs-5 fw-bold text-dark"><?= $total_reglas ?></div>
                                        <div class="text-muted" style="font-size: 10px;">Reglas Totales</div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="p-2 border rounded-3 bg-success-subtle border-success-subtle">
                                        <div class="fs-5 fw-bold text-success"><?= $total_avances ?></div>
                                        <div class="text-success" style="font-size: 10px;">Avances</div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="p-2 border rounded-3 bg-info-subtle border-info-subtle">
                                        <div class="fs-5 fw-bold text-info"><?= $total_firmas ?></div>
                                        <div class="text-info" style="font-size: 10px;">Firmas FirmaGob</div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="p-2 border rounded-3 bg-warning-subtle border-warning-subtle">
                                        <div class="fs-5 fw-bold text-warning"><?= $total_devoluciones ?></div>
                                        <div class="text-warning" style="font-size: 10px;">Devoluciones</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VISUALIZADOR DE RUTA PRINCIPAL (HAPPY PATH) -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-signpost-split-fill text-primary"></i>
                        Cadena Cronológica de Avance (Ruta Principal)
                    </h6>
                    <span class="badge bg-light text-muted border small"><?= count($ruta_feliz) ?> Etapas</span>
                </div>
                <div class="card-body p-4 overflow-auto">
                    <div class="d-flex align-items-center gap-3 pb-2" style="min-width: 900px;">
                        <?php foreach ($ruta_feliz as $idx => $st): 
                            $is_last = ($idx === count($ruta_feliz) - 1);
                            $es_firma = in_array($st['codigo'], ['EN_FIRMA_JEFATURA', 'EN_VALIDACION_PRESUPUESTARIA_FINAL', 'ESPERANDO_CDP_FINANZAS_FINAL', 'EN_APROBACION_ADMINISTRADOR']);
                        ?>
                            <div class="d-flex align-items-center">
                                <div class="card p-3 shadow-sm border <?= $es_firma ? 'border-primary bg-primary-subtle' : 'border-light bg-white' ?>" style="width: 170px;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="timeline-step-badge <?= $es_firma ? 'bg-primary text-white' : 'bg-dark text-white' ?>">
                                            <?= $idx + 1 ?>
                                        </span>
                                        <?php if ($es_firma): ?>
                                            <span class="badge bg-warning text-dark font-monospace" style="font-size: 9px;"><i class="bi bi-pen-fill"></i> FIRMAGOB</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="fw-bold text-dark small text-truncate" title="<?= htmlspecialchars($st['nombre']) ?>">
                                        <?= htmlspecialchars($st['nombre']) ?>
                                    </div>
                                    <div class="text-muted font-monospace mt-1" style="font-size: 9px;">
                                        <?= htmlspecialchars($st['codigo']) ?>
                                    </div>
                                    <div class="mt-2 pt-1 border-top">
                                        <span class="badge bg-secondary-subtle text-secondary badge-role text-truncate d-block text-center">
                                            <?= htmlspecialchars($st['rol_responsable']) ?>
                                        </span>
                                    </div>
                                </div>

                                <?php if (!$is_last): ?>
                                    <div class="px-2 text-secondary fs-4">
                                        <i class="bi bi-chevron-right text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- TABLA DE TRANSICIONES DETALLADAS -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Matriz de Reglas y Transiciones</h6>
                        <span class="text-muted small">Condiciones lógicas que rigen el pase entre estados, firmas electrónicas y devoluciones.</span>
                    </div>

                    <div class="d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-outline-dark btn-sm fw-bold d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalClonar">
                            <i class="bi bi-copy"></i> Clonar a otra modalidad
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small" style="font-size: 11px;">
                            <tr>
                                <th class="p-3">Estado Origen</th>
                                <th class="p-3">Acción Permitida</th>
                                <th class="p-3">Estado Destino</th>
                                <th class="p-3 text-center" style="width: 160px;">Condiciones</th>
                                <th class="p-3 text-center" style="width: 120px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <?php if (empty($flujos_raw)): ?>
                                <tr>
                                    <td colspan="5" class="p-5 text-center text-muted">
                                        <i class="bi bi-exclamation-triangle fs-2 d-block text-warning mb-2"></i>
                                        No hay reglas configuradas para este tipo de compra.
                                        <div class="mt-3">
                                            <button type="button" class="btn btn-primary btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalRestaurarOficial">
                                                Restaurar Reglas Oficiales de Fábrica
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($flujos_raw as $f): 
                                $origen = $mapa_estados[$f['estado_actual']] ?? ['nombre' => $f['estado_actual'], 'rol_responsable' => 'SISTEMA'];
                                $destino = $mapa_estados[$f['estado_destino']] ?? ['nombre' => $f['estado_destino'], 'rol_responsable' => 'SISTEMA'];
                                
                                $es_firma = strpos($f['accion_codigo'], 'FIRMAR') !== false;
                                $es_devolucion = ($f['accion_codigo'] === 'DEVOLVER');
                                $es_rechazo = ($f['accion_codigo'] === 'RECHAZAR');
                                $es_adjudicar = ($f['accion_codigo'] === 'ADJUDICAR');
                            ?>
                                <tr>
                                    <td class="p-3">
                                        <div class="fw-bold text-dark small"><?= htmlspecialchars($origen['nombre']) ?></div>
                                        <div class="d-flex align-items-center gap-1 mt-1">
                                            <span class="badge bg-light text-secondary border font-monospace" style="font-size: 9px;"><?= htmlspecialchars($f['estado_actual']) ?></span>
                                            <span class="badge bg-secondary-subtle text-secondary badge-role"><?= htmlspecialchars($origen['rol_responsable']) ?></span>
                                        </div>
                                    </td>

                                    <td class="p-3">
                                        <div class="fw-bold small text-dark"><?= htmlspecialchars($f['accion_label']) ?></div>
                                        <div class="d-flex align-items-center gap-1 mt-1">
                                            <?php if ($es_firma): ?>
                                                <span class="badge bg-primary font-monospace" style="font-size: 9px;"><i class="bi bi-pen-fill me-1"></i><?= htmlspecialchars($f['accion_codigo']) ?></span>
                                            <?php elseif ($es_devolucion): ?>
                                                <span class="badge bg-warning text-dark font-monospace" style="font-size: 9px;"><i class="bi bi-arrow-return-left me-1"></i>DEVOLVER</span>
                                            <?php elseif ($es_rechazo): ?>
                                                <span class="badge bg-danger font-monospace" style="font-size: 9px;"><i class="bi bi-x-circle-fill me-1"></i>RECHAZAR</span>
                                            <?php elseif ($es_adjudicar): ?>
                                                <span class="badge bg-info text-dark font-monospace" style="font-size: 9px;"><i class="bi bi-check2-all me-1"></i>ADJUDICAR</span>
                                            <?php else: ?>
                                                <span class="badge bg-success font-monospace" style="font-size: 9px;"><i class="bi bi-arrow-right-circle me-1"></i>APROBAR</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td class="p-3">
                                        <div class="fw-semibold text-dark small"><?= htmlspecialchars($destino['nombre']) ?></div>
                                        <div class="d-flex align-items-center gap-1 mt-1">
                                            <span class="badge bg-light text-secondary border font-monospace" style="font-size: 9px;"><?= htmlspecialchars($f['estado_destino']) ?></span>
                                            <span class="badge bg-secondary-subtle text-secondary badge-role"><?= htmlspecialchars($destino['rol_responsable']) ?></span>
                                        </div>
                                    </td>

                                    <td class="p-3 text-center">
                                        <div class="d-flex flex-column gap-1 align-items-center">
                                            <?php if ($f['requiere_comentario']): ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size: 9px;">Comentario Obligatorio</span>
                                            <?php endif; ?>
                                            <?php if ($f['requiere_archivo']): ?>
                                                <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size: 9px;">Adjunto Obligatorio</span>
                                            <?php endif; ?>
                                            <?php if ($f['monto_min_utm'] !== null): ?>
                                                <span class="badge bg-dark-subtle text-dark border font-monospace" style="font-size: 9px;">&gt; <?= $f['monto_min_utm'] ?> UTM</span>
                                            <?php endif; ?>
                                            <?php if ($f['monto_max_utm'] !== null): ?>
                                                <span class="badge bg-dark-subtle text-dark border font-monospace" style="font-size: 9px;">&le; <?= $f['monto_max_utm'] ?> UTM</span>
                                            <?php endif; ?>
                                            <?php if (!$f['requiere_comentario'] && !$f['requiere_archivo'] && $f['monto_min_utm'] === null && $f['monto_max_utm'] === null): ?>
                                                <span class="text-muted small" style="font-size: 11px;">-</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td class="p-3 text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <button type="button" class="btn btn-outline-primary btn-sm px-2 py-1 text-xs fw-bold" 
                                                    onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($f), ENT_QUOTES, 'UTF-8') ?>)"
                                                    title="Editar Transición">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <form method="POST" onsubmit="return confirm('¿Está seguro de eliminar esta regla del flujo?')" class="m-0">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                                <input type="hidden" name="accion" value="eliminar_transicion">
                                                <input type="hidden" name="tipo_compra_id" value="<?= $tipo_seleccionado_id ?>">
                                                <input type="hidden" name="transicion_id" value="<?= $f['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm px-2 py-1 text-xs fw-bold" title="Eliminar Transición">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- MODAL: AGREGAR TRANSICIÓN MANUAL -->
    <div class="modal fade" id="modalNuevaTransicion" tabindex="-1" aria-labelledby="modalNuevaTransicionLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-dark text-white py-3">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="modalNuevaTransicionLabel">
                        <i class="bi bi-plus-circle-fill text-primary"></i>
                        Agregar Nueva Transición Manual
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="accion" value="agregar_transicion">
                    <input type="hidden" name="tipo_compra_id" value="<?= $tipo_seleccionado_id ?>">

                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 11px;">Estado Origen</label>
                                <select name="estado_actual" required class="form-select text-sm">
                                    <option value="" disabled selected>-- Seleccione Estado Inicial --</option>
                                    <?php foreach ($estados_agrupados as $rol => $ests): ?>
                                        <optgroup label="Responsable: <?= htmlspecialchars($rol) ?>">
                                            <?php foreach ($ests as $e): ?>
                                                <option value="<?= $e['codigo'] ?>"><?= htmlspecialchars($e['nombre']) ?> (<?= $e['codigo'] ?>)</option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 11px;">Estado Destino</label>
                                <select name="estado_destino" required class="form-select text-sm">
                                    <option value="" disabled selected>-- Seleccione Estado Resultante --</option>
                                    <?php foreach ($estados_agrupados as $rol => $ests): ?>
                                        <optgroup label="Responsable: <?= htmlspecialchars($rol) ?>">
                                            <?php foreach ($ests as $e): ?>
                                                <option value="<?= $e['codigo'] ?>"><?= htmlspecialchars($e['nombre']) ?> (<?= $e['codigo'] ?>)</option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-5">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 11px;">Código de Acción del Sistema</label>
                                <select name="accion_codigo" required class="form-select text-sm">
                                    <optgroup label="Avance Estándar">
                                        <option value="APROBAR">APROBAR (Pase regular)</option>
                                        <option value="ADJUDICAR">ADJUDICAR (Oferta seleccionada)</option>
                                    </optgroup>
                                    <optgroup label="Cadena FirmaGob (Firma Digital)">
                                        <option value="FIRMAR_JEFATURA">FIRMAR_JEFATURA (Firma 1/3 V°B° Jefatura)</option>
                                        <option value="FIRMAR_PRESUPUESTO">FIRMAR_PRESUPUESTO (Firma 2/3 Presupuesto / CDP)</option>
                                        <option value="FIRMAR_CDP_FINANZAS">FIRMAR_CDP_FINANZAS (Firma CDP Oficial Finanzas)</option>
                                        <option value="FIRMAR_ADMIN">FIRMAR_ADMIN (Firma 3/3 Administrador / Folio OPI)</option>
                                    </optgroup>
                                    <optgroup label="Excepciones y Correcciones">
                                        <option value="DEVOLVER">DEVOLVER (Retorno a corrección)</option>
                                        <option value="RECHAZAR">RECHAZAR (Cierre definitivo)</option>
                                    </optgroup>
                                </select>
                            </div>

                            <div class="col-md-7">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 11px;">Etiqueta Visible del Botón</label>
                                <input type="text" name="accion_label" placeholder="Ej: Visar Saldo y Enviar a Adquisiciones" required class="form-control text-sm">
                            </div>

                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded border">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="requiere_comentario" id="add_chk_coment" value="1">
                                        <label class="form-check-label fw-bold small text-dark" for="add_chk_coment">
                                            Requiere Comentario Obligatorio
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="requiere_archivo" id="add_chk_arch" value="1">
                                        <label class="form-check-label fw-bold small text-dark" for="add_chk_arch">
                                            Requiere Archivo Adjunto Obligatorio
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded border">
                                    <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 10px;">Filtro por Monto en UTM (Opcional)</label>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <input type="number" step="0.01" name="monto_min_utm" class="form-control form-control-sm" placeholder="Mínimo UTM">
                                        </div>
                                        <div class="col-6">
                                            <input type="number" step="0.01" name="monto_max_utm" class="form-control form-control-sm" placeholder="Máximo UTM">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light py-2 px-4">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-bold px-3">Crear Transición</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: EDITAR TRANSICIÓN -->
    <div class="modal fade" id="modalEditarTransicion" tabindex="-1" aria-labelledby="modalEditarTransicionLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-dark text-white py-3">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="modalEditarTransicionLabel">
                        <i class="bi bi-pencil-square text-warning"></i>
                        Editar Transición de Flujo
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="accion" value="editar_transicion">
                    <input type="hidden" name="tipo_compra_id" value="<?= $tipo_seleccionado_id ?>">
                    <input type="hidden" name="transicion_id" id="edit-transicion-id">
                    
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 11px;">Estado Origen</label>
                                <select name="estado_actual" id="edit-estado-actual" required class="form-select text-sm">
                                    <?php foreach ($estados_agrupados as $rol => $ests): ?>
                                        <optgroup label="Responsable: <?= htmlspecialchars($rol) ?>">
                                            <?php foreach ($ests as $e): ?>
                                                <option value="<?= $e['codigo'] ?>"><?= htmlspecialchars($e['nombre']) ?> (<?= $e['codigo'] ?>)</option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 11px;">Estado Destino</label>
                                <select name="estado_destino" id="edit-estado-destino" required class="form-select text-sm">
                                    <?php foreach ($estados_agrupados as $rol => $ests): ?>
                                        <optgroup label="Responsable: <?= htmlspecialchars($rol) ?>">
                                            <?php foreach ($ests as $e): ?>
                                                <option value="<?= $e['codigo'] ?>"><?= htmlspecialchars($e['nombre']) ?> (<?= $e['codigo'] ?>)</option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-5">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 11px;">Código de Acción</label>
                                <select name="accion_codigo" id="edit-accion-codigo" required class="form-select text-sm">
                                    <optgroup label="Avance Estándar">
                                        <option value="APROBAR">APROBAR (Pase regular)</option>
                                        <option value="ADJUDICAR">ADJUDICAR (Oferta seleccionada)</option>
                                    </optgroup>
                                    <optgroup label="Cadena FirmaGob (Firma Digital)">
                                        <option value="FIRMAR_JEFATURA">FIRMAR_JEFATURA (Firma 1/3 V°B° Jefatura)</option>
                                        <option value="FIRMAR_PRESUPUESTO">FIRMAR_PRESUPUESTO (Firma 2/3 Presupuesto / CDP)</option>
                                        <option value="FIRMAR_CDP_FINANZAS">FIRMAR_CDP_FINANZAS (Firma CDP Oficial Finanzas)</option>
                                        <option value="FIRMAR_ADMIN">FIRMAR_ADMIN (Firma 3/3 Administrador / Folio OPI)</option>
                                    </optgroup>
                                    <optgroup label="Excepciones y Correcciones">
                                        <option value="DEVOLVER">DEVOLVER (Retorno a corrección)</option>
                                        <option value="RECHAZAR">RECHAZAR (Cierre definitivo)</option>
                                    </optgroup>
                                </select>
                            </div>

                            <div class="col-md-7">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 11px;">Etiqueta Visible del Botón</label>
                                <input type="text" name="accion_label" id="edit-accion-label" required class="form-control text-sm">
                            </div>

                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded border">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="requiere_comentario" id="edit-requiere-comentario" value="1">
                                        <label class="form-check-label fw-bold small text-dark" for="edit-requiere-comentario">
                                            Requiere Comentario Obligatorio
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="requiere_archivo" id="edit-requiere-archivo" value="1">
                                        <label class="form-check-label fw-bold small text-dark" for="edit-requiere-archivo">
                                            Requiere Archivo Adjunto Obligatorio
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded border">
                                    <label class="form-label fw-bold text-secondary small text-uppercase mb-1" style="font-size: 10px;">Filtro por Monto en UTM (Opcional)</label>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <input type="number" step="0.01" name="monto_min_utm" id="edit-monto-min" class="form-control form-control-sm" placeholder="Mínimo UTM">
                                        </div>
                                        <div class="col-6">
                                            <input type="number" step="0.01" name="monto_max_utm" id="edit-monto-max" class="form-control form-control-sm" placeholder="Máximo UTM">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light py-2 px-4">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-bold px-3">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: RESTAURAR FLUJO OFICIAL DE FÁBRICA -->
    <div class="modal fade" id="modalRestaurarOficial" tabindex="-1" aria-labelledby="modalRestaurarOficialLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-danger text-white py-3">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="modalRestaurarOficialLabel">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        Restaurar Flujo Oficial de Fábrica
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="accion" value="restaurar_oficial">
                    <input type="hidden" name="tipo_compra_id" value="<?= $tipo_seleccionado_id ?>">

                    <div class="modal-body p-4 text-center">
                        <div class="mb-3 text-warning fs-1">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">¿Restaurar reglas oficiales para <?= htmlspecialchars($tipo_actual['nombre'] ?? '') ?>?</h5>
                        <p class="text-muted small leading-relaxed mb-0">
                            Esta acción eliminará cualquier personalización manual sobre esta modalidad y cargará exactamente la matriz oficial verificada para el ciclo institucional OPIv2 y FirmaGob v18.
                        </p>
                    </div>

                    <div class="modal-footer bg-light py-2 px-4">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger btn-sm fw-bold px-3">Confirmar y Restaurar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: CLONAR FLUJO -->
    <div class="modal fade" id="modalClonar" tabindex="-1" aria-labelledby="modalClonarLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-dark text-white py-3">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="modalClonarLabel">
                        <i class="bi bi-copy text-info"></i>
                        Clonar Reglas entre Modalidades
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" onsubmit="return confirm('¿Está seguro de sobrescribir el flujo de destino?')">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="accion" value="clonar_flujo">
                    <input type="hidden" name="tipo_compra_id" value="<?= $tipo_seleccionado_id ?>">

                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 11px;">Modalidad de Origen (Copiar desde)</label>
                            <select name="origen_tipo_id" class="form-select text-sm">
                                <?php foreach ($tipos_compra as $tc): ?>
                                    <option value="<?= $tc['id'] ?>" <?= $tc['id'] == $tipo_seleccionado_id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($tc['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 11px;">Modalidad de Destino (Sobrescribir en)</label>
                            <select name="destino_tipo_id" class="form-select text-sm">
                                <option value="" disabled selected>-- Seleccione Destino --</option>
                                <?php foreach ($tipos_compra as $tc): ?>
                                    <?php if ($tc['id'] != $tipo_seleccionado_id): ?>
                                        <option value="<?= $tc['id'] ?>">
                                            <?= htmlspecialchars($tc['nombre']) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="alert alert-warning small mb-0 py-2">
                            <i class="bi bi-exclamation-circle-fill me-1"></i>
                            El flujo de destino será reemplazado completamente por las reglas del origen.
                        </div>
                    </div>

                    <div class="modal-footer bg-light py-2 px-4">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-bold px-3">Copiar y Clonar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SCRIPT DE INICIALIZACIÓN DE MODAL EDITAR -->
    <script>
        function abrirModalEditar(transicion) {
            document.getElementById('edit-transicion-id').value = transicion.id;
            document.getElementById('edit-estado-actual').value = transicion.estado_actual;
            document.getElementById('edit-estado-destino').value = transicion.estado_destino;
            document.getElementById('edit-accion-codigo').value = transicion.accion_codigo;
            document.getElementById('edit-accion-label').value = transicion.accion_label;
            document.getElementById('edit-requiere-comentario').checked = parseInt(transicion.requiere_comentario) === 1;
            document.getElementById('edit-requiere-archivo').checked = parseInt(transicion.requiere_archivo) === 1;
            document.getElementById('edit-monto-min').value = (transicion.monto_min_utm !== null && transicion.monto_min_utm !== undefined) ? transicion.monto_min_utm : '';
            document.getElementById('edit-monto-max').value = (transicion.monto_max_utm !== null && transicion.monto_max_utm !== undefined) ? transicion.monto_max_utm : '';
            
            const modalEl = document.getElementById('modalEditarTransicion');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    </script>

<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>