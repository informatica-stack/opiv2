<?php
// usuarios.php - Módulo de Administración de Usuarios y Aprobación de Auto-Registros
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/rut_helper.php';
require_once __DIR__ . '/mailer_helper.php';

// Seguridad: Solo SYSADMIN o ADMIN_MUNICIPAL
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$rol = $_SESSION['user_rol'] ?? '';
if ($rol !== 'SYSADMIN' && $rol !== 'ADMIN_MUNICIPAL') {
    die("Acceso Denegado. Módulo de administración exclusivo.");
}

$mensaje = '';
$tipo_mensaje = 'success';
$accion = $_GET['action'] ?? 'listar';
$id_editar = $_GET['id'] ?? null;

// --- LÓGICA (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['action'] ?? '';

    if ($post_action === 'delete') {
        $id_eliminar = $_POST['id'] ?? null;
        if ($id_eliminar) {
            $stmt = $pdo->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?");
            $stmt->execute([$id_eliminar]);
            header("Location: usuarios.php?msg=" . urlencode("Usuario desactivado."));
            exit;
        }
    } 
    elseif ($post_action === 'aprobar_registro') {
        try {
            $id_usr = intval($_POST['id_usuario'] ?? 0);
            $rol_id = intval($_POST['rol_id'] ?? 0);
            
            if (!$id_usr || !$rol_id) throw new Exception("Debe seleccionar un rol para el usuario.");

            // Actualizar usuario a APROBADO y activo = 1
            $stmtUpd = $pdo->prepare("UPDATE usuarios SET activo = 1, estado_aprobacion = 'APROBADO', rol_id = ? WHERE id = ?");
            $stmtUpd->execute([$rol_id, $id_usr]);

            // Datos del usuario para el correo
            $stmtU = $pdo->prepare("SELECT u.*, r.nombre as rol_nombre FROM usuarios u JOIN roles r ON u.rol_id = r.id WHERE u.id = ?");
            $stmtU->execute([$id_usr]);
            $u_data = $stmtU->fetch();

            if ($u_data) {
                $link_login = obtener_base_url() . "/login.php";
                $cuerpo_aprobado = '
                    <h3 style="color: #1e293b; margin-top: 0;">¡Cuenta Aprobada!</h3>
                    <p>Estimado(a) <strong>' . htmlspecialchars($u_data['nombre_completo']) . '</strong>,</p>
                    <p>Tu solicitud de registro en el <strong>Sistema de Órdenes de Pedido Interno de la Municipalidad de Lebu</strong> ha sido aprobada.</p>
                    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 6px; margin: 15px 0;">
                        <p style="margin: 3px 0;"><strong>Rol Asignado:</strong> ' . htmlspecialchars($u_data['rol_nombre']) . '</p>
                        <p style="margin: 3px 0;"><strong>RUT:</strong> ' . htmlspecialchars($u_data['rut']) . '</p>
                    </div>
                    <p style="text-align: center; margin: 25px 0;">
                        <a href="' . $link_login . '" class="btn" style="background-color: #198754; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">
                            Iniciar Sesión Ahora
                        </a>
                    </p>
                ';
                enviar_correo_institucional($u_data['email'], $u_data['nombre_completo'], "[OPI] Tu Cuenta ha sido Aprobada", $cuerpo_aprobado);
            }

            header("Location: usuarios.php?tab=pendientes&msg=" . urlencode("Usuario aprobado y activado exitosamente."));
            exit;
        } catch (Exception $e) {
            $mensaje = "Error al aprobar: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    }
    elseif ($post_action === 'rechazar_registro') {
        try {
            $id_usr = intval($_POST['id_usuario'] ?? 0);
            $motivo = trim($_POST['motivo_rechazo'] ?? 'Solicitud no aprobada por administración.');
            
            if (!$id_usr) throw new Exception("Usuario no válido.");

            // Obtener datos antes de actualizar
            $stmtU = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmtU->execute([$id_usr]);
            $u_data = $stmtU->fetch();

            $stmtUpd = $pdo->prepare("UPDATE usuarios SET activo = 0, estado_aprobacion = 'RECHAZADO' WHERE id = ?");
            $stmtUpd->execute([$id_usr]);

            if ($u_data) {
                $cuerpo_rechazado = '
                    <h3 style="color: #991b1b; margin-top: 0;">Solicitud de Registro Rechazada</h3>
                    <p>Estimado(a) <strong>' . htmlspecialchars($u_data['nombre_completo']) . '</strong>,</p>
                    <p>Lamentamos informarle que su solicitud de registro en el Sistema OPI ha sido rechazada por la siguiente razón:</p>
                    <blockquote style="background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 10px 15px; margin: 15px 0; color: #991b1b;">
                        ' . htmlspecialchars($motivo) . '
                    </blockquote>
                    <p style="font-size: 12px; color: #64748b;">Si considera que se trata de un error, por favor contacte al Departamento de Informática.</p>
                ';
                enviar_correo_institucional($u_data['email'], $u_data['nombre_completo'], "[OPI] Solicitud de Registro Rechazada", $cuerpo_rechazado);
            }

            header("Location: usuarios.php?tab=pendientes&msg=" . urlencode("Solicitud de usuario rechazada."));
            exit;
        } catch (Exception $e) {
            $mensaje = "Error al rechazar: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    }
    else {
        // CREACIÓN / EDICIÓN MANUAL DESDE ADMIN
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $rut_raw = trim($_POST['rut'] ?? '');
        $rol_id = intval($_POST['rol_id'] ?? 0);
        $unidad_id = intval($_POST['unidad_id'] ?? 0);
        $es_jefe = isset($_POST['es_jefe_unidad']) ? 1 : 0;
        $cargo = trim($_POST['cargo'] ?? '');
        $password_raw = $_POST['password'] ?? '';
        $id = $_POST['id'] ?? null;

        try {
            if (!empty($rut_raw) && !validar_rut_chileno($rut_raw)) {
                throw new Exception("El RUT ingresado no es válido.");
            }
            $rut = !empty($rut_raw) ? formatear_rut_chileno($rut_raw) : '';

            if ($id) {
                // EDICIÓN
                $sql = "UPDATE usuarios SET nombre_completo=?, email=?, rut=?, rol_id=?, unidad_id=?, es_jefe_unidad=?, cargo=?";
                $params = [$nombre, $email, $rut, $rol_id, $unidad_id, $es_jefe, $cargo];
                
                if (!empty($password_raw)) {
                    $sql .= ", password_hash=?";
                    $params[] = password_hash($password_raw, PASSWORD_DEFAULT);
                }
                
                $sql .= " WHERE id=?";
                $params[] = $id;
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $mensaje = "Usuario actualizado correctamente.";
            } else {
                // CREACIÓN MANUAL DE USUARIO
                if(empty($password_raw)) throw new Exception("La contraseña es obligatoria para nuevos usuarios.");
                
                $hash = password_hash($password_raw, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_completo, email, rut, rol_id, unidad_id, es_jefe_unidad, cargo, password_hash, activo, email_verificado, estado_aprobacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 1, 'APROBADO')");
                $stmt->execute([$nombre, $email, $rut, $rol_id, $unidad_id, $es_jefe, $cargo, $hash]);
                $mensaje = "Usuario creado exitosamente.";
            }
            header("Location: usuarios.php?msg=" . urlencode($mensaje));
            exit;
        } catch (Exception $e) {
            $mensaje = "Error: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    }
}

// --- CONSULTAS ---
$usuarios = $pdo->query("
    SELECT u.*, r.nombre as rol_nombre, d.nombre as unidad_nombre 
    FROM usuarios u
    JOIN roles r ON u.rol_id = r.id
    JOIN unidades d ON u.unidad_id = d.id
    WHERE u.activo = 1 AND u.estado_aprobacion = 'APROBADO'
    ORDER BY u.nombre_completo ASC
")->fetchAll();

$pendientes_aprobacion = $pdo->query("
    SELECT u.*, d.nombre as unidad_nombre 
    FROM usuarios u
    LEFT JOIN unidades d ON u.unidad_id = d.id
    WHERE u.estado_aprobacion = 'PENDIENTE_APROBACION'
    ORDER BY u.fecha_registro DESC
")->fetchAll();

$count_pendientes = count($pendientes_aprobacion);

$roles = $pdo->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();
$unidades = $pdo->query("SELECT * FROM unidades ORDER BY nombre ASC")->fetchAll();

$data = null;
if ($accion === 'edit' && $id_editar) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id_editar]);
    $data = $stmt->fetch();
}

$tab_activa = $_GET['tab'] ?? ($count_pendientes > 0 && !isset($_GET['action']) ? 'pendientes' : 'activos');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php 
    $titulo_pagina = "Usuarios del Sistema";
    include __DIR__ . '/head.php'; 
    ?>
</head>
<body class="bg-light text-slate-800 pb-20 font-sans">

    <?php include __DIR__ . '/nav.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <div class="container mt-4 px-3 px-md-4">

        <!-- CABECERA -->
        <div class="row align-items-center mb-4 g-3">
            <div class="col-12 col-md">
                <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                    <i class="bi bi-people text-primary"></i>
                    Usuarios del Sistema
                </h1>
                <p class="text-muted small mb-0">Administración de cuentas, aprobación de auto-registros y asignación de roles.</p>
            </div>
            <div class="col-12 col-md-auto text-start text-md-end">
                <a href="usuarios.php?action=create" class="btn btn-primary btn-sm fw-bold px-3 shadow-sm d-flex align-items-center gap-1.5">
                    <i class="bi bi-person-plus"></i> Crear Usuario Manual
                </a>
            </div>
        </div>

        <!-- ALERTAS -->
        <?php if(!empty($_GET['msg']) || !empty($mensaje)): ?>
            <?php 
            $msg_text = $_GET['msg'] ?? $mensaje; 
            $alert_class = ($tipo_mensaje === 'danger') ? 'danger' : 'success';
            ?>
            <div class="alert alert-<?= $alert_class ?> d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill shrink-0"></i>
                <div class="small fw-semibold"><?= htmlspecialchars($msg_text) ?></div>
            </div>
        <?php endif; ?>

        <!-- FORMULARIO DE CREACIÓN / EDICIÓN -->
        <?php if ($accion === 'create' || $accion === 'edit'): ?>
            <div class="card shadow-sm border-light mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0 text-dark"><?= $accion === 'edit' ? 'Modificar Datos de Usuario' : 'Registrar Nuevo Usuario Manual' ?></h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="usuarios.php">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <?php if ($data): ?><input type="hidden" name="id" value="<?= $data['id'] ?>"><?php endif; ?>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Nombre Completo</label>
                                <input type="text" name="nombre" required value="<?= htmlspecialchars($data['nombre_completo'] ?? '') ?>" class="form-control bg-white">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">RUT del Usuario</label>
                                <input type="text" name="rut" required value="<?= htmlspecialchars($data['rut'] ?? '') ?>" class="form-control bg-white" placeholder="12.345.678-K">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Correo Electrónico</label>
                                <input type="email" name="email" required value="<?= htmlspecialchars($data['email'] ?? '') ?>" class="form-control bg-white">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Contraseña</label>
                                <input type="password" name="password" class="form-control bg-white" <?= $accion==='edit' ? '' : 'required' ?>>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Unidad Asignada</label>
                                <select name="unidad_id" required class="form-select bg-white text-sm">
                                    <?php foreach($unidades as $u): ?>
                                        <option value="<?= $u['id'] ?>" <?= (isset($data) && $data['unidad_id'] == $u['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($u['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Rol de Usuario</label>
                                <select name="rol_id" required class="form-select bg-white text-sm">
                                    <?php foreach($roles as $r): ?>
                                        <option value="<?= $r['id'] ?>" <?= (isset($data) && $data['rol_id'] == $r['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($r['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Cargo del Funcionario</label>
                                <input type="text" name="cargo" value="<?= htmlspecialchars($data['cargo'] ?? '') ?>" class="form-control bg-white">
                            </div>
                        </div>

                        <div class="form-check p-3 bg-light rounded border mb-4">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="es_jefe_unidad" value="1" id="jefe" <?= (isset($data) && $data['es_jefe_unidad'] == 1) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold text-dark small" for="jefe">
                                Este usuario es <strong>Jefe de Unidad</strong> (Tiene permisos de visación y firma de su departamento)
                            </label>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="usuarios.php" class="btn btn-outline-secondary btn-sm px-4">Cancelar</a>
                            <button type="submit" class="btn btn-primary btn-sm fw-bold px-4">Guardar Usuario</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- VISTA CON PESTAÑAS (TABLA DE USUARIOS ACTIVOS Y PENDIENTES) -->
        <?php if ($accion === 'listar'): ?>

            <ul class="nav nav-tabs border-bottom mb-4" id="usersTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link fw-bold d-flex align-items-center gap-2 py-2.5 px-3 <?= $tab_activa === 'activos' ? 'active' : '' ?>" href="?tab=activos">
                        <i class="bi bi-people-fill text-primary"></i>
                        <span>Usuarios Activos</span>
                        <span class="badge bg-secondary rounded-pill"><?= count($usuarios) ?></span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link fw-bold d-flex align-items-center gap-2 py-2.5 px-3 <?= $tab_activa === 'pendientes' ? 'active' : '' ?>" href="?tab=pendientes">
                        <i class="bi bi-clock-history text-warning"></i>
                        <span>Solicitudes Pendientes de Aprobación</span>
                        <span class="badge bg-warning text-dark rounded-pill"><?= $count_pendientes ?></span>
                    </a>
                </li>
            </ul>

            <div class="tab-content" id="usersTabsContent">
                
                <!-- PESTAÑA 1: USUARIOS ACTIVOS -->
                <?php if ($tab_activa === 'activos'): ?>
                    <div class="card shadow-sm border-light">
                        <div class="card-header bg-white py-3">
                            <h6 class="fw-bold mb-0 text-dark">Nómina de Usuarios Activos en el Sistema</h6>
                        </div>
                        <div class="table-responsive rounded-bottom">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-uppercase small" style="font-size: 10px;">
                                    <tr>
                                        <th class="p-3">Funcionario</th>
                                        <th class="p-3">RUT</th>
                                        <th class="p-3">Unidad / Cargo</th>
                                        <th class="p-3">Rol en el Sistema</th>
                                        <th class="p-3 text-center" style="width: 130px;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($usuarios)): ?>
                                        <tr>
                                            <td colspan="5" class="p-4 text-center text-muted">No existen usuarios registrados.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach($usuarios as $u): ?>
                                            <tr>
                                                <td class="p-3">
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($u['nombre_completo']) ?></div>
                                                    <div class="text-muted small"><?= htmlspecialchars($u['email']) ?></div>
                                                </td>
                                                <td class="p-3 font-monospace fw-bold text-secondary"><?= htmlspecialchars($u['rut']) ?></td>
                                                <td class="p-3">
                                                    <div class="fw-semibold text-dark small"><?= htmlspecialchars($u['unidad_nombre']) ?></div>
                                                    <div class="text-muted" style="font-size: 10px;"><?= htmlspecialchars($u['cargo']) ?> <?= $u['es_jefe_unidad'] ? '<span class="badge bg-warning text-dark ms-1">JEFE</span>' : '' ?></div>
                                                </td>
                                                <td class="p-3">
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 text-uppercase" style="font-size: 9px;">
                                                        <?= htmlspecialchars($u['rol_nombre']) ?>
                                                    </span>
                                                </td>
                                                <td class="p-3 text-center">
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <a href="usuarios.php?action=edit&id=<?= $u['id'] ?>" class="btn btn-outline-secondary btn-sm px-2 py-1" title="Editar">
                                                            <i class="bi bi-pencil-fill" style="font-size: 12px;"></i>
                                                        </a>
                                                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                                            <form method="POST" action="usuarios.php" onsubmit="return confirm('¿Confirma desactivar a este usuario?')">
                                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                                <button type="submit" class="btn btn-outline-danger btn-sm px-2 py-1" title="Desactivar">
                                                                    <i class="bi bi-trash-fill" style="font-size: 12px;"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- PESTAÑA 2: SOLICITUDES PENDIENTES DE APROBACIÓN -->
                <?php if ($tab_activa === 'pendientes'): ?>
                    <div class="card shadow-sm border-light">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark">Solicitudes de Auto-Registro Verificadas por Correo</h6>
                            <span class="badge bg-warning text-dark"><?= $count_pendientes ?> pendientes de rol</span>
                        </div>
                        <div class="table-responsive rounded-bottom">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-uppercase small" style="font-size: 10px;">
                                    <tr>
                                        <th class="p-3">Funcionario Solicitante</th>
                                        <th class="p-3">RUT</th>
                                        <th class="p-3">Unidad / Cargo</th>
                                        <th class="p-3" style="width: 220px;">Asignar Rol de Acceso</th>
                                        <th class="p-3 text-center" style="width: 180px;">Acción SYSADMIN</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($pendientes_aprobacion)): ?>
                                        <tr>
                                            <td colspan="5" class="p-4 text-center text-muted italic">No hay solicitudes pendientes de aprobación.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach($pendientes_aprobacion as $p): ?>
                                            <tr>
                                                <td class="p-3">
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($p['nombre_completo']) ?></div>
                                                    <div class="text-muted small"><i class="bi bi-envelope-check text-success me-1"></i><?= htmlspecialchars($p['email']) ?></div>
                                                    <div class="text-muted" style="font-size: 9px;">Registrado: <?= date('d/m/Y H:i', strtotime($p['fecha_registro'])) ?></div>
                                                </td>
                                                <td class="p-3 font-monospace fw-bold text-dark"><?= htmlspecialchars($p['rut']) ?></td>
                                                <td class="p-3">
                                                    <div class="fw-semibold text-secondary small"><?= htmlspecialchars($p['unidad_nombre'] ?? 'N/A') ?></div>
                                                    <div class="text-muted" style="font-size: 10px;"><?= htmlspecialchars($p['cargo']) ?></div>
                                                </td>
                                                
                                                <!-- FORMULARIO DE APROBACIÓN -->
                                                <td class="p-3" colspan="2">
                                                    <form method="POST" action="usuarios.php" class="d-flex align-items-center gap-2">
                                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                                        <input type="hidden" name="action" value="aprobar_registro">
                                                        <input type="hidden" name="id_usuario" value="<?= $p['id'] ?>">

                                                        <select name="rol_id" required class="form-select form-select-sm bg-white fw-bold">
                                                            <?php foreach($roles as $r): ?>
                                                                <option value="<?= $r['id'] ?>" <?= $r['nombre'] === 'USUARIO_REQ' ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($r['nombre']) ?> (<?= htmlspecialchars($r['descripcion']) ?>)
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>

                                                        <button type="submit" onclick="return confirm('¿Confirma la aprobación de este usuario y asignación de su rol?')" class="btn btn-success btn-sm fw-bold px-3 text-nowrap shadow-sm">
                                                            <i class="bi bi-check-circle-fill me-1"></i> Aprobar
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

        <?php endif; ?>

    </div>
</body>
</html>