<?php
// mantenedor_flujos.php - Asistente e Interfaz de Control de Flujos V6.0
require_once __DIR__ . '/config.php';

// 1. SEGURIDAD
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$rol = $_SESSION['user_rol'] ?? '';
if ($rol !== 'PRESUPUESTO' && $rol !== 'ADMIN_MUNICIPAL' && $rol !== 'SYSADMIN') {
    die("Acceso Denegado. Solo Administración puede modificar los flujos del sistema.");
}

$mensaje = ''; $tipo_mensaje = '';
$tipo_seleccionado_id = $_GET['tipo'] ?? null;

// --- SECUENCIA MAESTRA DE ESTADOS ---
$pasos_maestros = [
    'BORRADOR' => ['nombre' => 'Ingreso de Solicitud (Borrador)', 'rol' => 'USUARIO_REQ', 'fijo' => true, 'def_label' => 'Crear Requerimiento'],
    'EN_REVISION_JEFATURA' => ['nombre' => 'Visación de Jefatura', 'rol' => 'JEFE_UNIDAD', 'fijo' => false, 'def_label' => 'Enviar a V°B° Jefatura'],
    'EN_VALIDACION_PRESUPUESTARIA' => ['nombre' => 'Reserva de Presupuesto Inicial', 'rol' => 'PRESUPUESTO', 'fijo' => false, 'def_label' => 'Enviar a Reserva de Fondos'],
    'EN_COTIZACION_ADQ' => ['nombre' => 'Búsqueda de Cotizaciones (Adquisiciones)', 'rol' => 'ADQUISICIONES', 'fijo' => false, 'def_label' => 'Enviar a Adquisiciones'],
    'EN_VALIDACION_PRESUPUESTARIA_FINAL' => ['nombre' => 'Visación de Gasto Definitivo (Presupuesto)', 'rol' => 'PRESUPUESTO', 'fijo' => false, 'def_label' => 'Enviar a Visación Final'],
    'EN_APROBACION_ADMINISTRADOR' => ['nombre' => 'Firma y Emisión de OPI (Administración)', 'rol' => 'ADMIN_MUNICIPAL', 'fijo' => false, 'def_label' => 'Enviar a Firma de Administrador'],
    'EN_EMISION_OC' => ['nombre' => 'Emisión y Envío de Orden de Compra', 'rol' => 'ADQUISICIONES', 'fijo' => false, 'def_label' => 'Enviar a Emisión de OC'],
    'ESPERANDO_ACEPTACION_OC' => ['nombre' => 'Esperando Aceptación del Proveedor', 'rol' => 'ADQUISICIONES', 'fijo' => false, 'def_label' => 'Enviar a Espera de Aceptación'],
    'FINALIZADO' => ['nombre' => 'Proceso Finalizado Exitosamente', 'rol' => 'SISTEMA', 'fijo' => true, 'def_label' => 'Finalizar Proceso']
];

