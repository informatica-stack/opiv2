<?php
// registro.php - Formulario Público de Auto-Registro de Usuarios con Validación de RUT Chileno
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/rut_helper.php';
require_once __DIR__ . '/mailer_helper.php';

// Si ya tiene sesión iniciada, redirigir a su panel
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$mensaje = '';
$tipo_mensaje = '';
$registro_exitoso = false;

// Cargar Unidades Municipales para la selección
$unidades = $pdo->query("SELECT * FROM unidades ORDER BY nombre ASC")->fetchAll();

// Obtener ID por defecto para rol USUARIO_REQ
$stmtRolReq = $pdo->query("SELECT id FROM roles WHERE nombre = 'USUARIO_REQ' LIMIT 1");
$default_rol_id = $stmtRolReq->fetchColumn() ?: 5;

// PROCESAR POST REGISTRO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $rut_raw = trim($_POST['rut'] ?? '');
        $nombre = trim($_POST['nombre_completo'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $unidad_id = intval($_POST['unidad_id'] ?? 0);
        $cargo = trim($_POST['cargo'] ?? '');
        $password_raw = $_POST['password'] ?? '';

        // 1. Validaciones de Campos Obligatorios
        if (empty($rut_raw) || empty($nombre) || empty($email) || empty($unidad_id) || empty($cargo) || empty($password_raw)) {
            throw new Exception("Todos los campos marcados con asterisco son obligatorios.");
        }

        // 2. Validación de RUT Chileno (Módulo 11)
        if (!validar_rut_chileno($rut_raw)) {
            throw new Exception("El RUT ingresado no es válido. Por favor verifique el número y dígito verificador.");
        }
        $rut = formatear_rut_chileno($rut_raw);

        // 3. Validación de Email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("La dirección de correo electrónico ingresada no tiene un formato válido.");
        }

        // 4. Verificar duplicados de RUT y Email
        $stmtCheckRut = $pdo->prepare("SELECT id FROM usuarios WHERE rut = ?");
        $stmtCheckRut->execute([$rut]);
        if ($stmtCheckRut->fetch()) {
            throw new Exception("El RUT $rut ya se encuentra registrado en el sistema.");
        }

        $stmtCheckEmail = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmtCheckEmail->execute([$email]);
        if ($stmtCheckEmail->fetch()) {
            throw new Exception("El correo electrónico $email ya se encuentra registrado.");
        }

        // 5. Hash de Contraseña y Token de Verificación
        $hash = password_hash($password_raw, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32));

        // 6. Insertar Usuario (Inactivo / Pendiente de verificación)
        $stmtIns = $pdo->prepare("
            INSERT INTO usuarios 
            (nombre_completo, email, rut, rol_id, unidad_id, es_jefe_unidad, cargo, password_hash, activo, token_verificacion, email_verificado, estado_aprobacion, fecha_registro) 
            VALUES (?, ?, ?, ?, ?, 0, ?, ?, 0, ?, 0, 'PENDIENTE_VERIFICACION', NOW())
        ");
        $stmtIns->execute([$nombre, $email, $rut, $default_rol_id, $unidad_id, $cargo, $hash, $token]);

        // 7. Enviar Correo de Verificación
        $link_verificacion = obtener_base_url() . "/verificar_email.php?token=$token";

        $cuerpo_correo = '
            <h3 style="color: #1e293b; margin-top: 0;">¡Bienvenido(a), ' . htmlspecialchars($nombre) . '!</h3>
            <p>Gracias por registrarte en el <strong>Sistema de Órdenes de Pedido Interno de la Municipalidad de Lebu</strong>.</p>
            <p>Para continuar con el proceso de activación de tu cuenta, por favor confirma tu correo electrónico haciendo clic en el siguiente botón:</p>
            <p style="text-align: center; margin: 30px 0;">
                <a href="' . $link_verificacion . '" class="btn" style="background-color: #0d6efd; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">
                    Confirmar mi Correo Electrónico
                </a>
            </p>
            <p style="font-size: 12px; color: #64748b;">Si no puedes hacer clic en el botón, copia y pega el siguiente enlace en tu navegador web:<br>
            <a href="' . $link_verificacion . '" style="color: #0d6efd;">' . $link_verificacion . '</a></p>
            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;">
            <p style="font-size: 12px; color: #64748b;">Una vez verificado tu correo, tu solicitud será revisada por el Administrador del Sistema (SYSADMIN) para la asignación definitiva de tu rol y activación final.</p>
        ';

        $correo_enviado = enviar_correo_institucional($email, $nombre, "Verificación de Correo - Registro OPI Municipalidad de Lebu", $cuerpo_correo);

        $mensaje = "Registro exitoso. Se ha enviado un enlace de verificación a su correo ($email). Por favor revise su bandeja de entrada para activar su solicitud.";
        $tipo_mensaje = "success";
        $registro_exitoso = true;

    } catch (Exception $e) {
        $mensaje = "Error en el registro: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto-Registro de Funcionario - Municipalidad de Lebu</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; }
        .card-registro { max-width: 650px; border-radius: 12px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.08); }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 justify-content-center align-items-center py-5">

    <div class="container px-3">
        <div class="card card-registro mx-auto bg-white overflow-hidden">
            
            <div class="card-header bg-dark text-white text-center py-4 px-4">
                <img src="logo.png" alt="Municipalidad de Lebu" style="max-height: 55px;" class="mb-2">
                <h4 class="fw-bold mb-1">Registro de Funcionario</h4>
                <p class="small text-white-50 mb-0">Sistema de Órdenes de Pedido Interno</p>
            </div>

            <div class="card-body p-4 p-md-5">

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?= $tipo_mensaje ?> d-flex align-items-center gap-2 mb-4" role="alert">
                        <i class="bi bi-<?= $tipo_mensaje === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> fs-4 shrink-0"></i>
                        <div class="small fw-semibold"><?= htmlspecialchars($mensaje) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($registro_exitoso): ?>
                    <div class="text-center py-3">
                        <div class="mb-3 text-success">
                            <i class="bi bi-envelope-check-fill" style="font-size: 64px;"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">¡Revisa tu Correo Electrónico!</h5>
                        <p class="text-secondary small mb-4">Hemos enviado un enlace de confirmación a tu casilla de correo. Haz clic en el enlace para verificar tu correo e informar al Administrador del Sistema para la aprobación final.</p>
                        <a href="login.php" class="btn btn-outline-dark fw-bold px-4">Volver al Inicio de Sesión</a>
                    </div>
                <?php else: ?>

                    <form method="POST" id="formRegistro" onsubmit="return validarFormularioRut()">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                        <div class="row g-3">
                            
                            <!-- RUT CHILENO -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">RUT Funcionario *</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light"><i class="bi bi-card-heading"></i></span>
                                    <input type="text" name="rut" id="rutInput" required class="form-control fw-bold text-dark" placeholder="12.345.678-K" onblur="formatearRutInput(this)">
                                </div>
                                <div id="rutFeedback" class="form-text text-danger small" style="display:none; font-size: 10px;">RUT no válido.</div>
                            </div>

                            <!-- NOMBRE COMPLETO -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Nombre Completo *</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light"><i class="bi bi-person-fill"></i></span>
                                    <input type="text" name="nombre_completo" required class="form-control" placeholder="Juan Pérez González">
                                </div>
                            </div>

                            <!-- CORREO INSTITUCIONAL -->
                            <div class="col-md-12">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Correo Electrónico Institucional *</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light"><i class="bi bi-envelope-fill"></i></span>
                                    <input type="email" name="email" required class="form-control" placeholder="ejemplo@lebu.cl">
                                </div>
                                <div class="form-text text-muted" style="font-size: 10px;">Le enviaremos un correo de verificación a esta casilla.</div>
                            </div>

                            <!-- UNIDAD MUNICIPAL -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Unidad Municipal *</label>
                                <select name="unidad_id" required class="form-select form-select-sm">
                                    <option value="">-- Seleccionar Unidad --</option>
                                    <?php foreach ($unidades as $u): ?>
                                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- CARGO -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Cargo / Función *</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light"><i class="bi bi-briefcase-fill"></i></span>
                                    <input type="text" name="cargo" required class="form-control" placeholder="Analista, Encargado, etc.">
                                </div>
                            </div>

                            <!-- CONTRASEÑA -->
                            <div class="col-md-12">
                                <label class="form-label fw-bold text-secondary small text-uppercase" style="font-size: 10px;">Contraseña de Acceso *</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" name="password" required class="form-control" minlength="6" placeholder="Mínimo 6 caracteres">
                                </div>
                            </div>

                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm d-flex justify-content-center align-items-center gap-2">
                                <i class="bi bi-person-plus-fill"></i>
                                Crear mi Cuenta de Funcionario
                            </button>
                        </div>

                        <div class="text-center mt-3 pt-3 border-top">
                            <a href="login.php" class="text-decoration-none small fw-bold text-secondary">
                                <i class="bi bi-arrow-left me-1"></i> ¿Ya tienes una cuenta? Inicia Sesión
                            </a>
                        </div>
                    </form>

                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- VALIDACIÓN CLIENTE RUT CHILENO (MÓDULO 11) -->
    <script>
    function validarRutM11(rut) {
        if (!rut) return false;
        let limpio = rut.replace(/[^0-9kK]/g, '');
        if (limpio.length < 7 || limpio.length > 9) return false;
        
        let dv = limpio.slice(-1).toUpperCase();
        let cuerpo = limpio.slice(0, -1);
        
        let suma = 0;
        let mult = 2;
        for (let i = cuerpo.length - 1; i >= 0; i--) {
            suma += parseInt(cuerpo.charAt(i)) * mult;
            mult = (mult === 7) ? 2 : mult + 1;
        }
        let resto = suma % 11;
        let dvCalc = 11 - resto;
        let dvEsp = (dvCalc === 11) ? '0' : (dvCalc === 10) ? 'K' : dvCalc.toString();
        
        return (dv === dvEsp);
    }

    function formatearRutInput(input) {
        let val = input.value.replace(/[^0-9kK]/g, '');
        if (val.length >= 2) {
            let dv = val.slice(-1).toUpperCase();
            let cuerpo = val.slice(0, -1);
            input.value = Number(cuerpo).toLocaleString('es-CL') + '-' + dv;
        }
    }

    function validarFormularioRut() {
        let input = document.getElementById('rutInput');
        let feedback = document.getElementById('rutFeedback');
        if (!validarRutM11(input.value)) {
            feedback.style.display = 'block';
            input.classList.add('is-invalid');
            input.focus();
            return false;
        }
        feedback.style.display = 'none';
        input.classList.remove('is-invalid');
        return true;
    }
    </script>
</body>
</html>
