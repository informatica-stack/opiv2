<?php
// master.php - Consola Suprema de Administración (V7.1)
require_once __DIR__ . '/config.php';

// Seguridad: Solo SYSADMIN
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if (($_SESSION['user_rol'] ?? '') !== 'SYSADMIN') {
    die("Acceso Denegado. Consola exclusiva para administradores de sistema (SYSADMIN).");
}

// --- RESPALDO DE BASE DE DATOS (DESCARGA DIRECTA) ---
if (isset($_GET['action']) && $_GET['action'] === 'backup_db') {
    try {
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="backup_opi_' . date('Y-m-d_H-i-s') . '.sql"');
        
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        echo "-- =====================================================\n";
        echo "-- RESPALDO DE BASE DE DATOS - SISTEMA DE ÓRDENES DE PEDIDO INTERNO\n";
        echo "-- GENERADO EL: " . date('Y-m-d H:i:s') . "\n";
        echo "-- =====================================================\n\n";
        
        echo "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        foreach ($tables as $table) {
            $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
            echo "--\n";
            echo "-- Estructura de tabla para `$table`\n";
            echo "--\n\n";
            echo "DROP TABLE IF EXISTS `$table`;\n";
            echo $create['Create Table'] . ";\n\n";
            
            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            if (count($rows) > 0) {
                echo "--\n";
                echo "-- Datos para la tabla `$table`\n";
                echo "--\n\n";
                echo "INSERT INTO `$table` VALUES \n";
                
                $values_list = [];
                foreach ($rows as $row) {
                    $row_values = [];
                    foreach ($row as $val) {
                        if ($val === null) {
                            $row_values[] = 'NULL';
                        } else {
                            $row_values[] = $pdo->quote($val);
                        }
                    }
                    $values_list[] = "(" . implode(", ", $row_values) . ")";
                }
                echo implode(",\n", $values_list) . ";\n\n";
            }
        }
        
        echo "SET FOREIGN_KEY_CHECKS=1;\n";
        exit;
    } catch (Exception $e) {
        die("Error al generar el respaldo: " . $e->getMessage());
    }
}

// Mensajes de estado
$mensaje = ''; $tipo_mensaje = '';
if (isset($_GET['msg'])) {
    $mensaje = $_GET['msg'];
    $tipo_mensaje = $_GET['tipo'] ?? 'success';
}

// --- ACCIONES POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $accion = $_POST['accion'] ?? '';
        
        if ($accion === 'reset_password') {
            $user_id = $_POST['user_id'];
            $new_password = $_POST['new_password'] ?? '';

            if (strlen($new_password) < 4) {
                throw new Exception("La contraseña debe tener al menos 4 caracteres.");
            }

            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hash, $user_id]);

            $mensaje = "Contraseña restablecida con éxito para el usuario.";
            $tipo_mensaje = "success";
        }
        
        if ($accion === 'actualizar_usuario') {
            $user_id = $_POST['user_id'];
            $rol_id = $_POST['rol_id'];
            $unidad_id = $_POST['unidad_id'];
            $es_jefe = isset($_POST['es_jefe_unidad']) ? 1 : 0;
            $activo = isset($_POST['activo']) ? 1 : 0;

            $stmt = $pdo->prepare("UPDATE usuarios SET rol_id = ?, unidad_id = ?, es_jefe_unidad = ?, activo = ? WHERE id = ?");
            $stmt->execute([$rol_id, $unidad_id, $es_jefe, $activo, $user_id]);

            $mensaje = "Usuario actualizado con éxito.";
            $tipo_mensaje = "success";
        }
        
        if ($accion === 'actualizar_configuracion') {
            $modo_mantenimiento = isset($_POST['modo_mantenimiento']) ? '1' : '0';
            $valor_utm = intval(str_replace(['.', '$', ' '], '', $_POST['valor_utm']));
            $limite_peso = intval($_POST['limite_peso']);
            
            $stmt = $pdo->prepare("UPDATE configuraciones_sistema SET valor = ? WHERE clave = ?");
            $stmt->execute([$modo_mantenimiento, 'modo_mantenimiento']);
            $stmt->execute([$valor_utm, 'valor_utm']);
            $stmt->execute([$limite_peso, 'limite_peso_adjunto_mb']);
            
            header("Location: master.php?msg=" . urlencode("Configuraciones del sistema actualizadas con éxito.") . "&tipo=success");
            exit;
        }

        if ($accion === 'limpiar_huerfanos') {
            $upload_dir = __DIR__ . '/uploads';
            $archivos_fisicos = [];
            if (is_dir($upload_dir)) {
                $files = scandir($upload_dir);
                foreach ($files as $f) {
                    if ($f !== '.' && $f !== '..') {
                        $archivos_fisicos[] = $f;
                    }
                }
            }
            
            $archivos_db = $pdo->query("SELECT ruta_archivo FROM expedientes_documentos")->fetchAll(PDO::FETCH_COLUMN);
            $nombres_db = array_map(function($path) {
                return basename($path);
            }, $archivos_db);
            
            $eliminados = 0;
            $espacio_liberado = 0;
            foreach ($archivos_fisicos as $file) {
                if (!in_array($file, $nombres_db)) {
                    $filepath = $upload_dir . '/' . $file;
                    if (is_file($filepath)) {
                        $size = filesize($filepath);
                        if (unlink($filepath)) {
                            $eliminados++;
                            $espacio_liberado += $size;
                        }
                    }
                }
            }
            
            $mb_liberados = round($espacio_liberado / (1024 * 1024), 2);
            header("Location: master.php?msg=" . urlencode("Limpieza de disco finalizada. Se eliminaron $eliminados archivos huérfanos ($mb_liberados MB liberados).") . "&tipo=success");
            exit;
        }

    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// --- CONSULTAR DATOS ---
