<?php
// Archivo de prueba inofensivo de seguridad para comprobar si Nginx/PHP ejecuta scripts en uploads/
header('Content-Type: text/html; charset=utf-8');
echo "<h2>⚠️ ALERTA DE PRUEBA DE SEGURIDAD</h2>";
echo "<p>Si estás viendo este mensaje formateado por PHP, significa que el servidor web <b>EJECUTÓ</b> este archivo PHP dentro de la carpeta <code>/uploads/</code>.</p>";
echo "<p>Hora actual del servidor: <b>" . date('Y-m-d H:i:s') . "</b></p>";
?>
