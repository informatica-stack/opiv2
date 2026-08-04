<?php
// firmantes.php - Gestión de Autoridad Administrativa (Contingencia)
require_once __DIR__ . '/config.php';

// 1. SEGURIDAD
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

// SOLO ROL PRESUPUESTO O ADMIN O SYSADMIN PUEDEN ENTRAR ACÁ
$rol = $_SESSION['user_rol'] ?? '';
if ($rol !== 'PRESUPUESTO' && $rol !== 'ADMIN_MUNICIPAL' && $rol !== 'SYSADMIN') {
    die("Acceso Denegado. Gestión exclusiva de Finanzas/Administración.");
}

$mensaje = '';
$tipo_mensaje = '';

// 2. OBTENER AL TITULAR (ADMINISTRADOR MUNICIPAL)
// Buscamos al usuario que tiene el rol base de ADMIN_MUNICIPAL
$stmtAdmin = $pdo->prepare("
    SELECT u.id, u.nombre_completo, u.rut 
    FROM usuarios u 
    JOIN roles r ON u.rol_id = r.id 
    WHERE r.nombre = 'ADMIN_MUNICIPAL' AND u.activo = 1 
    LIMIT 1
");
$stmtAdmin->execute();
$titular = $stmtAdmin->fetch();

if (!$titular) {
    die("Error Crítico: No existe un usuario con el rol 'ADMIN_MUNICIPAL' creado en el sistema.");
}

// --- LÓGICA POST (ACCIONES) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // A. DESIGNAR FIRMANTE (FORZAR SUBROGANCIA)
        if ($_POST['accion'] === 'designar') {
            $subrogante_id = $_POST['usuario_firmante_id'];
            $inicio = $_POST['fecha_inicio'];
            $fin = $_POST['fecha_fin'];
            $motivo = "Designación por Finanzas (Contingencia): " . $_POST['motivo'];

            if ($subrogante_id == $titular['id']) throw new Exception("No puede designar al mismo titular como subrogante.");
            if ($inicio > $fin) throw new Exception("La fecha de inicio no puede ser posterior al fin.");

            // Desactivar subrogancias solapadas para evitar conflictos
            $pdo->prepare("UPDATE subrogancias SET activo = 0 WHERE usuario_titular_id = ? AND activo = 1 AND ((fecha_inicio BETWEEN ? AND ?) OR (fecha_fin BETWEEN ? AND ?))")
                ->execute([$titular['id'], $inicio, $fin, $inicio, $fin]);

            // Crear la nueva
            $stmt = $pdo->prepare("INSERT INTO subrogancias (usuario_titular_id, usuario_subrogante_id, fecha_inicio, fecha_fin, motivo, activo) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$titular['id'], $subrogante_id, $inicio, $fin, $motivo]);

            $mensaje = "Firmante suplente asignado correctamente.";
            $tipo_mensaje = "success";
        }

        // B. REVOCAR FIRMANTE
        if ($_POST['accion'] === 'revocar') {
            $id_sub = $_POST['id_subrogancia'];
            $pdo->prepare("UPDATE subrogancias SET activo = 0 WHERE id = ?")->execute([$id_sub]);
            $mensaje = "Designación revocada. El titular retoma la firma.";
            $tipo_mensaje = "success";
        }

        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// --- CONSULTAS ---

// 1. ¿Quién está firmando HOY?
$hoy = date('Y-m-d');
$stmtVigente = $pdo->prepare("
    SELECT s.id, u.nombre_completo, s.fecha_inicio, s.fecha_fin, s.motivo
    FROM subrogancias s
    JOIN usuarios u ON s.usuario_subrogante_id = u.id
    WHERE s.usuario_titular_id = ? 
    AND s.activo = 1 
    AND ? BETWEEN s.fecha_inicio AND s.fecha_fin
    LIMIT 1
");
$stmtVigente->execute([$titular['id'], $hoy]);
$firmante_actual = $stmtVigente->fetch();

// 2. Historial / Futuros
$stmtHist = $pdo->prepare("
    SELECT s.*, u.nombre_completo 
    FROM subrogancias s
    JOIN usuarios u ON s.usuario_subrogante_id = u.id
    WHERE s.usuario_titular_id = ? AND s.activo = 1
    ORDER BY s.fecha_inicio DESC
");
$stmtHist->execute([$titular['id']]);
$historial = $stmtHist->fetchAll();

// 3. Lista de Candidatos (Cualquier usuario excepto el titular)
$candidatos = $pdo->prepare("SELECT id, nombre_completo, rut FROM usuarios WHERE id != ? AND activo = 1 ORDER BY nombre_completo");
$candidatos->execute([$titular['id']]);
$lista_usuarios = $candidatos->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <?php 
    $titulo_pagina = "Gestión de Firmantes";
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
            <div class="col-12">
                <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                    <i class="bi bi-person-fill-lock text-primary"></i>
                    Gestión de Firmas (Administración)
                </h1>
                <p class="text-muted small mb-0">Defina quién aprueba las OPIs en nombre del Municipio en caso de subrogancia o ausencias del titular.</p>
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

        <!-- PANELES DE CONTROL -->
        <div class="row g-4">
            
            <!-- PANEL 1: ESTADO FIRMANTE HOY -->
            <div class="col-md-6">
                <div class="card shadow-sm border-light h-100 relative overflow-hidden">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold mb-0 text-dark">Firmante Vigente Hoy</h6>
                    </div>
                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                        
                        <?php if($firmante_actual): ?>
                            <div class="alert alert-warning border border-warning-subtle d-flex flex-column gap-1 p-3 mb-4 rounded-3 text-warning-emphasis">
                                <span class="badge bg-warning text-dark text-uppercase w-fit mb-1" style="font-size: 8px;">Subrogante Activo</span>
                                <h5 class="fw-bold mb-1"><?= htmlspecialchars($firmante_actual['nombre_completo']) ?></h5>
                                <p class="small mb-2 text-warning-emphasis">Desde <strong><?= date('d/m/Y', strtotime($firmante_actual['fecha_inicio'])) ?></strong> hasta <strong><?= date('d/m/Y', strtotime($firmante_actual['fecha_fin'])) ?></strong></p>
                                <p class="small mb-0 text-muted italic">"<?= htmlspecialchars($firmante_actual['motivo']) ?>"</p>
                            </div>
                            <form method="POST" onsubmit="return confirm('¿Está seguro de revocar la firma a este usuario inmediatamente?')">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                <input type="hidden" name="accion" value="revocar">
                                <input type="hidden" name="id_subrogancia" value="<?= $firmante_actual['id'] ?>">
                                <button class="btn btn-outline-danger w-100 fw-bold py-2 shadow-sm d-flex align-items-center justify-content-center gap-1.5">
                                    <i class="bi bi-x-circle"></i> Revocar Suplencia (Retornar al Titular)
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="bg-light border p-3.5 mb-4 rounded-3">
                                <span class="badge bg-primary text-uppercase w-fit mb-1.5" style="font-size: 8px;">Titular en Ejercicio</span>
                                <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($titular['nombre_completo']) ?></h5>
                                <p class="small text-muted font-monospace mb-0">RUT: <?= htmlspecialchars($titular['rut']) ?></p>
                            </div>
                            <div class="text-success small fw-semibold d-flex align-items-center gap-1.5">
                                <i class="bi bi-shield-check-fill fs-5"></i>
                                Operación y Firma Normal (Titular Activo)
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

            <!-- PANEL 2: DESIGNAR SUPLENTE -->
            <div class="col-md-6">
                <div class="card shadow-sm border-light h-100">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold mb-0 text-dark">Designar Suplente (Contingencia)</h6>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                            <input type="hidden" name="accion" value="designar">
                            
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Funcionario a Designar</label>
                                    <select name="usuario_firmante_id" required class="form-select text-sm">
                                        <option value="">-- Seleccione Funcionario --</option>
                                        <?php foreach($lista_usuarios as $usr): ?>
                                            <option value="<?= $usr['id'] ?>"><?= htmlspecialchars($usr['nombre_completo']) ?> (<?= htmlspecialchars($usr['rut']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Fecha Desde</label>
                                    <input type="date" name="fecha_inicio" value="<?= date('Y-m-d') ?>" required class="form-control text-sm">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Fecha Hasta</label>
                                    <input type="date" name="fecha_fin" required class="form-control text-sm">
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Motivo / Decreto / Resolución</label>
                                    <input type="text" name="motivo" required class="form-control text-sm">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-dark w-100 mt-4 py-2.5 fw-bold shadow transition d-flex align-items-center justify-content-center gap-1.5">
                                <i class="bi bi-save"></i> Guardar Designación suplente
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>

        <!-- TABLA: HISTORIAL DE DESIGNACIONES -->
        <div class="card shadow-sm border-light mt-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-dark">Historial de Designaciones y Subrogancias</h6>
            </div>
            <div class="table-responsive rounded-bottom">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small" style="font-size: 10px;">
                        <tr>
                            <th class="p-3">Funcionario Designado</th>
                            <th class="p-3" style="width: 220px;">Periodo</th>
                            <th class="p-3">Motivo</th>
                            <th class="p-3 text-center" style="width: 140px;">Estado</th>
                            <th class="p-3 text-center" style="width: 120px;">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <?php if(empty($historial)): ?>
                            <tr>
                                <td colspan="5" class="p-4 text-center text-muted italic">No existen registros históricos de subrogancias.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach($historial as $h): 
                            $activo = $h['activo'] && ($hoy >= $h['fecha_inicio'] && $hoy <= $h['fecha_fin']);
                            $futuro = $h['activo'] && ($h['fecha_inicio'] > $hoy);
                            $pasado = $h['fecha_fin'] < $hoy;
                        ?>
                        <tr>
                            <td class="p-3 fw-bold text-dark small"><?= htmlspecialchars($h['nombre_completo']) ?></td>
                            <td class="p-3 small text-secondary fw-semibold">
                                <span class="font-monospace"><?= date('d/m/Y', strtotime($h['fecha_inicio'])) ?></span> al <span class="font-monospace"><?= date('d/m/Y', strtotime($h['fecha_fin'])) ?></span>
                            </td>
                            <td class="p-3 text-muted small italic" style="font-size: 11px;"><?= htmlspecialchars($h['motivo']) ?></td>
                            <td class="p-3 text-center">
                                <?php if(!$h['activo']): ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 text-uppercase" style="font-size: 8px;">Revocado</span>
                                <?php elseif($activo): ?>
                                    <span class="badge bg-green-subtle text-success border border-success-subtle px-2 py-1 text-uppercase flex w-fit mx-auto align-items-center gap-1" style="font-size: 8px;">
                                        <span class="spinner-grow spinner-grow-sm" style="width: 6px; height: 6px;" role="status"></span> Vigente
                                    </span>
                                <?php elseif($futuro): ?>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1 text-uppercase" style="font-size: 8px;">Programado</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary border px-2 py-1 text-uppercase" style="font-size: 8px;">Finalizado</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 text-center">
                                <?php if($h['activo'] && !$pasado): ?>
                                    <form method="POST" onsubmit="return confirm('¿Cancelar esta designación?')">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="accion" value="revocar">
                                        <input type="hidden" name="id_subrogancia" value="<?= $h['id'] ?>">
                                        <button class="btn btn-link text-danger p-0 fw-bold text-decoration-none small" style="font-size: 11px;">Revocar</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
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