$usuarios = $pdo->query("
    SELECT u.*, r.nombre as rol_nombre, un.nombre as unidad_nombre 
    FROM usuarios u
    JOIN roles r ON u.rol_id = r.id
    JOIN unidades un ON u.unidad_id = un.id
    ORDER BY r.nombre ASC, u.nombre_completo ASC
")->fetchAll();

$roles = $pdo->query("SELECT id, nombre, descripcion FROM roles ORDER BY nombre ASC")->fetchAll();
$unidades = $pdo->query("SELECT id, nombre FROM unidades ORDER BY nombre ASC")->fetchAll();

// --- ESTADÍSTICAS ---
$cant_usuarios = count($usuarios);
$cant_usuarios_activos = 0;
foreach ($usuarios as $u) { if ($u['activo']) $cant_usuarios_activos++; }
$cant_expedientes = $pdo->query("SELECT COUNT(*) FROM expedientes")->fetchColumn();
$cant_documentos = $pdo->query("SELECT COUNT(*) FROM expedientes_documentos")->fetchColumn();

// Tamaño en disco y archivos huérfanos
$upload_dir = __DIR__ . '/uploads';
$tamano_uploads = 0;
$archivos_fisicos_count = 0;
$archivos_huerfanos_count = 0;
$tamano_huerfanos = 0;

$archivos_db = $pdo->query("SELECT ruta_archivo FROM expedientes_documentos")->fetchAll(PDO::FETCH_COLUMN);
$nombres_db = array_map(function($path) {
    return basename($path);
}, $archivos_db);

if (is_dir($upload_dir)) {
    $files = scandir($upload_dir);
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..') {
            $filepath = $upload_dir . '/' . $f;
            if (is_file($filepath)) {
                $archivos_fisicos_count++;
                $size = filesize($filepath);
                $tamano_uploads += $size;
                
                if (!in_array($f, $nombres_db)) {
                    $archivos_huerfanos_count++;
                    $tamano_huerfanos += $size;
                }
            }
        }
    }
}
$tamano_uploads_mb = round($tamano_uploads / (1024 * 1024), 2);
$tamano_huerfanos_mb = round($tamano_huerfanos / (1024 * 1024), 2);

