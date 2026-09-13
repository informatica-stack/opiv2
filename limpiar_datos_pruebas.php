<?php
// limpiar_datos_pruebas.php - Herramienta de Limpieza y Reseteo del Sistema (Pruebas desde cero)
require_once __DIR__ . '/config.php';

$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
    $rol = $_SESSION['user_rol'] ?? '';
    if ($rol !== 'SYSADMIN' && $rol !== 'ADMIN_MUNICIPAL') {
        die("Acceso Denegado. Se requieren permisos de Administración para ejecutar esta acción.");
    }
}

$ejecutar = false;
$mensaje = '';
$tipo_mensaje = 'success';
$detalles = [];
$archivos_borrados = 0;
$limpiar_proveedores_test = false;
$sincronizar_flujos = true;
$reglas_sincronizadas = 0;

if ($is_cli) {
    // Si viene desde CLI con flag --confirm o confirm
    $args = $_SERVER['argv'] ?? [];
    if (in_array('--confirm', $args) || in_array('confirm', $args)) {
        $ejecutar = true;
        $limpiar_proveedores_test = in_array('--clean-providers', $args);
        $sincronizar_flujos = !in_array('--no-sync-flujos', $args);
    }
} else {
    // Si viene por POST en Web
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_limpieza'])) {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || $token !== ($_SESSION['csrf_token'] ?? '')) {
            $mensaje = "Error de validación de seguridad (Token CSRF inválido o expirado). Recargue la página e intente nuevamente.";
            $tipo_mensaje = 'danger';
        } else {
            $ejecutar = true;
            $limpiar_proveedores_test = !empty($_POST['limpiar_proveedores_test']);
            $sincronizar_flujos = !empty($_POST['sincronizar_flujos']);
        }
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
            $pdo->exec("DELETE FROM `$t`");
            $pdo->exec("ALTER TABLE `$t` AUTO_INCREMENT = 1");
            $detalles[$t] = $cnt;
        }

        // Limpiar proveedores de prueba creados durante los ensayos (preservando los iniciales id <= 2)
        if ($limpiar_proveedores_test) {
            $cnt_prov = $pdo->query("SELECT COUNT(*) FROM `proveedores` WHERE id > 2")->fetchColumn();
            $pdo->exec("DELETE FROM `proveedores` WHERE id > 2");
            $max_prov_id = (int)$pdo->query("SELECT IFNULL(MAX(id), 0) FROM `proveedores`")->fetchColumn();
            $next_prov_id = max(3, $max_prov_id + 1);
            $pdo->exec("ALTER TABLE `proveedores` AUTO_INCREMENT = $next_prov_id");
            $detalles['proveedores (test)'] = $cnt_prov;
        }

        // Sincronizar estados_tramite y flujos_definicion desde el archivo SQL maestro
        if ($sincronizar_flujos) {
            $sql_file = __DIR__ . '/sql/database_produccion_limpia.sql';
            if (!file_exists($sql_file)) {
                $sql_file = __DIR__ . '/OPI_produccion.sql';
            }

            if (file_exists($sql_file)) {
                $sql_content = file_get_contents($sql_file);

                // 1. Sincronizar estados_tramite
                if (preg_match('/INSERT INTO `estados_tramite`[^;]+;/s', $sql_content, $m_est)) {
                    $insert_est = rtrim(trim($m_est[0]), ';') . " ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), rol_responsable = VALUES(rol_responsable), descripcion = VALUES(descripcion);";
                    $pdo->exec($insert_est);
                }

                // 2. Sincronizar flujos_definicion
                $pdo->exec("DELETE FROM `flujos_definicion`");
                $pdo->exec("ALTER TABLE `flujos_definicion` AUTO_INCREMENT = 1");

                if (preg_match_all('/INSERT INTO `flujos_definicion`[^;]+;/s', $sql_content, $m_fluj)) {
                    foreach ($m_fluj[0] as $q) {
                        $pdo->exec($q);
                    }
                }
                $reglas_sincronizadas = (int)$pdo->query("SELECT COUNT(*) FROM `flujos_definicion`")->fetchColumn();
                $detalles['flujos_definicion (reglas sincronizadas)'] = $reglas_sincronizadas;
            }
        }

        // Borrar archivos en carpeta uploads
        $uploads_dir = __DIR__ . '/uploads';
        if (file_exists($uploads_dir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($uploads_dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                $todo = $fileinfo->getRealPath();
                $filename = $fileinfo->getFilename();
                // Preservar .gitkeep o index.html en uploads
                if ($fileinfo->isFile() && in_array($filename, ['.gitkeep', 'index.html'])) {
                    continue;
                }
                if ($fileinfo->isDir()) {
                    @rmdir($todo);
                } else {
                    @unlink($todo);
                    $archivos_borrados++;
                }
            }
        } else {
            @mkdir($uploads_dir, 0777, true);
        }

        // Asegurar existencia de index.html vacío protector
        if (!file_exists($uploads_dir . '/index.html')) {
            @file_put_contents($uploads_dir . '/index.html', '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Directory access is forbidden.</h1></body></html>');
        }

        $mensaje = "Limpieza y sincronización ejecutadas con éxito. El sistema ha sido reiniciado a cero con los flujos de trabajo actualizados.";

    } catch (Exception $e) {
        $mensaje = "Error al ejecutar limpieza: " . $e->getMessage();
        $tipo_mensaje = 'danger';
    } finally {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
}

