<?php
// limpiar_datos_pruebas.php - Herramienta de Limpieza y Reseteo del Sistema (Pruebas desde cero)
require_once __DIR__ . '/config.php';

$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
        die("Acceso Denegado. Debe inicar sesión.");
    }
    $rol = $_SESSION['user_rol'] ?? '';
    if ($rol !== 'SYSADMIN' && $rol !== 'ADMIN_MUNICIPAL') {
        die("Acceso Denegado. Se requieren permisos de Administración para ejecutar esta acción.");
    }
}

$ejecutar = false;
$mensaje = '';
$detalles = [];
$archivos_borrados = 0;

if ($is_cli) {
    // Si viene desde CLI con flag --confirm o confirm
    $args = $_SERVER['argv'] ?? [];
    if (in_array('--confirm', $args) || in_array('confirm', $args)) {
        $ejecutar = true;
    }
} else {
    // Si viene por POST en Web
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_limpieza'])) {
        $ejecutar = true;
    }
}

if ($ejecutar) {
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        $tablas = [
            'expedientes_firmas',
            'expedientes_documentos',
            'expedientes_historial',
            'expedientes_criterios',
            'expedientes_items',
            'expedientes'
        ];

        foreach ($tablas as $t) {
            // Contar antes de borrar
            $cnt = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
            $pdo->exec("TRUNCATE TABLE `$t`");
            $pdo->exec("ALTER TABLE `$t` AUTO_INCREMENT = 1");
            $detalles[$t] = $cnt;
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

        // Borrar archivos en carpeta uploads
        $uploads_dir = __DIR__ . '/uploads';
        if (file_exists($uploads_dir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($uploads_dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                $todo = $fileinfo->getRealPath();
                if ($fileinfo->isDir()) {
                    @rmdir($todo);
                } else {
                    @unlink($todo);
                    $archivos_borrados++;
                }
            }
        }

        $mensaje = "Limpieza ejecutada con éxito. El sistema ha sido reiniciado a cero para nuevas pruebas.";

    } catch (Exception $e) {
        $mensaje = "Error al ejecutar limpieza: " . $e->getMessage();
    }
}

if ($is_cli) {
    if (!$ejecutar) {
        echo "====================================================\n";
        echo "HERRAMIENTA DE LIMPIEZA DE DATOS DE PRUEBA (MUNI LEBU)\n";
        echo "====================================================\n";
        echo "ADVERTENCIA: Esta acción vaciará todos los expedientes, firmas,\n";
        echo "documentos e historial de prueba y reseteará los IDs a 1.\n\n";
        echo "Para ejecutar, incluya el flag --confirm:\n";
        echo "php limpiar_datos_pruebas.php --confirm\n";
        echo "====================================================\n";
        exit(0);
    } else {
        echo "====================================================\n";
        echo "RESUMEN DE LIMPIEZA EJECUTADA CON ÉXITO\n";
        echo "====================================================\n";
        foreach ($detalles as $t => $c) {
            echo "Tabla '$t': $c registros eliminados (AUTO_INCREMENT reset a 1).\n";
        }
        echo "Archivos borrados en uploads/: $archivos_borrados\n";
        echo "====================================================\n";
        exit(0);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Limpieza y Reseteo del Sistema - Gestión de Compras</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light text-slate-800 pb-20">

    <?php include __DIR__ . '/nav.php'; ?>

    <div class="container mt-4 px-3 px-md-4" style="max-width: 750px;">
        
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-dark text-white py-3 d-flex align-items-center gap-2">
                <i class="bi bi-trash3-fill text-warning fs-4"></i>
                <h5 class="fw-bold mb-0">Limpieza y Reseteo de Datos de Prueba</h5>
            </div>
            <div class="card-body p-4">
                
                <?php if ($ejecutar): ?>
                    <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
                        <i class="bi bi-check-circle-fill fs-4 shrink-0"></i>
                        <div>
                            <h6 class="fw-bold mb-0"><?= htmlspecialchars($mensaje) ?></h6>
                            <span class="small text-muted">Las tablas transaccionales han sido vaciadas y los IDs reseteados a 1.</span>
                        </div>
                    </div>

                    <div class="card border mb-4">
                        <div class="card-header bg-light py-2">
                            <strong class="small text-uppercase">Resumen de Registros Eliminados</strong>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Tabla Transaccional</th>
                                        <th class="text-end">Registros Purga</th>
                                        <th class="text-center">Estado Auto Increment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($detalles as $t => $c): ?>
                                        <tr>
                                            <td class="font-monospace fw-bold text-primary"><?= $t ?></td>
                                            <td class="text-end fw-bold"><?= number_format($c, 0, ',', '.') ?></td>
                                            <td class="text-center text-success small fw-bold">Reset a 1</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="alert alert-info py-2 text-center small mb-4">
                        <i class="bi bi-folder-check me-1"></i>
                        Se purgaron <strong><?= $archivos_borrados ?></strong> archivos PDF físicos en la carpeta <code>uploads/</code>.
                    </div>

                    <a href="index.php" class="btn btn-primary btn-lg w-100 fw-bold shadow">
                        <i class="bi bi-house-door me-1"></i> Volver al Inicio y Comenzar Pruebas
                    </a>

                <?php else: ?>

                    <div class="alert alert-warning border-warning p-3 mb-4">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-exclamation-triangle-fill fs-3 text-warning shrink-0"></i>
                            <div>
                                <h6 class="fw-bold mb-1">¿Desea reiniciar el sistema para realizar pruebas desde cero?</h6>
                                <p class="mb-0 small leading-relaxed">
                                    Esta acción eliminará de forma permanente todos los expedientes creados, ítems, firmas de OPI, historial de acciones y documentos PDF adjuntos.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="card border border-light bg-light p-3 mb-4">
                        <h6 class="fw-bold text-dark mb-2" style="font-size: 11px; text-transform: uppercase;">Elementos que se Purgan:</h6>
                        <ul class="small mb-0 text-secondary ps-3">
                            <li>Expedientes creados y folios asignados.</li>
                            <li>Ítems y líneas de productos de cada solicitud.</li>
                            <li>Historial de movimientos y firmas estampadas.</li>
                            <li>Documentos adjuntos y archivos en carpeta <code>uploads/</code>.</li>
                            <li>Reseteo de contadores correlativos (el próximo expediente será #1).</li>
                        </ul>
                        <hr class="my-2">
                        <h6 class="fw-bold text-dark mb-1" style="font-size: 11px; text-transform: uppercase;">Elementos Preservados:</h6>
                        <p class="small text-muted mb-0">Usuarios, Roles, Unidades, Centros de Costo, Plan de Cuentas Presupuestarias y Reglas de Flujos.</p>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="confirmar_limpieza" value="1">
                        <button type="submit" onclick="return confirm('¿Está COMPLETAMENTE SEGURO de reiniciar todas las solicitudes e iniciar pruebas desde cero?')" class="btn btn-danger btn-lg w-100 py-3 fw-bold shadow transition d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-trash3-fill"></i>
                            Confirmar y Reiniciar Sistema a Cero
                        </button>
                    </form>

                <?php endif; ?>

            </div>
        </div>

    </div>
</body>
</html>
