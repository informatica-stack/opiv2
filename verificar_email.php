<?php
// verificar_email.php - Procesamiento de Enlace de Verificación de Correo Electrónico
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer_helper.php';

$token = trim($_GET['token'] ?? '');
$mensaje = '';
$tipo_mensaje = '';
$exito = false;

if (empty($token)) {
    $mensaje = "Token de verificación no especificado en la URL.";
    $tipo_mensaje = "danger";
} else {
    try {
        // Buscar el usuario por su token
        $stmt = $pdo->prepare("
            SELECT u.*, un.nombre as unidad_nombre 
            FROM usuarios u 
            LEFT JOIN unidades un ON u.unidad_id = un.id 
            WHERE u.token_verificacion = ?
        ");
        $stmt->execute([$token]);
        $usr = $stmt->fetch();

        if (!$usr) {
            $mensaje = "El enlace de verificación es inválido o ya fue utilizado anteriormente.";
            $tipo_mensaje = "warning";
        } else {
            // Actualizar usuario: correo verificado y pasa a PENDIENTE_APROBACION
            $stmtUpd = $pdo->prepare("
                UPDATE usuarios 
                SET email_verificado = 1, 
                    estado_aprobacion = 'PENDIENTE_APROBACION', 
                    token_verificacion = NULL 
                WHERE id = ?
            ");
            $stmtUpd->execute([$usr['id']]);

            // Notificar por correo a los administradores SYSADMIN
            $stmtSys = $pdo->query("
                SELECT u.email, u.nombre_completo 
                FROM usuarios u 
                JOIN roles r ON u.rol_id = r.id 
                WHERE r.nombre = 'SYSADMIN' AND u.activo = 1
            ");
            $sysadmins = $stmtSys->fetchAll();

            $link_admin = obtener_base_url() . "/usuarios.php";

            $cuerpo_sysadmin = '
                <h3 style="color: #1e293b; margin-top: 0;">[Atención SYSADMIN] Nueva Solicitud de Usuario</h3>
                <p>El siguiente funcionario ha verificado su correo electrónico con éxito y requiere aprobación y asignación de rol:</p>
                <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 6px; margin: 15px 0;">
                    <p style="margin: 3px 0;"><strong>Nombre:</strong> ' . htmlspecialchars($usr['nombre_completo']) . '</p>
                    <p style="margin: 3px 0;"><strong>RUT:</strong> ' . htmlspecialchars($usr['rut']) . '</p>
                    <p style="margin: 3px 0;"><strong>Email:</strong> ' . htmlspecialchars($usr['email']) . '</p>
                    <p style="margin: 3px 0;"><strong>Unidad:</strong> ' . htmlspecialchars($usr['unidad_nombre']) . '</p>
                    <p style="margin: 3px 0;"><strong>Cargo:</strong> ' . htmlspecialchars($usr['cargo']) . '</p>
                </div>
                <p style="text-align: center; margin: 25px 0;">
                    <a href="' . $link_admin . '" class="btn" style="background-color: #0d6efd; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">
                        Ir a Panel de Aprobación de Usuarios
                    </a>
                </p>
            ';

            if (!empty($sysadmins)) {
                foreach ($sysadmins as $sa) {
                    enviar_correo_institucional($sa['email'], $sa['nombre_completo'], "[OPI] Solicitud de Registro Pendiente de Aprobación", $cuerpo_sysadmin);
                }
            } else {
                // Notificación por defecto a informatica@lebu.cl
                enviar_correo_institucional('informatica@lebu.cl', 'Soporte Informática', "[OPI] Solicitud de Registro Pendiente de Aprobación", $cuerpo_sysadmin);
            }

            $mensaje = "¡Correo electrónico verificado con éxito! Su solicitud ha sido enviada al Administrador del Sistema (SYSADMIN) para la asignación de su rol y aprobación de acceso.";
            $tipo_mensaje = "success";
            $exito = true;
        }

    } catch (Exception $e) {
        $mensaje = "Error al procesar la verificación: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Correo - Municipalidad de Lebu</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; }
        .card-verif { max-width: 550px; border-radius: 12px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.08); }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 justify-content-center align-items-center py-5">

    <div class="container px-3">
        <div class="card card-verif mx-auto bg-white overflow-hidden text-center">
            
            <div class="card-header bg-dark text-white py-4 px-4">
                <img src="logo.png" alt="Municipalidad de Lebu" style="max-height: 50px;" class="mb-2">
                <h4 class="fw-bold mb-0">Verificación de Correo</h4>
            </div>

            <div class="card-body p-4 p-md-5">

                <?php if ($exito): ?>
                    <div class="mb-3 text-success">
                        <i class="bi bi-patch-check-fill" style="font-size: 64px;"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-2">¡Correo Verificado!</h4>
                    <p class="text-secondary small mb-4 leading-relaxed"><?= htmlspecialchars($mensaje) ?></p>
                    <a href="login.php" class="btn btn-primary fw-bold px-4 shadow-sm">Ir a Inicio de Sesión</a>
                <?php else: ?>
                    <div class="mb-3 text-<?= $tipo_mensaje === 'warning' ? 'warning' : 'danger' ?>">
                        <i class="bi bi-exclamation-triangle-fill" style="font-size: 64px;"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Verificación de Correo</h5>
                    <p class="text-secondary small mb-4 leading-relaxed"><?= htmlspecialchars($mensaje) ?></p>
                    <a href="login.php" class="btn btn-outline-dark fw-bold px-4">Volver al Inicio de Sesión</a>
                <?php endif; ?>

            </div>
        </div>
    </div>

</body>
</html>