if ($is_cli) {
    if (!$ejecutar) {
        echo "====================================================\n";
        echo "HERRAMIENTA DE LIMPIEZA DE DATOS DE PRUEBA (MUNI LEBU)\n";
        echo "====================================================\n";
        echo "ADVERTENCIA: Esta acción vaciará todos los expedientes, firmas,\n";
        echo "documentos e historial de prueba y reseteará los IDs a 1.\n";
        echo "También sincronizará las reglas de flujo desde el SQL maestro.\n\n";
        echo "Para ejecutar, incluya el flag --confirm:\n";
        echo "php limpiar_datos_pruebas.php --confirm\n\n";
        echo "Opciones:\n";
        echo "  --clean-providers  Purgar también proveedores de prueba creados.\n";
        echo "  --no-sync-flujos   Omitir la re-sincronización de reglas de flujo.\n";
        echo "====================================================\n";
        exit(0);
    } else {
        echo "====================================================\n";
        echo "RESUMEN DE LIMPIEZA EJECUTADA CON ÉXITO\n";
        echo "====================================================\n";
        foreach ($detalles as $t => $c) {
            echo "Elemento '$t': $c registros procesados.\n";
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
    <?php 
    $titulo_pagina = "Limpieza y Reseteo del Sistema";
    include __DIR__ . '/head.php'; 
    ?>
</head>
<body class="bg-light text-slate-800 d-flex flex-column min-vh-100">

    <?php include __DIR__ . '/nav.php'; ?>

    <div class="container mt-4 px-3 px-md-4" style="max-width: 750px;">
        
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-dark text-white py-3 d-flex align-items-center gap-2">
                <i class="bi bi-trash3-fill text-warning fs-4"></i>
                <h5 class="fw-bold mb-0">Limpieza y Reseteo de Datos de Prueba</h5>
            </div>
            <div class="card-body p-4">
                
                <?php if ($ejecutar && $tipo_mensaje === 'success'): ?>
                    <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
                        <i class="bi bi-check-circle-fill fs-4 shrink-0"></i>
                        <div>
                            <h6 class="fw-bold mb-0"><?= htmlspecialchars($mensaje) ?></h6>
                            <span class="small text-muted">Las tablas transaccionales han sido vaciadas y los correlativos reseteados a 1.</span>
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
                                        <th class="text-end">Registros Purgados</th>
                                        <th class="text-center">Estado Auto Increment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($detalles as $t => $c): ?>
                                        <tr>
                                            <td class="font-monospace fw-bold text-primary"><?= htmlspecialchars($t) ?></td>
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

                    <?php if (!empty($mensaje) && $tipo_mensaje === 'danger'): ?>
                        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
                            <i class="bi bi-exclamation-octagon-fill fs-4 shrink-0"></i>
                            <div>
                                <h6 class="fw-bold mb-0">Error en la operación</h6>
                                <span class="small"><?= htmlspecialchars($mensaje) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

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
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                        <input type="hidden" name="confirmar_limpieza" value="1">
                        
                        <div class="form-check form-switch mb-3 p-3 bg-white border rounded-3">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="sincronizar_flujos" id="chkSyncFlujos" value="1" checked>
                            <label class="form-check-label fw-bold small text-dark" for="chkSyncFlujos">
                                Sincronizar y actualizar reglas de flujos y estados de compra según el archivo SQL maestro
                            </label>
                        </div>

                        <div class="form-check form-switch mb-4 p-3 bg-white border rounded-3">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="limpiar_proveedores_test" id="chkProvTest" value="1" checked>
                            <label class="form-check-label fw-bold small text-dark" for="chkProvTest">
                                Limpiar también proveedores temporales creados en pruebas (preserva proveedores del catálogo base)
                            </label>
                        </div>

                        <button type="submit" onclick="return confirm('¿Está COMPLETAMENTE SEGURO de reiniciar todas las solicitudes e iniciar pruebas desde cero?')" class="btn btn-danger btn-lg w-100 py-3 fw-bold shadow transition d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-trash3-fill"></i>
                            Confirmar y Reiniciar Sistema a Cero
                        </button>
                    </form>

                <?php endif; ?>

            </div>
        </div>

    </div>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
