<?php
// centros_de_costo.php - Gestión de Presupuestos (Corregido)
require_once __DIR__ . '/config.php';

// 1. SEGURIDAD Y SESIÓN
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

// VALIDACIÓN ROL (Presupuesto y Admin tienen acceso implícito)
$rol_actual = $_SESSION['user_rol'] ?? '';
if ($rol_actual !== 'PRESUPUESTO' && $rol_actual !== 'ADMIN_MUNICIPAL' && $rol_actual !== 'SYSADMIN') {
    die("Acceso Denegado. Módulo exclusivo de Finanzas.");
}

$mensaje = '';
$tipo_mensaje = '';
$vista = $_GET['view'] ?? 'lista';

// --- LÓGICA DE PROCESAMIENTO (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // A. CREAR CENTRO DE COSTO (PADRE)
        if (isset($_POST['accion']) && $_POST['accion'] === 'crear_cc') {
            $nombre = trim($_POST['nombre']);
            $codigo = trim($_POST['codigo']);
            $anio = $_POST['anio'];
            
            $stmt = $pdo->prepare("INSERT INTO centros_costo (nombre, codigo_cuenta, anio_fiscal, activo) VALUES (?, ?, ?, 1)");
            $stmt->execute([$nombre, $codigo, $anio]);
            $mensaje = "Centro de Costo creado correctamente.";
            $tipo_mensaje = "success";
        }

        // B. ASIGNAR CUENTA
        if (isset($_POST['accion']) && $_POST['accion'] === 'asignar_cuenta') {
            $cc_id = $_POST['centro_costo_id'];
            $maestra_id = $_POST['cuenta_maestra_id'];
            $ag_id = !empty($_POST['area_gestion_id']) ? $_POST['area_gestion_id'] : null;

            // Validar duplicados
            $check = $pdo->prepare("SELECT id FROM presupuestos_asignados WHERE centro_costo_id = ? AND cuenta_maestra_id = ? AND area_gestion_id <=> ?");
            $check->execute([$cc_id, $maestra_id, $ag_id]);
            if ($check->fetch()) throw new Exception("Esta cuenta ya está asignada a este Centro con esa Área de Gestión.");

            $stmt = $pdo->prepare("INSERT INTO presupuestos_asignados (centro_costo_id, cuenta_maestra_id, area_gestion_id) VALUES (?, ?, ?)");
            $stmt->execute([$cc_id, $maestra_id, $ag_id]);
            
            $mensaje = "Cuenta vinculada correctamente.";
            $tipo_mensaje = "success";
            $vista = 'detalle';
            $_GET['id'] = $cc_id;
        }

        // D. ELIMINAR ASIGNACIÓN
        if (isset($_POST['accion']) && $_POST['accion'] === 'eliminar_asignacion') {
            $asignacion_id = $_POST['asignacion_id'];
            
            $check = $pdo->prepare("SELECT COUNT(*) FROM expedientes_items WHERE presupuesto_asignado_id = ?");
            $check->execute([$asignacion_id]);
            if ($check->fetchColumn() > 0) throw new Exception("No se puede eliminar: Ya existen compras vinculadas a esta asignación.");

            $pdo->prepare("DELETE FROM presupuestos_asignados WHERE id = ?")->execute([$asignacion_id]);
            $mensaje = "Asignación eliminada.";
            $tipo_mensaje = "success";
            $vista = 'detalle';
            $_GET['id'] = $_POST['centro_costo_id'];
        }

        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "error";
        if(isset($_POST['centro_costo_id'])) { $vista = 'detalle'; $_GET['id'] = $_POST['centro_costo_id']; }
    }
}

// --- CONSULTAS ---
$areas_gestion = $pdo->query("SELECT * FROM areas_gestion WHERE activo = 1 ORDER BY codigo ASC")->fetchAll();
$catalogo_cuentas = $pdo->query("SELECT * FROM cuentas_maestras WHERE activo = 1 ORDER BY codigo ASC")->fetchAll();

$centro_actual = null;
$asignaciones = [];

if ($vista === 'detalle' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM centros_costo WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $centro_actual = $stmt->fetch();

    if ($centro_actual) {
        $sqlAsig = "
            SELECT pa.id, pa.centro_costo_id, pa.cuenta_maestra_id, pa.area_gestion_id,
                   cm.codigo, cm.nombre, ag.codigo as ag_cod,
                   (SELECT COUNT(*) FROM expedientes_items ei WHERE ei.presupuesto_asignado_id = pa.id) as items_count
            FROM presupuestos_asignados pa 
            JOIN cuentas_maestras cm ON pa.cuenta_maestra_id = cm.id
            LEFT JOIN areas_gestion ag ON pa.area_gestion_id = ag.id
            WHERE pa.centro_costo_id = ? 
            ORDER BY cm.codigo ASC
        ";
        $stmtA = $pdo->prepare($sqlAsig);
        $stmtA->execute([$_GET['id']]);
        $asignaciones = $stmtA->fetchAll();

    }
} else {
    $sqlLista = "
        SELECT cc.*
        FROM centros_costo cc
        WHERE cc.activo = 1
        ORDER BY cc.anio_fiscal DESC, cc.nombre ASC
    ";
    $centros_list = $pdo->query($sqlLista)->fetchAll();
}

