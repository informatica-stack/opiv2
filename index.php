<?php
// index.php - Enrutador Principal e Inicio del Sistema de Órdenes de Pedido Interno
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Si no hay sesión iniciada, redirigir a Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Redirección inteligente al módulo principal según el Rol del usuario
$rol = $_SESSION['user_rol'] ?? '';

switch ($rol) {
    case 'SYSADMIN':
        header('Location: mis_solicitudes.php');
        break;
    case 'PRESUPUESTO': 
        header('Location: control_presupuestario.php'); 
        break;
    case 'FINANZAS': 
        header('Location: finanzas.php'); 
        break;
    case 'ADQUISICIONES': 
        header('Location: adquisiciones.php'); 
        break;
    case 'ADMIN_MUNICIPAL': 
        header('Location: administrador.php'); 
        break;
    case 'JEFE_UNIDAD': 
        header('Location: jefatura.php'); 
        break;
    case 'USUARIO_REQ':
    default: 
        if (isset($_SESSION['es_jefe']) && $_SESSION['es_jefe'] == 1) {
            header('Location: jefatura.php');
        } else {
            header('Location: mis_solicitudes.php');
        }
        break;
}
exit;
