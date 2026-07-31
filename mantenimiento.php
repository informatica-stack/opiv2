<?php
// mantenimiento.php - Pantalla de bloqueo por mantenimiento
require_once __DIR__ . '/config.php';

// Si el usuario es administrador y logra entrar aquí, mostrar enlace para volver
if (session_status() === PHP_SESSION_NONE) session_start();
$es_admin = (($_SESSION['user_rol'] ?? '') === 'SYSADMIN');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenimiento en Curso - Sistema de Órdenes de Pedido Interno</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: radial-gradient(circle at center, #0f172a 0%, #020617 100%);
            min-height: 100vh;
            color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .maint-container {
            max-width: 500px;
            text-align: center;
            padding: 2.5rem;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            animation: fadeIn 0.8s ease-out;
        }

        .gear-icon-wrapper {
            position: relative;
            width: 100px;
            height: 100px;
            margin: 0 auto 2rem;
        }

        .gear-large {
            font-size: 80px;
            color: #3b82f6;
            display: inline-block;
            animation: spinClockwise 12s linear infinite;
        }

        .gear-small {
            position: absolute;
            bottom: 5px;
            right: 5px;
            font-size: 40px;
            color: #60a5fa;
            display: inline-block;
            animation: spinCounterClockwise 6s linear infinite;
        }

        @keyframes spinClockwise {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes spinCounterClockwise {
            0% { transform: rotate(360deg); }
            100% { transform: rotate(0deg); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h1 {
            font-weight: 800;
            letter-spacing: -1px;
            background: linear-gradient(135deg, #ffffff 30%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        p {
            color: #94a3b8;
            font-size: 16px;
            line-height: 1.6;
        }
    </style>
</head>
<body>

    <div class="maint-container">
        
        <!-- Animación de Engranajes -->
        <div class="gear-icon-wrapper">
            <i class="bi bi-gear-fill gear-large"></i>
            <i class="bi bi-gear-fill gear-small"></i>
        </div>

        <h1>Mantenimiento en Curso</h1>
        <p>Estamos realizando mejoras programadas y actualizaciones de seguridad en la plataforma para ofrecerle un mejor servicio. Estaremos de vuelta muy pronto.</p>
        
        <div class="border-top border-secondary-subtle pt-3 mt-4 opacity-50">
            <small class="text-xs text-uppercase tracking-wider">Sistema de Órdenes de Pedido Interno - Ilustre Municipalidad</small>
        </div>

        <?php if ($es_admin): ?>
            <div class="mt-4">
                <a href="master.php" class="btn btn-primary btn-sm px-4 py-2 rounded-pill fw-bold">
                    <i class="bi bi-shield-lock me-1"></i> Ir al Panel Maestro
                </a>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>
