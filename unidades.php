<?php
require_once 'config.php';

// Seguridad: Solo SYSADMIN o ADMIN_MUNICIPAL
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$rol = $_SESSION['user_rol'] ?? '';
if ($rol !== 'SYSADMIN' && $rol !== 'ADMIN_MUNICIPAL') {
    die("Acceso Denegado. Módulo de administración exclusivo.");
}

// --- LÓGICA DE NEGOCIO (POST) ---
$mensaje = '';
$accion = $_GET['action'] ?? 'listar';
$id_editar = $_GET['id'] ?? null;

// Procesar Formulario (Crear, Editar, Eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id_eliminar = $_POST['id'] ?? null;
        if ($id_eliminar) {
            // Validar si tiene hijos antes de borrar
            $check = $pdo->prepare("SELECT COUNT(*) FROM unidades WHERE padre_id = ?");
            $check->execute([$id_eliminar]);
            if ($check->fetchColumn() > 0) {
                $mensaje = "No se puede eliminar: Esta unidad tiene sub-unidades dependientes.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM unidades WHERE id = ?");
                $stmt->execute([$id_eliminar]);
                header("Location: unidades.php?msg=" . urlencode("Unidad eliminada."));
                exit;
            }
        }
    } else {
        $nombre = $_POST['nombre'];
        $padre_id = !empty($_POST['padre_id']) ? $_POST['padre_id'] : null;
        $centro_costo_id = !empty($_POST['centro_costo_id']) ? $_POST['centro_costo_id'] : null;
        $id = $_POST['id'] ?? null;

        try {
            if ($id) {
                // Actualizar
                $stmt = $pdo->prepare("UPDATE unidades SET nombre=?, padre_id=?, centro_costo_id=? WHERE id=?");
                $stmt->execute([$nombre, $padre_id, $centro_costo_id, $id]);
                $mensaje = "Unidad actualizada correctamente.";
            } else {
                // Crear
                $stmt = $pdo->prepare("INSERT INTO unidades (nombre, padre_id, centro_costo_id) VALUES (?, ?, ?)");
                $stmt->execute([$nombre, $padre_id, $centro_costo_id]);
                $mensaje = "Unidad creada correctamente.";
            }
            // Redireccionar para limpiar POST
            header("Location: unidades.php?msg=" . urlencode($mensaje));
            exit;
        } catch (PDOException $e) {
            $mensaje = "Error: " . $e->getMessage();
        }
    }
}

// --- CONSULTAS DE DATOS (READ) ---