// --- LEER LOG DE ERRORES PHP ---
$error_log_path = ini_get('error_log');
if (empty($error_log_path) || !is_file($error_log_path) || !is_readable($error_log_path)) {
    $default_logs = [
        'C:\xampp\php\logs\php_error.log',
        'C:\xampp\apache\logs\error.log',
        'C:\xampp\php\php_error.log'
    ];
    foreach ($default_logs as $log) {
        if (is_file($log) && is_readable($log)) {
            $error_log_path = $log;
            break;
        }
    }
}

function get_last_lines($filepath, $lines = 100) {
    if (empty($filepath) || !is_file($filepath) || !is_readable($filepath)) {
        return "El archivo de logs no está configurado, no existe o no es accesible.\n\nRuta actual detectada: " . htmlspecialchars($filepath ?: 'Ninguna');
    }
    
    $file = fopen($filepath, 'r');
    if (!$file) return "Error al abrir el archivo de logs.";
    
    fseek($file, -1, SEEK_END);
    $pos = ftell($file);
    $line_count = 0;
    
    while ($pos > 0 && $line_count < $lines) {
        fseek($file, $pos, SEEK_SET);
        $char = fgetc($file);
        if ($char === "\n") {
            $line_count++;
        }
        $pos--;
    }
    
    fseek($file, $pos + 1, SEEK_SET);
    $output = '';
    while ($line = fgets($file)) {
        $output .= $line;
    }
    fclose($file);
    
    return $output;
}
$log_content = get_last_lines($error_log_path, 100);

// Cargar configs actuales
$modo_mantenimiento_val = intval($config_sistema['modo_mantenimiento'] ?? 0);
$valor_utm_val = floatval($config_sistema['valor_utm'] ?? 66000);
$limite_peso_val = intval($config_sistema['limite_peso_adjunto_mb'] ?? 10);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Maestro de Control - Sistema de Órdenes de Pedido Interno</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }

        .terminal-box {
            background-color: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 12px;
            font-family: 'Fira Code', monospace;
            font-size: 13px;
            color: #38bdf8;
            padding: 1.5rem;
            max-height: 500px;
            overflow-y: auto;
            white-space: pre-wrap;
        }

        .stat-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }

        .nav-tabs .nav-link {
            color: #64748b;
            border: none;
            border-bottom: 3px solid transparent;
            transition: all 0.2s ease;
        }

        .nav-tabs .nav-link.active {
            background: none !important;
            color: #0d6efd !important;
            border-bottom: 3px solid #0d6efd !important;
        }

        .nav-tabs .nav-link:hover {
            color: #1e293b;
            border-bottom: 3px solid #cbd5e1;
        }
    </style>
