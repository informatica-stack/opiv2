<?php
// head.php - Encabezado HTML Unificado y Carga de Estilos Antiparpadeo (FOUC)
if (!isset($titulo_pagina)) {
    $titulo_pagina = 'Sistema de Órdenes de pedido interno';
}
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($titulo_pagina) ?></title>

<!-- Preconexión Anticipada DNS/TLS a CDNs -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://cdn.jsdelivr.net">

<!-- 1. Bootstrap 5.3.3 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

<!-- 2. Bootstrap Icons 1.11.3 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<!-- 3. Sistema de Diseño Global Custom (Prioridad Máxima sobre Bootstrap) -->
<link rel="stylesheet" href="css/style.css">
