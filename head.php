<?php
// head.php - Encabezado HTML Unificado y Carga de Estilos Antiparpadeo (FOUC)
if (!isset($titulo_pagina)) {
    $titulo_pagina = 'Sistema de Órdenes de pedido interno';
}
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="light">
<script>document.documentElement.setAttribute('data-bs-theme', 'light');</script>
<title><?= htmlspecialchars($titulo_pagina) ?></title>

<!-- Preconexión Anticipada DNS/TLS a CDNs -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://cdn.jsdelivr.net">

<!-- 1. Bootstrap 5.3.3 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

<!-- 2. Bootstrap Icons 1.11.3 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<!-- Tipografía Oficial Inter (Diseño Moderno SaaS) y Roboto -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

<!-- 3. Sistema de Diseño Global Custom -->
<link rel="stylesheet" href="css/style.css">
<!-- 4. Sistema de Diseño SaaS Clean Minimalist Unificado -->
<link rel="stylesheet" href="css/saas-theme.css">

<!-- 5. Bootstrap 5.3.3 JS Bundle (Carga Centralizada) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