// 1. Obtener todas las unidades (para lista y select padre)
$stmt = $pdo->query("
    SELECT u.*, 
           p.nombre as nombre_padre, 
           cc.nombre as nombre_cc 
    FROM unidades u
    LEFT JOIN unidades p ON u.padre_id = p.id
    LEFT JOIN centros_costo cc ON u.centro_costo_id = cc.id
    ORDER BY u.nombre ASC
");
$unidades = $stmt->fetchAll();

// 2. Obtener Centros de Costo (para select)
$centros = $pdo->query("SELECT id, nombre, codigo_cuenta FROM centros_costo WHERE activo = 1")->fetchAll();

// Datos para editar
$data_editar = null;
if ($accion === 'edit' && $id_editar) {
    $stmt = $pdo->prepare("SELECT * FROM unidades WHERE id = ?");
    $stmt->execute([$id_editar]);
    $data_editar = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unidades Municipales - Sistema de Órdenes de Pedido Interno</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
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
                    <i class="bi bi-diagram-3 text-primary"></i>
                    Unidades Municipales
                </h1>
                <p class="text-muted small mb-0">Gestione la estructura organizacional y la asignación de centros de costo.</p>
            </div>
            <div class="col-12 col-md-auto text-start text-md-end">
                <a href="unidades.php?action=create" class="btn btn-primary btn-sm fw-bold px-3 shadow-sm d-flex align-items-center gap-1.5">
                    <i class="bi bi-plus-lg"></i> Nueva Unidad
                </a>
            </div>
        </div>

        <!-- ALERTAS -->
        <?php if(!empty($_GET['msg']) || !empty($mensaje)): ?>
            <div class="alert alert-primary d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
                <i class="bi bi-info-circle-fill shrink-0"></i>
                <div class="small fw-semibold"><?= htmlspecialchars($_GET['msg'] ?? $mensaje) ?></div>
            </div>
        <?php endif; ?>

        <!-- FORMULARIO DE EDICIÓN / REGISTRO -->
        <?php if ($accion === 'create' || $accion === 'edit'): ?>
            <div class="card shadow-sm border-light mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0 text-dark"><?= $accion === 'edit' ? 'Editar Unidad' : 'Registrar Nueva Unidad' ?></h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="unidades.php">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <?php if ($data_editar): ?>
                            <input type="hidden" name="id" value="<?= $data_editar['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Nombre de la Unidad</label>
                                <input type="text" name="nombre" required value="<?= $data_editar['nombre'] ?? '' ?>" class="form-control bg-white">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Unidad Padre (Jerarquía)</label>
                                <select name="padre_id" class="form-select bg-white text-sm">
                                    <option value="">-- Es Dirección Principal --</option>
                                    <?php foreach($unidades as $u): ?>
                                        <?php if(isset($data_editar) && $data_editar['id'] == $u['id']) continue; ?>
                                        <option value="<?= $u['id'] ?>" <?= (isset($data_editar) && $data_editar['padre_id'] == $u['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($u['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="text-muted small mt-1 mb-0" style="font-size: 10px;">Dejar vacío si corresponde a una dirección superior.</p>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Centro de Costo (Finanzas)</label>
                                <select name="centro_costo_id" class="form-select bg-white text-sm">
                                    <option value="">-- Heredar del Padre --</option>
                                    <?php foreach($centros as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= (isset($data_editar) && $data_editar['centro_costo_id'] == $c['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['codigo_cuenta'] . ' - ' . $c['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="text-muted small mt-1 mb-0" style="font-size: 10px;">Si se hereda, usará las cuentas y fondos de la dirección a la que pertenece.</p>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-content-end gap-2">
                            <a href="unidades.php" class="btn btn-secondary btn-sm px-4">Cancelar</a>
                            <button type="submit" class="btn btn-primary btn-sm fw-bold px-4">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- LISTADO GENERAL DE UNIDADES -->
        <div class="card shadow-sm border-light">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-dark">Estructura de Unidades Municipales</h6>
            </div>
            <div class="table-responsive rounded-bottom">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small" style="font-size: 10px;">
                        <tr>
                            <th class="p-3">Nombre Unidad</th>
                            <th class="p-3">Dependencia Jerárquica</th>
                            <th class="p-3">Presupuesto Asignado</th>
                            <th class="p-3 text-center" style="width: 150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <?php foreach($unidades as $row): ?>
                        <tr>
                            <td class="p-3 fw-bold text-dark small">
                                <?= htmlspecialchars($row['nombre']) ?>
                            </td>
                            <td class="p-3">
                                <?php if($row['nombre_padre']): ?>
                                    <span class="text-secondary small d-flex align-items-center gap-1">
                                        <i class="bi bi-arrow-return-right text-muted"></i>
                                        <?= htmlspecialchars($row['nombre_padre']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-purple-subtle text-purple border border-purple-subtle px-2 py-1 text-uppercase" style="font-size: 8px;">Dirección Principal</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3">
                                <?php if($row['nombre_cc']): ?>
                                    <div class="d-flex align-items-center gap-1.5">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 text-uppercase" style="font-size: 8px;">Propio</span>
                                        <span class="text-secondary font-monospace small" style="font-size: 11px;"><?= htmlspecialchars($row['nombre_cc']) ?></span>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary border px-2 py-1 text-uppercase" style="font-size: 8px;">Heredado</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 text-center">
                                <div class="d-flex align-items-center justify-content-center gap-1.5">
                                    <a href="unidades.php?action=edit&id=<?= $row['id'] ?>" class="btn btn-outline-primary btn-sm px-2.5 py-1 fw-bold text-xs" title="Editar">
                                        Editar
                                    </a>
                                    <form method="POST" action="unidades.php" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar esta unidad?')">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm px-2.5 py-1 fw-bold text-xs" title="Eliminar">
                                            Eliminar
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

    </div>
</body>
</html>