</head>
<body class="bg-light text-slate-800 font-sans pb-20">

    <!-- Navbar Superior Global -->
    <?php include __DIR__ . '/nav.php'; ?>

    <!-- Mantenimiento Alert Bar -->
    <?php if ($modo_mantenimiento_val === 1): ?>
        <div class="bg-warning text-dark text-center py-2 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm mb-3">
            <i class="bi bi-exclamation-triangle-fill animate-pulse"></i>
            SISTEMA EN MODO MANTENIMIENTO: El acceso a usuarios comunes está bloqueado.
        </div>
    <?php endif; ?>

    <div class="container mt-4 px-3 px-md-4">

        <!-- CABECERA -->
        <div class="row align-items-center mb-4 g-3">
            <div class="col-12">
                <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                    <i class="bi bi-shield-lock text-primary"></i>
                    Panel Maestro de Control
                </h1>
                <p class="text-muted small mb-0">Herramienta de desarrollo y administración global para credenciales, parámetros del entorno y depuración de salud del sistema.</p>
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

        <!-- NAVEGACIÓN PESTAÑAS (TABS) -->
        <ul class="nav nav-tabs border-bottom mb-4" id="masterTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold py-2.5" id="accounts-tab" data-bs-toggle="tab" data-bs-target="#accounts-pane" type="button" role="tab" aria-controls="accounts-pane" aria-selected="true">
                    <i class="bi bi-people me-1"></i> Gestión de Cuentas
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold py-2.5" id="system-tab" data-bs-toggle="tab" data-bs-target="#system-pane" type="button" role="tab" aria-controls="system-pane" aria-selected="false">
                    <i class="bi bi-sliders me-1"></i> Configuración Global
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold py-2.5" id="database-tab" data-bs-toggle="tab" data-bs-target="#database-pane" type="button" role="tab" aria-controls="database-pane" aria-selected="false">
                    <i class="bi bi-database-fill-gear me-1"></i> Mantenimiento BD y Salud
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold py-2.5" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs-pane" type="button" role="tab" aria-controls="logs-pane" aria-selected="false">
                    <i class="bi bi-terminal me-1"></i> Visor de Logs PHP
                </button>
            </li>
        </ul>

        <!-- CONTENIDOS DE PESTAÑAS -->
        <div class="tab-content" id="masterTabContent">
            
            <!-- TAB 1: GESTIÓN DE CUENTAS -->
            <div class="tab-pane fade show active" id="accounts-pane" role="tabpanel" aria-labelledby="accounts-tab" tabindex="0">
                <div class="card shadow-sm border-light bg-white">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark">Cuentas Registradas en el Sistema</h6>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 text-uppercase fw-bold" style="font-size: 9px;">Total: <?= $cant_usuarios ?></span>
                    </div>
                    
                    <div class="table-responsive rounded-bottom">
                        <table class="table table-hover align-middle mb-0 bg-white">
                            <thead class="table-light text-uppercase small" style="font-size: 10px;">
                                <tr>
                                    <th class="p-3">Usuario</th>
                                    <th class="p-3" style="width: 180px;">Rol en el Sistema</th>
                                    <th class="p-3" style="width: 200px;">Unidad</th>
                                    <th class="p-3 text-center" style="width: 160px;">Atributos</th>
                                    <th class="p-3 text-center" style="width: 260px;">Restablecer Contraseña</th>
                                    <th class="p-3 text-end" style="width: 120px;">Guardar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach($usuarios as $u): ?>
                                <tr>
                                    <!-- Formulario general por usuario -->
                                    <form method="POST">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        
                                        <td class="p-3">
                                            <div class="fw-bold text-dark small leading-tight"><?= htmlspecialchars($u['nombre_completo']) ?></div>
                                            <div class="text-muted mt-0.5" style="font-size: 10px;">
                                                <?= htmlspecialchars($u['email']) ?> &bull; <span class="font-monospace text-primary"><?= htmlspecialchars($u['rut']) ?></span>
                                            </div>
                                        </td>

                                        <td class="p-3">
                                            <select name="rol_id" class="form-select form-select-sm text-xs font-semibold bg-white border-light-subtle">
                                                <?php foreach ($roles as $r): ?>
                                                    <option value="<?= $r['id'] ?>" <?= $r['id'] == $u['rol_id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($r['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>

                                        <td class="p-3">
                                            <select name="unidad_id" class="form-select form-select-sm text-xs bg-white border-light-subtle">
                                                <?php foreach ($unidades as $un): ?>
                                                    <option value="<?= $un['id'] ?>" <?= $un['id'] == $u['unidad_id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($un['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>

                                        <td class="p-3">
                                            <div class="d-flex flex-column gap-1.5 align-items-center justify-content-center">
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input animate-none" type="checkbox" name="es_jefe_unidad" id="jefe_<?= $u['id'] ?>" value="1" <?= $u['es_jefe_unidad'] ? 'checked' : '' ?>>
                                                    <label class="form-check-label text-secondary" style="font-size: 10px;" for="jefe_<?= $u['id'] ?>">Es Jefe</label>
                                                </div>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input animate-none" type="checkbox" name="activo" id="act_<?= $u['id'] ?>" value="1" <?= $u['activo'] ? 'checked' : '' ?>>
                                                    <label class="form-check-label text-secondary" style="font-size: 10px;" for="act_<?= $u['id'] ?>">Activo</label>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="p-3">
                                            <div class="input-group input-group-sm justify-content-center">
                                                <input type="text" id="pwd_input_<?= $u['id'] ?>" class="form-control form-control-sm text-xs bg-white text-center border-light-subtle" style="max-width: 120px;">
                                                <button type="button" onclick="restablecerClave(<?= $u['id'] ?>)" class="btn btn-warning fw-bold text-xs shadow-sm">
                                                    Restablecer
                                                </button>
                                            </div>
                                        </td>

                                        <td class="p-3 text-end">
                                            <button type="submit" name="accion" value="actualizar_usuario" class="btn btn-primary btn-sm fw-bold px-3 shadow-sm">
                                                Guardar
                                            </button>
                                        </td>
                                    </form>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 2: CONFIGURACIÓN GLOBAL -->
            <div class="tab-pane fade" id="system-pane" role="tabpanel" aria-labelledby="system-tab" tabindex="0">
                <div class="card shadow-sm border-light bg-white">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="fw-bold mb-0 text-dark">Configuración del Entorno y Parámetros Globales</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="accion" value="actualizar_configuracion">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                            <div class="row g-4">
                                <div class="col-md-6 col-12">
                                    <div class="stat-card">
                                        <label class="form-label fw-bold text-dark d-flex align-items-center gap-2">
                                            <i class="bi bi-tools text-warning fs-5"></i> Modo Mantenimiento
                                        </label>
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" name="modo_mantenimiento" id="modoMantenimientoSwitch" value="1" <?= $modo_mantenimiento_val === 1 ? 'checked' : '' ?>>
                                            <label class="form-check-label text-muted small" for="modoMantenimientoSwitch">
                                                Activar para restringir el acceso a usuarios comunes. Solo los administradores podrán iniciar sesión.
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="stat-card">
                                        <label class="form-label fw-bold text-dark d-flex align-items-center gap-2">
                                            <i class="bi bi-coin text-success fs-5"></i> Valor UTM actual ($ CLP)
                                        </label>
                                        <input type="number" name="valor_utm" value="<?= $valor_utm_val ?>" required class="form-control mt-2 bg-white text-dark">
                                        <small class="text-muted d-block mt-1">Este valor se utiliza de referencia para los rangos monetarios y flujos condicionales.</small>
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="stat-card">
                                        <label class="form-label fw-bold text-dark d-flex align-items-center gap-2">
                                            <i class="bi bi-file-earmark-arrow-up text-primary fs-5"></i> Peso Máximo Adjunto (MB)
                                        </label>
                                        <input type="number" name="limite_peso" value="<?= $limite_peso_val ?>" required class="form-control mt-2 bg-white text-dark">
                                        <small class="text-muted d-block mt-1">Peso máximo en Megabytes permitido para la subida de documentos y cotizaciones.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end border-top mt-4 pt-3">
                                <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm">
                                    Guardar Cambios Globales
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- TAB 3: MANTENIMIENTO BD Y SALUD -->
            <div class="tab-pane fade" id="database-pane" role="tabpanel" aria-labelledby="database-tab" tabindex="0">
                <div class="row g-3 mb-4">
                    <!-- Stat 1 -->
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card text-center bg-white">
                            <h2 class="fw-bold text-primary mb-0"><?= $cant_usuarios_activos ?> <span class="fs-6 text-muted">/ <?= $cant_usuarios ?></span></h2>
                            <div class="small text-muted fw-bold mt-1 text-uppercase">Usuarios Activos</div>
                        </div>
                    </div>
                    <!-- Stat 2 -->
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card text-center bg-white">
                            <h2 class="fw-bold text-success mb-0"><?= $cant_expedientes ?></h2>
                            <div class="small text-muted fw-bold mt-1 text-uppercase">Total Expedientes</div>
                        </div>
                    </div>
                    <!-- Stat 3 -->
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card text-center bg-white">
                            <h2 class="fw-bold text-info mb-0"><?= $cant_documentos ?></h2>
                            <div class="small text-muted fw-bold mt-1 text-uppercase">Documentos en BD</div>
                        </div>
                    </div>
                    <!-- Stat 4 -->
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card text-center bg-white">
                            <h2 class="fw-bold text-warning mb-0"><?= $tamano_uploads_mb ?> <span class="fs-6">MB</span></h2>
                            <div class="small text-muted fw-bold mt-1 text-uppercase">Espacio Adjuntos</div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Respaldo -->
                    <div class="col-md-6 col-12">
                        <div class="card shadow-sm border-light bg-white h-100">
                            <div class="card-header bg-white border-bottom py-3">
                                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-cloud-download me-1 text-primary"></i> Copia de Seguridad</h6>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-between">
                                <p class="text-muted small">Descargue una copia de seguridad autocontenida de la base de datos en formato `.sql`. Este archivo contiene las instrucciones para recrear las tablas e insertar los datos en caso de migración o restauración.</p>
                                <div class="mt-3">
                                    <a href="master.php?action=backup_db" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">
                                        <i class="bi bi-download me-1"></i> Descargar Respaldo de Base de Datos
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Limpieza -->
                    <div class="col-md-6 col-12">
                        <div class="card shadow-sm border-light bg-white h-100">
                            <div class="card-header bg-white border-bottom py-3">
                                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-trash3 me-1 text-danger"></i> Depuración y Limpieza de Disco</h6>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-between">
                                <div>
                                    <p class="text-muted small mb-3">Escanea el directorio físico de almacenamiento de documentos (`uploads/`) y detecta archivos huérfanos que no correspondan a ningún expediente activo registrado en la base de datos.</p>
                                    <div class="stat-card bg-light border-0 p-3 mb-3 shadow-none">
                                        <div class="d-flex justify-content-between small text-muted">
                                            <span>Archivos Físicos en Servidor:</span>
                                            <span class="text-dark fw-bold"><?= $archivos_fisicos_count ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between small text-muted mt-1">
                                            <span>Archivos Huérfanos Detectados:</span>
                                            <span class="text-warning fw-bold"><?= $archivos_huerfanos_count ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between small text-muted mt-1">
                                            <span>Espacio Desperdiciado:</span>
                                            <span class="text-warning fw-bold"><?= $tamano_huerfanos_mb ?> MB</span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <form method="POST">
                                        <input type="hidden" name="accion" value="limpiar_huerfanos">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <button type="submit" class="btn btn-outline-danger w-100 fw-bold py-2 shadow-sm" <?= $archivos_huerfanos_count === 0 ? 'disabled' : '' ?>>
                                            <i class="bi bi-brush me-1"></i> Eliminar Archivos Huérfanos
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 4: VISOR DE LOGS PHP -->
            <div class="tab-pane fade" id="logs-pane" role="tabpanel" aria-labelledby="logs-tab" tabindex="0">
                <div class="card shadow-sm border-light bg-white">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-file-text me-1 text-info"></i> Log de Errores PHP (Últimas 100 Líneas)</h6>
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary px-2.5 py-1 font-monospace" style="font-size: 9px;"><?= htmlspecialchars(basename($error_log_path ?: 'No detectado')) ?></span>
                    </div>
                    <div class="card-body">
                        <div class="terminal-box"><?= htmlspecialchars($log_content) ?></div>
                        <div class="mt-3 text-muted small">
                            <i class="bi bi-info-circle me-1"></i>
                            El visor lee las últimas líneas del archivo de logs del sistema de forma segura.
                            <strong>Ruta completa detectada:</strong> <code class="text-primary font-monospace"><?= htmlspecialchars($error_log_path ?: 'Ninguna') ?></code>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Formulario oculto para restablecimiento de contraseña -->
        <form id="resetPwdForm" method="POST" class="d-none">
            <input type="hidden" name="accion" value="reset_password">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="user_id" id="resetUserId">
            <input type="hidden" name="new_password" id="resetNewPassword">
        </form>

    </div>

    <!-- JS LOGIC -->
    <script>
        function restablecerClave(userId) {
            const input = document.getElementById('pwd_input_' + userId);
            const pass = input.value.trim();
            if (pass.length < 4) {
                alert("La contraseña debe tener al menos 4 caracteres.");
                return;
            }
            if (confirm("¿Está seguro de cambiar la contraseña de este usuario?")) {
                document.getElementById('resetUserId').value = userId;
                document.getElementById('resetNewPassword').value = pass;
                document.getElementById('resetPwdForm').submit();
            }
        }
    </script>
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