function money($v) { return '$ ' . number_format($v, 0, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php 
    $titulo_pagina = "Gestión Presupuestaria";
    include __DIR__ . '/head.php'; 
    ?>
</head>
<body class="bg-light text-slate-800 pb-20 font-sans">

    <?php include __DIR__ . '/nav.php'; ?>

    <!-- Inclusión de Bootstrap 5 JS Bundle después de nav.php -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <div class="container mt-4 px-3 px-md-4">

        <!-- CABECERA -->
        <div class="row align-items-center mb-4 g-3">
            <div class="col-12 col-md">
                <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                    <i class="bi bi-cash-coin text-primary"></i>
                    Gestión de Presupuesto
                </h1>
                <p class="text-muted small mb-0">Administración de fondos y centros de costo municipales.</p>
            </div>
            <div class="col-12 col-md-auto">
                <?php if($vista === 'detalle'): ?>
                    <a href="centros_de_costo.php" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                        <i class="bi bi-arrow-left me-1"></i> Volver al Listado
                    </a>
                <?php else: ?>
                    <div class="d-flex gap-2">
                        <a href="mantenedor_cuentas.php" class="btn btn-dark btn-sm fw-bold px-3 shadow-sm d-flex align-items-center gap-1.5">
                            <i class="bi bi-list-columns-reverse"></i> Catálogo Global
                        </a>
                        <button class="btn btn-primary btn-sm fw-bold px-3 shadow-sm d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#modalCC">
                            <i class="bi bi-plus-lg"></i> Nuevo Centro de Costo
                        </button>
                    </div>
                <?php endif; ?>
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

        <!-- VISTA: LISTA DE CENTROS DE COSTO -->
        <?php if($vista === 'lista'): ?>
            <div class="card shadow-sm border-light">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0 text-dark">Centros de Costo Activos</h6>
                </div>
                <div class="table-responsive rounded-bottom">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small" style="font-size: 10px;">
                            <tr>
                                <th class="p-3" style="width: 80px;">Año</th>
                                <th class="p-3">Centro de Costo</th>
                                <th class="p-3" style="width: 160px;">Código</th>
                                <th class="p-3 text-center" style="width: 120px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <?php foreach($centros_list as $cc): ?>
                            <tr>
                                <td class="p-3 fw-bold text-secondary"><?= $cc['anio_fiscal'] ?></td>
                                <td class="p-3 fw-bold text-dark fs-6"><?= htmlspecialchars($cc['nombre']) ?></td>
                                <td class="p-3">
                                    <span class="badge bg-light text-secondary font-monospace border px-2 py-1"><?= htmlspecialchars($cc['codigo_cuenta']) ?></span>
                                </td>
                                <td class="p-3 text-center">
                                    <a href="centros_de_costo.php?view=detalle&id=<?= $cc['id'] ?>" class="btn btn-outline-primary btn-sm fw-bold px-3">
                                        Gestionar
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MODAL: CREAR CENTRO DE COSTO -->
            <div class="modal fade" id="modalCC" tabindex="-1" aria-labelledby="modalCCLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content shadow-2xl border-light">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title fw-bold text-dark" id="modalCCLabel">Crear Centro de Costo</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                            <div class="modal-body p-4">
                                <input type="hidden" name="accion" value="crear_cc">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Nombre Descriptivo</label>
                                        <input type="text" name="nombre" required class="form-control">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Código Interno</label>
                                        <input type="text" name="codigo" required class="form-control">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Año Fiscal</label>
                                        <input type="number" name="anio" value="<?= date('Y') ?>" required class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary btn-sm fw-bold px-4">Crear Centro</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- VISTA: DETALLE CENTRO DE COSTO Y ASIGNACIONES -->
        <?php if($vista === 'detalle' && $centro_actual): ?>
            
            <div class="card shadow-sm border-light mb-4">
                <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                    <div>
                        <span class="badge bg-primary-subtle text-primary text-uppercase tracking-wider mb-1" style="font-size: 8px;">Centro de Costo</span>
                        <h2 class="h4 fw-bold text-dark mb-1"><?= htmlspecialchars($centro_actual['nombre']) ?></h2>
                        <p class="text-muted small mb-0 font-monospace">Código: <?= htmlspecialchars($centro_actual['codigo_cuenta']) ?> &bull; Año Fiscal <?= $centro_actual['anio_fiscal'] ?></p>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-light">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0 text-dark">Cuentas Presupuestarias Vinculadas</h6>
                    <button class="btn btn-success btn-sm fw-bold px-3 d-flex align-items-center gap-1 shadow-sm" onclick="abrirModalAsignar()">
                        <i class="bi bi-plus-lg"></i> Vincular Cuenta
                    </button>
                </div>
                <div class="table-responsive rounded-bottom">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small" style="font-size: 10px;">
                            <tr>
                                <th class="p-3">Cuenta</th>
                                <th class="p-3" style="width: 120px;">Área Gestión</th>
                                <th class="p-3 text-center" style="width: 120px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <?php foreach($asignaciones as $a): 
                                $usado = $a['items_count'];
                            ?>
                            <tr>
                                <td class="p-3">
                                    <span class="badge bg-light text-secondary font-monospace border mb-1"><?= htmlspecialchars($a['codigo']) ?></span>
                                    <div class="fw-semibold text-dark small leading-tight"><?= htmlspecialchars($a['nombre']) ?></div>
                                </td>
                                <td class="p-3">
                                    <?php if($a['ag_cod']): ?>
                                        <span class="badge bg-purple-subtle text-purple border border-purple-subtle px-2 py-1">
                                            <?= htmlspecialchars($a['ag_cod']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-1.5">
                                        <?php if($usado == 0): ?>
                                            <form method="POST" onsubmit="return confirm('¿Eliminar esta asignación presupuestaria?')" class="d-inline mb-0">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                                <input type="hidden" name="accion" value="eliminar_asignacion">
                                                <input type="hidden" name="centro_costo_id" value="<?= $centro_actual['id'] ?>">
                                                <input type="hidden" name="asignacion_id" value="<?= $a['id'] ?>">
                                                <button class="btn btn-outline-danger btn-sm px-2" title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MODAL: ASIGNAR / EDITAR PRESUPUESTO -->
            <div class="modal fade" id="modalAsig" tabindex="-1" aria-labelledby="modalAsigLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content shadow-2xl border-light">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title fw-bold text-dark" id="modalTit">Vincular Cuenta</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" id="formAsig">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                            <div class="modal-body p-4">
                                <input type="hidden" name="accion" id="formAct" value="asignar_cuenta">
                                <input type="hidden" name="centro_costo_id" value="<?= $centro_actual['id'] ?>">
                                <input type="hidden" name="asignacion_id" id="asigId">

                                <div class="row g-3">
                                    <div class="col-12" id="divCuenta">
                                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Seleccionar Cuenta Maestra</label>
                                        <select name="cuenta_maestra_id" id="selCuenta" class="form-select text-sm">
                                            <option value="">-- Buscar Cuenta del Catálogo --</option>
                                            <?php foreach($catalogo_cuentas as $cat): ?>
                                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['codigo']) ?> - <?= htmlspecialchars($cat['nombre']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-12 d-none" id="divInfoCuenta">
                                        <div class="bg-light p-3 rounded border">
                                            <span class="text-muted fw-bold d-block text-uppercase" style="font-size: 9px;">Editando Asignación:</span>
                                            <p class="font-bold text-dark text-sm mt-1 mb-0" id="txtCuentaNombre"></p>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Área de Gestión</label>
                                        <select name="area_gestion_id" id="selAg" class="form-select text-sm">
                                            <option value="">-- General --</option>
                                            <?php foreach($areas_gestion as $ag): ?>
                                                <option value="<?= $ag['id'] ?>"><?= htmlspecialchars($ag['codigo']) ?> - <?= htmlspecialchars($ag['nombre']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary btn-sm fw-bold px-4">Vincular Cuenta</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- COMPACT JS LOGIC FOR MODALS -->
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    window.abrirModalAsignar = function() {
                        const modalEl = document.getElementById('modalAsig');
                        const bootstrapModal = new bootstrap.Modal(modalEl);
                        
                        document.getElementById('modalTit').innerText = "Vincular Cuenta al Centro de Costo";
                        document.getElementById('formAct').value = "asignar_cuenta";
                        document.getElementById('asigId').value = "";
                        
                        document.getElementById('divCuenta').classList.remove('d-none');
                        document.getElementById('divInfoCuenta').classList.add('d-none');
                        document.getElementById('selCuenta').required = true;
                        document.getElementById('selAg').disabled = false;
                        
                        document.getElementById('selCuenta').value = "";
                        document.getElementById('selAg').value = "";
                        
                        bootstrapModal.show();
                    };
                });
            </script>
        <?php endif; ?>

    </div>
</body>
</html>