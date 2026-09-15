<?php
// centros_de_costo.php - Redirección fluida al nuevo Centro Unificado de Cuentas y Centros de Costo
require_once __DIR__ . '/config.php';

$query = $_SERVER['QUERY_STRING'] ?? '';
if (!empty($query)) {
    header("Location: cuentas_centros_costo.php?" . $query);
} else {
    header("Location: cuentas_centros_costo.php?tab=centros");
}
exit;