// --- LÓGICA POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $tipo_compra_id = $_POST['tipo_compra_id'] ?? null;
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'guardar_wizard') {
            $pasos_seleccionados = $_POST['pasos'] ?? [];

            // Forzar primer y último paso
            if (!in_array('BORRADOR', $pasos_seleccionados)) array_unshift($pasos_seleccionados, 'BORRADOR');
            if (!in_array('FINALIZADO', $pasos_seleccionados)) $pasos_seleccionados[] = 'FINALIZADO';

            // Filtrar manteniendo el orden cronológico del maestro
            $pasos_activos = [];
            foreach ($pasos_maestros as $code => $info) {
                if (in_array($code, $pasos_seleccionados)) {
                    $pasos_activos[] = $code;
                }
            }

            $pdo->beginTransaction();

            // 1. Limpiar transiciones previas
            $pdo->prepare("DELETE FROM flujos_definicion WHERE tipo_compra_id = ?")->execute([$tipo_compra_id]);

            // 2. Generar nuevas transiciones
            for ($i = 0; $i < count($pasos_activos) - 1; $i++) {
                $actual = $pasos_activos[$i];
                $siguiente = $pasos_activos[$i + 1];

                $condicion_monto = isset($_POST['condicion_' . $siguiente]) ? floatval($_POST['monto_limite_' . $siguiente]) : null;

                if ($condicion_monto !== null && $condicion_monto > 0) {
                    // Transición A: Si supera el monto, pasa por este estado
                    $label_si = $pasos_maestros[$siguiente]['def_label'] . " (> " . $condicion_monto . " UTM)";
                    $stmt = $pdo->prepare("
                        INSERT INTO flujos_definicion (tipo_compra_id, estado_actual, accion_codigo, accion_label, estado_destino, monto_min_utm)
                        VALUES (?, ?, 'APROBAR', ?, ?, ?)
                    ");
                    $stmt->execute([$tipo_compra_id, $actual, $label_si, $siguiente, $condicion_monto + 0.01]);

                    // Transición B: Si es menor o igual, se salta este estado y va al subsiguiente
                    $subsiguiente = $pasos_activos[$i + 2] ?? 'FINALIZADO';
                    $label_no = "Avanzar sin " . $pasos_maestros[$siguiente]['nombre'] . " (<= " . $condicion_monto . " UTM)";
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO flujos_definicion (tipo_compra_id, estado_actual, accion_codigo, accion_label, estado_destino, monto_min_utm, monto_max_utm)
                        VALUES (?, ?, 'APROBAR', ?, ?, 0.00, ?)
                    ");
                    $stmt->execute([$tipo_compra_id, $actual, $label_no, $subsiguiente, $condicion_monto]);

                } else {
                    // Avance directo lineal
                    $label = $siguiente === 'FINALIZADO' ? 'Finalizar y Cerrar Compra' : $pasos_maestros[$siguiente]['def_label'];
                    $stmt = $pdo->prepare("
                        INSERT INTO flujos_definicion (tipo_compra_id, estado_actual, accion_codigo, accion_label, estado_destino)
                        VALUES (?, ?, 'APROBAR', ?, ?)
                    ");
                    $stmt->execute([$tipo_compra_id, $actual, $label, $siguiente]);
                }

                // Generar devoluciones y rechazos para el estado actual (si no es BORRADOR)
                if ($actual !== 'BORRADOR') {
                    $destino_dev = $_POST['devolucion_' . $actual] ?? 'EN_CORRECCION';
                    
                    // A. Devolución
                    $stmt = $pdo->prepare("
                        INSERT INTO flujos_definicion (tipo_compra_id, estado_actual, accion_codigo, accion_label, estado_destino, requiere_comentario)
                        VALUES (?, ?, 'DEVOLVER', ?, ?, 1)
                    ");
                    $stmt->execute([$tipo_compra_id, $actual, "Devolver Trámite", $destino_dev]);

                    // B. Rechazo Definitivo
                    $stmt = $pdo->prepare("
                        INSERT INTO flujos_definicion (tipo_compra_id, estado_actual, accion_codigo, accion_label, estado_destino, requiere_comentario)
                        VALUES (?, ?, 'RECHAZAR', 'Rechazar y Cerrar Requerimiento', 'RECHAZADO', 1)
                    ");
                    $stmt->execute([$tipo_compra_id, $actual]);
                }
            }

            $pdo->commit();
            $mensaje = "Flujo creado e instalado correctamente con el Asistente.";
            $tipo_mensaje = "success";
            $tipo_seleccionado_id = $tipo_compra_id;
        }

        if ($accion === 'agregar_transicion') {
            $origen = $_POST['estado_actual'];
            $destino = $_POST['estado_destino'];
            $accion_codigo = $_POST['accion_codigo'];
            $accion_label = trim($_POST['accion_label']);
            $requiere_comentario = isset($_POST['requiere_comentario']) ? 1 : 0;
            $monto_min = !empty($_POST['monto_min_utm']) ? floatval($_POST['monto_min_utm']) : null;
            $monto_max = !empty($_POST['monto_max_utm']) ? floatval($_POST['monto_max_utm']) : null;

            if ($origen === $destino) throw new Exception("El estado origen y destino no pueden ser el mismo.");
            if (empty($accion_label)) throw new Exception("La etiqueta de la acción es requerida.");

            $stmt = $pdo->prepare("
                INSERT INTO flujos_definicion (tipo_compra_id, estado_actual, accion_codigo, accion_label, estado_destino, requiere_comentario, monto_min_utm, monto_max_utm)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$tipo_compra_id, $origen, $accion_codigo, $accion_label, $destino, $requiere_comentario, $monto_min, $monto_max]);
            
            $mensaje = "Transición agregada correctamente.";
            $tipo_mensaje = "success";
            $tipo_seleccionado_id = $tipo_compra_id;
        }

        if ($accion === 'editar_transicion') {
            $transicion_id = $_POST['transicion_id'] ?? null;
            $origen = $_POST['estado_actual'];
            $destino = $_POST['estado_destino'];
            $accion_codigo = $_POST['accion_codigo'];
            $accion_label = trim($_POST['accion_label']);
            $requiere_comentario = isset($_POST['requiere_comentario']) ? 1 : 0;
            $monto_min = !empty($_POST['monto_min_utm']) ? floatval($_POST['monto_min_utm']) : null;
            $monto_max = !empty($_POST['monto_max_utm']) ? floatval($_POST['monto_max_utm']) : null;

            if (empty($transicion_id)) throw new Exception("ID de transición no válido.");
            if ($origen === $destino) throw new Exception("El estado origen y destino no pueden ser el mismo.");
            if (empty($accion_label)) throw new Exception("La etiqueta de la acción es requerida.");

            $stmt = $pdo->prepare("
                UPDATE flujos_definicion 
                SET estado_actual = ?, 
                    accion_codigo = ?, 
                    accion_label = ?, 
                    estado_destino = ?, 
                    requiere_comentario = ?, 
                    monto_min_utm = ?, 
                    monto_max_utm = ?
                WHERE id = ? AND tipo_compra_id = ?
            ");
            $stmt->execute([$origen, $accion_codigo, $accion_label, $destino, $requiere_comentario, $monto_min, $monto_max, $transicion_id, $tipo_compra_id]);
            
            $mensaje = "Transición editada correctamente.";
            $tipo_mensaje = "success";
            $tipo_seleccionado_id = $tipo_compra_id;
        }

        if ($accion === 'eliminar_transicion') {
            $transicion_id = $_POST['transicion_id'];
            $pdo->prepare("DELETE FROM flujos_definicion WHERE id = ?")->execute([$transicion_id]);
            $mensaje = "La transición fue eliminada del flujo.";
            $tipo_mensaje = "success";
        }

        if ($accion === 'clonar_flujo') {
            $origen_id = $_POST['origen_tipo_id'];
            $destino_id = $_POST['destino_tipo_id'];

            if ($origen_id == $destino_id) throw new Exception("El flujo de origen y de destino deben ser diferentes.");

            $pdo->prepare("DELETE FROM flujos_definicion WHERE tipo_compra_id = ?")->execute([$destino_id]);

            $stmtCopy = $pdo->prepare("
                INSERT INTO flujos_definicion (tipo_compra_id, estado_actual, accion_codigo, accion_label, estado_destino, requiere_comentario, monto_min_utm, monto_max_utm)
                SELECT ?, estado_actual, accion_codigo, accion_label, estado_destino, requiere_comentario, monto_min_utm, monto_max_utm
                FROM flujos_definicion WHERE tipo_compra_id = ?
            ");
            $stmtCopy->execute([$destino_id, $origen_id]);

            $mensaje = "El flujo fue clonado exitosamente.";
            $tipo_mensaje = "success";
            $tipo_seleccionado_id = $destino_id;
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $mensaje = $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// --- DATOS MAESTROS ---
$tipos_compra = $pdo->query("SELECT * FROM tipos_compra WHERE activo = 1 ORDER BY id ASC")->fetchAll();
$estados_raw = $pdo->query("SELECT * FROM estados_tramite ORDER BY rol_responsable ASC, nombre ASC")->fetchAll();

$mapa_estados = [];
$estados_agrupados = [];
foreach ($estados_raw as $e) {
    $mapa_estados[$e['codigo']] = $e;
    $estados_agrupados[$e['rol_responsable']][] = $e;
}

if (!$tipo_seleccionado_id && !empty($tipos_compra)) {
    $tipo_seleccionado_id = $tipos_compra[0]['id'];
}

// Cargar flujo actual
$flujos_raw = [];
if ($tipo_seleccionado_id) {
    $stmtF = $pdo->prepare("SELECT * FROM flujos_definicion WHERE tipo_compra_id = ?");
    $stmtF->execute([$tipo_seleccionado_id]);
    $flujos_raw = $stmtF->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php 
    $titulo_pagina = "Diseñador de Flujos Dinámicos";
    include __DIR__ . '/head.php'; 
    ?>
</head>
<body class="bg-light text-slate-800 font-sans pb-20">

    <?php include __DIR__ . '/nav.php'; ?>

    <!-- Inclusión de Bootstrap 5 JS Bundle después de nav.php -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <div class="container mt-4 px-3 px-md-4">

        <!-- CABECERA -->
        <div class="row align-items-center mb-4 g-3">
            <div class="col-12 col-md">
                <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                    <i class="bi bi-gear-wide-connected text-primary"></i>
                    Configuración de Flujos de Compra
                </h1>
                <p class="text-muted small mb-0">Usa el Asistente Guiado para crear rutas en segundos o accede al modo avanzado para mayor control.</p>
            </div>
            <div class="col-12 col-md-auto text-start text-md-end">
                <a href="mis_solicitudes.php" class="btn btn-outline-secondary btn-sm px-3 shadow-sm font-bold">
                    <i class="bi bi-arrow-left me-1"></i> Volver
                </a>
            </div>
        </div>

        <!-- ALERTAS -->
        <?php if($mensaje): ?>
            <?php 
            $alertClass = ($tipo_mensaje === 'error') ? 'danger' : 'success';
            $iconClass = ($tipo_mensaje === 'error') ? 'exclamation-triangle-fill' : 'check-circle-fill';
            ?>
            <div class="alert alert-<?= $alertClass ?> d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
                <i class="bi bi-<?= $iconClass ?> shrink-0"></i>
                <div class="small fw-semibold"><?= htmlspecialchars($mensaje) ?></div>
            </div>
        <?php endif; ?>

        <!-- Tabs de Selección -->
        <ul class="nav nav-tabs mb-4 bg-white rounded-top border px-3 pt-2">
            <li class="nav-item">
                <button onclick="switchTab('wizard')" id="btn-tab-wizard" class="nav-link active fw-bold text-sm">
                    <i class="bi bi-magic me-1"></i> Asistente Guiado (Recomendado)
                </button>
            </li>
            <li class="nav-item">
                <button onclick="switchTab('advanced')" id="btn-tab-advanced" class="nav-link fw-bold text-sm">
                    <i class="bi bi-sliders me-1"></i> Configuración Avanzada
                </button>
            </li>
        </ul>

        <!-- PANEL 1: WIZARD -->
        <div id="panel-wizard" class="tab-panel">
            <div class="row g-4">
                
                <!-- Sidebar del Wizard -->
                <div class="col-lg-3">
                    <div class="card shadow-sm border-light">
                        <div class="card-header bg-white py-3">
                            <h6 class="fw-bold mb-0 text-secondary small text-uppercase" style="font-size: 10px;">Progreso Asistente</h6>
                        </div>
                        <div class="list-group list-group-flush" id="wizard-steps-indicators">
                            <div class="list-group-item d-flex align-items-center gap-2 text-primary fw-bold py-3" id="ind-step-1">
                                <span class="badge bg-primary rounded-circle p-2 d-inline-flex justify-content-center align-items-center" style="width: 22px; height: 22px;">1</span>
                                <span class="small">Inicialización</span>
                            </div>
                            <div class="list-group-item d-flex align-items-center gap-2 text-muted py-3" id="ind-step-2">
                                <span class="badge bg-secondary rounded-circle p-2 d-inline-flex justify-content-center align-items-center" style="width: 22px; height: 22px;">2</span>
                                <span class="small">Ruta Feliz (Pasos)</span>
                            </div>
                            <div class="list-group-item d-flex align-items-center gap-2 text-muted py-3" id="ind-step-3">
                                <span class="badge bg-secondary rounded-circle p-2 d-inline-flex justify-content-center align-items-center" style="width: 22px; height: 22px;">3</span>
                                <span class="small">Devoluciones</span>
                            </div>
                            <div class="list-group-item d-flex align-items-center gap-2 text-muted py-3" id="ind-step-4">
                                <span class="badge bg-secondary rounded-circle p-2 d-inline-flex justify-content-center align-items-center" style="width: 22px; height: 22px;">4</span>
                                <span class="small">Guardar</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulario del Wizard -->
                <div class="col-lg-9">
                    <form method="POST" id="wizard-form" class="card shadow-sm border-light p-4">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <input type="hidden" name="accion" value="guardar_wizard">

                        <!-- WIZARD STEP 1: Selección de Tipo -->
                        <div id="w-step-1" class="step-panel active">
                            <h5 class="fw-bold text-dark mb-1">Paso 1: ¿A qué flujo aplicamos este diseño?</h5>
                            <p class="text-muted small mb-4">Selecciona el tipo de compra. El asistente reemplazará el flujo existente.</p>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Tipo de Compra</label>
                                <select name="tipo_compra_id" required class="form-select text-sm">
                                    <?php foreach ($tipos_compra as $tc): ?>
                                        <option value="<?= $tc['id'] ?>" <?= $tc['id'] == $tipo_seleccionado_id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($tc['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- WIZARD STEP 2: Ruta Feliz -->
                        <div id="w-step-2" class="step-panel d-none">
                            <h5 class="fw-bold text-dark mb-1">Paso 2: Define las etapas de la compra</h5>
                            <p class="text-muted small mb-4">Marca las visaciones y gestiones por las que debe pasar la solicitud.</p>

                            <div class="row g-3 mb-3">
                                <?php foreach ($pasos_maestros as $code => $info): ?>
                                    <div class="col-12">
                                        <div class="card p-3 border-light shadow-sm bg-light-subtle">
                                            <div class="d-flex align-items-start gap-3">
                                                <input type="checkbox" name="pasos[]" value="<?= $code ?>" id="chk_<?= $code ?>" 
                                                    <?= $info['fijo'] ? 'checked disabled' : '' ?> 
                                                    class="form-check-input mt-1 step-checkbox"
                                                    onchange="toggleConditionalOption('<?= $code ?>')">
                                                
                                                <!-- Enviar campo hidden si es fijo para asegurar que se reciba en post -->
                                                <?php if($info['fijo']): ?>
                                                    <input type="hidden" name="pasos[]" value="<?= $code ?>">
                                                <?php endif; ?>

                                                <div class="flex-grow-1">
                                                    <label for="chk_<?= $code ?>" class="fw-bold text-dark mb-0 cursor-pointer block">
                                                        <?= htmlspecialchars($info['nombre']) ?>
                                                    </label>
                                                    <span class="text-muted d-block uppercase tracking-wider mt-0.5" style="font-size: 9px; font-weight: bold;">
                                                        Responsable: <?= htmlspecialchars($info['rol']) ?>
                                                    </span>

                                                    <!-- Opciones condicionales por montos -->
                                                    <?php if(!$info['fijo']): ?>
                                                        <div class="mt-3 bg-white p-3 rounded border d-none" id="cond-box-<?= $code ?>">
                                                            <div class="form-check mb-1">
                                                                <input type="checkbox" id="cond_toggle_<?= $code ?>" name="condicion_<?= $code ?>" class="form-check-input" onchange="toggleLimitInput('<?= $code ?>')">
                                                                <label for="cond_toggle_<?= $code ?>" class="form-check-label text-xs fw-semibold text-secondary">Saltar este paso si el monto es bajo</label>
                                                            </div>
                                                            <div class="mt-2 d-flex align-items-center gap-2 text-xs text-secondary d-none" id="limit-input-<?= $code ?>">
                                                                <span>Aplicar paso solo si el monto es mayor a:</span>
                                                                <input type="number" step="0.01" name="monto_limite_<?= $code ?>" class="form-control form-control-sm text-center fw-bold" style="width: 80px;">
                                                                <span>UTM</span>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- WIZARD STEP 3: Devoluciones -->
                        <div id="w-step-3" class="step-panel d-none">
                            <h5 class="fw-bold text-dark mb-1">Paso 3: ¿Dónde devolver en caso de observaciones?</h5>
                            <p class="text-muted small mb-4">Si alguna jefatura o presupuesto rechaza provisoriamente, ¿a qué etapa regresará el expediente?</p>

                            <div class="row g-3 mb-3" id="devoluciones-container">
                                <!-- Se llena dinámicamente con JS -->
                            </div>
                        </div>

                        <!-- WIZARD STEP 4: Confirmación -->
                        <div id="w-step-4" class="step-panel d-none text-center py-4">
                            <div class="w-16 h-16 bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 64px; height: 64px; font-size: 28px;">
                                <i class="bi bi-check2-all"></i>
                            </div>
                            <h4 class="fw-bold text-dark">¡Todo Listo!</h4>
                            <p class="text-muted small max-w-md mx-auto mb-4">Al presionar guardar, el asistente limpiará la ruta anterior del tipo de compra seleccionado y generará todas las transiciones lógicas (avances, devoluciones y condicionales UTM).</p>

                            <div class="pt-2">
                                <button type="submit" class="btn btn-primary fw-bold px-4 py-2.5 shadow-sm">
                                    Generar e Instalar Flujo
                                </button>
                            </div>
                        </div>

                        <!-- Botones de Navegación del Wizard -->
                        <div class="border-top pt-3 d-flex justify-content-between">
                            <button type="button" id="wizard-prev-btn" onclick="prevStep()" class="btn btn-outline-secondary btn-sm px-4 d-none">
                                <i class="bi bi-arrow-left me-1"></i> Anterior
                            </button>
                            <button type="button" id="wizard-next-btn" onclick="nextStep()" class="btn btn-dark btn-sm px-4 ms-auto">
                                Siguiente <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <!-- PANEL 2: ADVANCED -->
        <div id="panel-advanced" class="tab-panel d-none">
            <div class="row g-4">
                
                <!-- Sidebar Avanzado -->
                <div class="col-lg-3">
                    <!-- Lista de flujos -->
                    <div class="card shadow-sm border-light mb-4">
                        <div class="card-header bg-white py-3">
                            <h6 class="fw-bold mb-0 text-secondary small text-uppercase" style="font-size: 10px;">Lista de Flujos</h6>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php foreach($tipos_compra as $tc): ?>
                                <a href="?tipo=<?= $tc['id'] ?>&tab=advanced" class="list-group-item list-group-item-action py-3 small fw-bold <?= $tc['id'] == $tipo_seleccionado_id ? 'active' : '' ?>">
                                    <?= htmlspecialchars($tc['nombre']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Clonar -->
                    <div class="card shadow-sm border-light">
                        <div class="card-header bg-white py-3">
                            <h6 class="fw-bold mb-0 text-secondary small text-uppercase" style="font-size: 10px;">Clonar Flujo</h6>
                        </div>
                        <div class="card-body p-3">
                            <form method="POST" onsubmit="return confirm('¿Copiar este flujo?')">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                <input type="hidden" name="accion" value="clonar_flujo">
                                <div class="mb-3">
                                    <label class="form-label text-secondary small text-uppercase" style="font-size: 9px; font-weight: bold;">Origen</label>
                                    <select name="origen_tipo_id" class="form-select form-select-sm text-xs">
                                        <?php foreach($tipos_compra as $tc): ?>
                                            <option value="<?= $tc['id'] ?>"><?= htmlspecialchars($tc['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-secondary small text-uppercase" style="font-size: 9px; font-weight: bold;">Destino</label>
                                    <select name="destino_tipo_id" class="form-select form-select-sm text-xs">
                                        <?php foreach($tipos_compra as $tc): ?>
                                            <option value="<?= $tc['id'] ?>" <?= $tc['id'] == $tipo_seleccionado_id ? 'selected' : '' ?>><?= htmlspecialchars($tc['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-dark btn-sm w-100 fw-bold">Clonar Flujo</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Detalle de transiciones -->
                <div class="col-lg-9">
                    <div class="card shadow-sm border-light mb-4">
                        <div class="card-header bg-white py-3">
                            <h6 class="fw-bold mb-0 text-dark">Transiciones de: <?= htmlspecialchars($tipos_compra[array_search($tipo_seleccionado_id, array_column($tipos_compra, 'id'))]['nombre'] ?? '') ?></h6>
                        </div>
                        <div class="table-responsive rounded-bottom">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-uppercase small" style="font-size: 10px;">
                                    <tr>
                                        <th class="p-3">Origen</th>
                                        <th class="p-3">Acción</th>
                                        <th class="p-3">Destino</th>
                                        <th class="p-3 text-center" style="width: 140px;">Restricciones</th>
                                        <th class="p-3 text-center" style="width: 120px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    <?php if (empty($flujos_raw)): ?>
                                        <tr><td colspan="5" class="p-4 text-center text-muted italic">No hay transiciones configuradas en este flujo.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($flujos_raw as $f): 
                                        $origen = $mapa_estados[$f['estado_actual']] ?? ['nombre' => $f['estado_actual'], 'rol_responsable' => 'SISTEMA'];
                                        $destino = $mapa_estados[$f['estado_destino']] ?? ['nombre' => $f['estado_destino'], 'rol_responsable' => 'SISTEMA'];
                                    ?>
                                    <tr>
                                        <td class="p-3">
                                            <div class="fw-bold text-dark small"><?= htmlspecialchars($origen['nombre']) ?></div>
                                            <span class="text-muted uppercase" style="font-size: 8px; font-weight: bold;"><?= htmlspecialchars($origen['rol_responsable']) ?></span>
                                        </td>
                                        <td class="p-3">
                                            <div class="fw-bold text-primary small"><?= htmlspecialchars($f['accion_label']) ?></div>
                                            <span class="badge bg-light text-secondary font-monospace border mt-0.5" style="font-size: 8px;"><?= htmlspecialchars($f['accion_code'] ?? $f['accion_codigo']) ?></span>
                                        </td>
                                        <td class="p-3">
                                            <div class="fw-semibold text-dark small"><?= htmlspecialchars($destino['nombre']) ?></div>
                                            <span class="text-muted uppercase" style="font-size: 8px; font-weight: bold;"><?= htmlspecialchars($destino['rol_responsable']) ?></span>
                                        </td>
                                        <td class="p-3 text-center">
                                            <div class="d-flex flex-column gap-1 align-items-center">
                                                <?php if ($f['requiere_comentario']): ?><span class="badge bg-warning-subtle text-warning border border-warning-subtle text-uppercase" style="font-size: 7px;">Comentario</span><?php endif; ?>
                                                <?php if ($f['monto_min_utm'] !== null): ?><span class="badge bg-info-subtle text-info border border-info-subtle font-monospace" style="font-size: 8px;">&gt; <?= $f['monto_min_utm'] ?> UTM</span><?php endif; ?>
                                                <?php if ($f['monto_max_utm'] !== null): ?><span class="badge bg-info-subtle text-info border border-info-subtle font-monospace" style="font-size: 8px;">&lt;= <?= $f['monto_max_utm'] ?> UTM</span><?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="p-3 text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <button type="button" class="btn btn-outline-primary btn-sm px-2 py-1 text-xs fw-bold" 
                                                        onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($f), ENT_QUOTES, 'UTF-8') ?>)"
                                                        title="Editar Transición">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" onsubmit="return confirm('¿Eliminar esta transición?')" class="m-0">
                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                                    <input type="hidden" name="accion" value="eliminar_transicion">
                                                    <input type="hidden" name="tipo_compra_id" value="<?= $tipo_seleccionado_id ?>">
                                                    <input type="hidden" name="transicion_id" value="<?= $f['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm px-2 py-1 text-xs fw-bold" title="Eliminar Transición">&times;</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Agregar transicion manual -->
                    <div class="card shadow-sm border-light">
                        <div class="card-header bg-white py-3">
                            <h6 class="fw-bold mb-0 text-dark">Agregar Transición Manual (Avanzado)</h6>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                <input type="hidden" name="accion" value="agregar_transicion">
                                <input type="hidden" name="tipo_compra_id" value="<?= $tipo_seleccionado_id ?>">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Origen</label>
                                        <select name="estado_actual" required class="form-select text-sm">
                                            <?php foreach($estados_agrupados as $rol => $ests): ?>
                                                <optgroup label="<?= $rol ?>">
                                                    <?php foreach($ests as $e): ?>
                                                        <option value="<?= $e['codigo'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Destino</label>
                                        <select name="estado_destino" required class="form-select text-sm">
                                            <?php foreach($estados_agrupados as $rol => $ests): ?>
                                                <optgroup label="<?= $rol ?>">
                                                    <?php foreach($ests as $e): ?>
                                                        <option value="<?= $e['codigo'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Código Acción</label>
                                        <select name="accion_codigo" class="form-select text-sm">
                                            <option value="APROBAR">APROBAR</option>
                                            <option value="DEVOLVER">DEVOLVER</option>
                                            <option value="RECHAZAR">RECHAZAR</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Etiqueta de Acción</label>
                                        <input type="text" name="accion_label" required class="form-control text-sm">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm fw-bold px-4">Agregar Transición</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Script de Control del Wizard y Pestañas -->
    <script>
        let currentStep = 1;
        const pasosMaestros = <?= json_encode($pasos_maestros) ?>;

        function switchTab(tab) {
            document.querySelectorAll('.nav-link').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('d-none'));

            if (tab === 'wizard') {
                document.getElementById('btn-tab-wizard').classList.add('active');
                document.getElementById('panel-wizard').classList.remove('d-none');
            } else {
                document.getElementById('btn-tab-advanced').classList.add('active');
                document.getElementById('panel-advanced').classList.remove('d-none');
            }
        }

        // Recuperar pestaña activa por url param
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('tab') === 'advanced') {
            switchTab('advanced');
        }

        function toggleConditionalOption(code) {
            const box = document.getElementById('cond-box-' + code);
            const chk = document.getElementById('chk_' + code);
            if (box && chk) {
                if (chk.checked) {
                    box.classList.remove('d-none');
                } else {
                    box.classList.add('d-none');
                }
            }
        }

        function toggleLimitInput(code) {
            const input = document.getElementById('limit-input-' + code);
            const toggle = document.getElementById('cond_toggle_' + code);
            if (input && toggle) {
                if (toggle.checked) {
                    input.classList.remove('d-none');
                } else {
                    input.classList.add('d-none');
                }
            }
        }

        // Navegación del Wizard
        function showStep(step) {
            document.querySelectorAll('.step-panel').forEach(p => p.classList.add('d-none'));
            document.getElementById('w-step-' + step).classList.remove('d-none');

            // Actualizar indicadores
            for (let i = 1; i <= 4; i++) {
                const ind = document.getElementById('ind-step-' + i);
                if (ind) {
                    if (i === step) {
                        ind.className = "list-group-item d-flex align-items-center gap-2 text-primary fw-bold py-3";
                        ind.querySelector('span').className = "badge bg-primary rounded-circle p-2 d-inline-flex justify-content-center align-items-center";
                    } else if (i < step) {
                        ind.className = "list-group-item d-flex align-items-center gap-2 text-secondary py-3";
                        ind.querySelector('span').className = "badge bg-secondary rounded-circle p-2 d-inline-flex justify-content-center align-items-center";
                    } else {
                        ind.className = "list-group-item d-flex align-items-center gap-2 text-muted py-3";
                        ind.querySelector('span').className = "badge bg-light text-secondary rounded-circle p-2 d-inline-flex justify-content-center align-items-center border";
                    }
                }
            }

            // Mostrar/ocultar botones
            if (step === 1) {
                document.getElementById('wizard-prev-btn').classList.add('d-none');
            } else {
                document.getElementById('wizard-prev-btn').classList.remove('d-none');
            }

            if (step === 4) {
                document.getElementById('wizard-next-btn').classList.add('d-none');
            } else {
                document.getElementById('wizard-next-btn').classList.remove('d-none');
            }
        }

        function nextStep() {
            if (currentStep < 4) {
                if (currentStep === 2) {
                    // Cargar dinámicamente las devoluciones en el Paso 3 según los pasos activos
                    construirFormularioDevoluciones();
                }
                currentStep++;
                showStep(currentStep);
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
            }
        }

        function construirFormularioDevoluciones() {
            const container = document.getElementById('devoluciones-container');
            container.innerHTML = '';

            // Obtener todos los checkboxes de pasos activos
            const checkboxes = document.querySelectorAll('.step-checkbox');
            const pasosActivos = ['BORRADOR'];

            checkboxes.forEach(chk => {
                if (chk.checked) {
                    pasosActivos.push(chk.value);
                }
            });
            if (!pasosActivos.includes('FINALIZADO')) {
                pasosActivos.push('FINALIZADO');
            }

            // Para cada paso activo (excepto BORRADOR y FINALIZADO), preguntar destino de devolución
            pasosActivos.forEach(code => {
                if (code !== 'BORRADOR' && code !== 'FINALIZADO') {
                    const info = pasosMaestros[code];
                    
                    // Crear select con estados anteriores
                    let optionsHtml = '<option value="EN_CORRECCION" selected>Ingreso de Solicitud (EN_CORRECCION)</option>';
                    
                    pasosActivos.forEach(prevCode => {
                        // Solo permitir devolver a pasos cronológicamente anteriores
                        const indexPrev = Object.keys(pasosMaestros).indexOf(prevCode);
                        const indexCurr = Object.keys(pasosMaestros).indexOf(code);
                        if (indexPrev < indexCurr && prevCode !== 'BORRADOR') {
                            optionsHtml += `<option value="${prevCode}">${pasosMaestros[prevCode].nombre}</option>`;
                        }
                    });

                    const fieldHtml = `
                        <div class="col-12">
                            <div class="card p-3 border-light shadow-sm bg-light-subtle d-flex flex-column flex-md-row md:items-center justify-content-between gap-3">
                                <div>
                                    <span class="fw-bold text-dark d-block">${info.nombre}</span>
                                    <span class="text-muted small">Si se rechaza o devuelve en esta etapa, enviar a:</span>
                                </div>
                                <div>
                                    <select name="devolucion_${code}" class="form-select form-select-sm bg-white text-sm" style="min-width: 200px;">
                                        ${optionsHtml}
                                    </select>
                                </div>
                            </div>
                        </div>
                    `;
                    container.innerHTML += fieldHtml;
                }
            });

            if (container.innerHTML === '') {
                container.innerHTML = '<div class="col-12"><p class="text-muted text-sm italic">No se requiere configurar devoluciones para este flujo lineal básico.</p></div>';
            }
        }

        function abrirModalEditar(transicion) {
            document.getElementById('edit-transicion-id').value = transicion.id;
            document.getElementById('edit-estado-actual').value = transicion.estado_actual;
            document.getElementById('edit-estado-destino').value = transicion.estado_destino;
            document.getElementById('edit-accion-codigo').value = transicion.accion_codigo;
            document.getElementById('edit-accion-label').value = transicion.accion_label;
            document.getElementById('edit-requiere-comentario').checked = parseInt(transicion.requiere_comentario) === 1;
            document.getElementById('edit-monto-min').value = transicion.monto_min_utm !== null ? transicion.monto_min_utm : '';
            document.getElementById('edit-monto-max').value = transicion.monto_max_utm !== null ? transicion.monto_max_utm : '';
            
            const modalEl = document.getElementById('modalEditarTransicion');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    </script>

    <!-- Modal de Edición de Transición -->
    <div class="modal fade" id="modalEditarTransicion" tabindex="-1" aria-labelledby="modalEditarTransicionLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-dark text-white py-3">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="modalEditarTransicionLabel">
                        <i class="bi bi-pencil-square"></i>
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
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Origen</label>
                                <select name="estado_actual" id="edit-estado-actual" required class="form-select text-sm">
                                    <?php foreach($estados_agrupados as $rol => $ests): ?>
                                        <optgroup label="<?= $rol ?>">
                                            <?php foreach($ests as $e): ?>
                                                <option value="<?= $e['codigo'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Destino</label>
                                <select name="estado_destino" id="edit-estado-destino" required class="form-select text-sm">
                                    <?php foreach($estados_agrupados as $rol => $ests): ?>
                                        <optgroup label="<?= $rol ?>">
                                            <?php foreach($ests as $e): ?>
                                                <option value="<?= $e['codigo'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Código Acción</label>
                                <select name="accion_codigo" id="edit-accion-codigo" class="form-select text-sm">
                                    <option value="APROBAR">APROBAR</option>
                                    <option value="DEVOLVER">DEVOLVER</option>
                                    <option value="RECHAZAR">RECHAZAR</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Etiqueta de Acción</label>
                                <input type="text" name="accion_label" id="edit-accion-label" required class="form-control text-sm">
                            </div>
                            <div class="col-md-4 d-flex align-items-center">
                                <div class="form-check pt-3">
                                    <input class="form-check-input" type="checkbox" name="requiere_comentario" id="edit-requiere-comentario" value="1">
                                    <label class="form-check-label text-xs fw-semibold text-secondary" for="edit-requiere-comentario">
                                        Requiere Comentario
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Monto Mínimo (UTM)</label>
                                <input type="number" step="0.01" name="monto_min_utm" id="edit-monto-min" class="form-control text-sm">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Monto Máximo (UTM)</label>
                                <input type="number" step="0.01" name="monto_max_utm" id="edit-monto-max" class="form-control text-sm">
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
</body>
</html>