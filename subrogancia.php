<?php
require_once __DIR__ . '/config.php';

// Seguridad: Usuario logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$unidad_id = $_SESSION['user_unidad'];
$mensaje = '';
$tipo_mensaje = '';

// --- ACCIONES (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. CREAR NUEVA SUBROGANCIA
    if (isset($_POST['crear_subrogancia'])) {
        $subrogante_id = $_POST['subrogante_id'];
        $fecha_inicio = $_POST['fecha_inicio'];
        $fecha_fin = $_POST['fecha_fin'];
        $motivo = $_POST['motivo'];

        try {
            // Validaciones básicas
            if ($fecha_fin < $fecha_inicio) throw new Exception("La fecha de fin no puede ser anterior al inicio.");
            if ($subrogante_id == $user_id) throw new Exception("No puedes subrogarte a ti mismo.");

            // Validar solapamiento (que no haya otra activa en esas fechas)
            $stmtCheck = $pdo->prepare("
                SELECT COUNT(*) FROM subrogancias 
                WHERE usuario_titular_id = ? AND activo = 1
                AND (
                    (fecha_inicio BETWEEN ? AND ?) OR 
                    (fecha_fin BETWEEN ? AND ?)
                )
            ");
            $stmtCheck->execute([$user_id, $fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin]);
            
            if ($stmtCheck->fetchColumn() > 0) {
                throw new Exception("Ya tienes una subrogancia programada en ese rango de fechas.");
            }

            // Insertar
            $sql = "INSERT INTO subrogancias (usuario_titular_id, usuario_subrogante_id, fecha_inicio, fecha_fin, motivo) VALUES (?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$user_id, $subrogante_id, $fecha_inicio, $fecha_fin, $motivo]);

            $mensaje = "Subrogancia configurada exitosamente.";
            $tipo_mensaje = 'success';

        } catch (Exception $e) {
            $mensaje = $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }

    // 2. CANCELAR SUBROGANCIA
    if (isset($_POST['cancelar_id'])) {
        $stmt = $pdo->prepare("UPDATE subrogancias SET activo = 0 WHERE id = ? AND usuario_titular_id = ?");
        $stmt->execute([$_POST['cancelar_id'], $user_id]);
        $mensaje = "Subrogancia cancelada.";
        $tipo_mensaje = 'success';
    }
}

// --- CONSULTAS (READ) ---

// 1. Mis Subrogancias (Historial)
$stmt = $pdo->prepare("
    SELECT s.*, u.nombre_completo as nombre_subrogante 
    FROM subrogancias s
    JOIN usuarios u ON s.usuario_subrogante_id = u.id
    WHERE s.usuario_titular_id = ? AND s.activo = 1
    ORDER BY s.fecha_inicio DESC
");
$stmt->execute([$user_id]);
$mis_subrogancias = $stmt->fetchAll();

// 2. Candidatos a Subrogarme (Compañeros de mi misma unidad)
$stmtU = $pdo->prepare("SELECT id, nombre_completo FROM usuarios WHERE unidad_id = ? AND id != ? AND activo = 1");
$stmtU->execute([$unidad_id, $user_id]);
$candidatos = $stmtU->fetchAll();

// Helper de Estado
function estado_fecha($inicio, $fin) {
    $hoy = date('Y-m-d');
    if ($hoy >= $inicio && $hoy <= $fin) return ['texto' => 'EN CURSO', 'color' => 'bg-green-100 text-green-700 animate-pulse'];
    if ($hoy < $inicio) return ['texto' => 'PROGRAMADA', 'color' => 'bg-blue-100 text-blue-700'];
    return ['texto' => 'FINALIZADA', 'color' => 'bg-gray-100 text-gray-500'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Suplente - Sistema de Órdenes de Pedido Interno</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-slate-50 text-slate-800 font-sans pb-20">

    <?php include __DIR__ . '/nav.php'; ?>

    <div class="container mt-4 px-3 px-md-4">
        
        <!-- CABECERA DE LA PÁGINA -->
        <div class="row align-items-center mb-4 g-3">
            <div class="col-12 col-md">
                <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                    <i class="bi bi-people-fill text-primary"></i>
                    Mis Subrogancias / Suplentes
                </h1>
                <p class="text-muted small mb-0">Delegue sus permisos y responsabilidades de firma/visación durante periodos de ausencia.</p>
            </div>
        </div>

        <?php if($mensaje): ?>
            <div class="alert alert-<?= $tipo_mensaje === 'error' ? 'danger' : 'success' ?> d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="bi bi-<?= $tipo_mensaje === 'error' ? 'exclamation-triangle-fill' : 'check-circle-fill' ?>"></i>
                <div class="small fw-semibold"><?= htmlspecialchars($mensaje) ?></div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            
            <!-- PANEL: NUEVA DELEGACIÓN -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-light">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-person-plus-fill text-primary"></i>
                            Nueva Delegación
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Funcionario Subrogante</label>
                                    <select name="subrogante_id" required class="form-select text-sm">
                                        <option value="">-- Seleccione Compañero --</option>
                                        <?php foreach($candidatos as $c): ?>
                                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre_completo']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text text-muted" style="font-size: 10px;">Solo compañeros activos de su misma unidad.</div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Desde</label>
                                    <input type="date" name="fecha_inicio" required min="<?= date('Y-m-d') ?>" class="form-control text-sm">
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Hasta (Inclusive)</label>
                                    <input type="date" name="fecha_fin" required min="<?= date('Y-m-d') ?>" class="form-control text-sm">
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Motivo (Interno)</label>
                                    <input type="text" name="motivo" class="form-control text-sm">
                                </div>

                                <div class="col-12">
                                    <button type="submit" name="crear_subrogancia" class="btn btn-primary w-100 py-2.5 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2">
                                        Activar Subrogancia
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- PANEL: LISTADO PROGRAMADAS -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-light">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-clock-history text-secondary"></i>
                            Subrogancias Programadas
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <?php if (count($mis_subrogancias) > 0): ?>
                            <div class="d-flex flex-column gap-3">
                                <?php foreach($mis_subrogancias as $row): 
                                    $est = estado_fecha($row['fecha_inicio'], $row['fecha_fin']);
                                    $badgeColor = ($est['texto'] === 'EN CURSO') ? 'bg-success' : (($est['texto'] === 'PROGRAMADA') ? 'bg-primary' : 'bg-secondary');
                                ?>
                                <div class="p-3 border rounded-3 bg-light d-flex justify-content-between align-items-center shadow-sm">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1.5">
                                            <span class="badge <?= $badgeColor ?> px-2 py-1 uppercase font-bold tracking-wide" style="font-size: 8px;">
                                                <?= $est['texto'] ?>
                                            </span>
                                            <span class="text-muted small" style="font-size: 10px;">ID: #<?= $row['id'] ?></span>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-1">
                                            Subroga: <?= htmlspecialchars($row['nombre_subrogante']) ?>
                                        </h6>
                                        <p class="text-secondary small mb-1">
                                            <i class="bi bi-calendar-event me-1"></i>
                                            Del <?= date('d/m/Y', strtotime($row['fecha_inicio'])) ?> 
                                            al <?= date('d/m/Y', strtotime($row['fecha_fin'])) ?>
                                        </p>
                                        <p class="text-muted small italic mb-0">
                                            <i class="bi bi-info-circle me-1"></i>
                                            "<?= htmlspecialchars($row['motivo']) ?>"
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <form method="POST" onsubmit="return confirm('¿Cancelar esta subrogancia?')">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                            <input type="hidden" name="cancelar_id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm border-0 rounded-circle" title="Cancelar Subrogancia">
                                                <i class="bi bi-trash fs-5"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5 border border-dashed rounded-3 bg-light">
                                <i class="bi bi-people text-secondary display-6 mb-2 d-block"></i>
                                <p class="text-muted small mb-0">No tiene delegaciones de subrogancia activas.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>