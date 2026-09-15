<?php
// mantenedor_cuentas.php - Redirección fluida al nuevo Centro Unificado de Cuentas y Centros de Costo
require_once __DIR__ . '/config.php';

header("Location: cuentas_centros_costo.php?tab=cuentas");
exit;