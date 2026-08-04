#!/bin/sh
# Script de inicio para solucionar permisos de volúmenes en tiempo de ejecución (Runtime)
# Crear directorio de cargas en el volumen si no existe
mkdir -p /app/uploads
# Aplicar permisos 775 y Setgid (g+s) para herencia automática de grupo
chmod -R 775 /app/uploads || true
chmod g+s /app/uploads || true
# Cambiar propietario al usuario del servidor web si está disponible
(chown -R www-data:www-data /app/uploads 2>/dev/null || chown -R nobody:nogroup /app/uploads 2>/dev/null || true)
# Iniciar PHP-FPM y Nginx
php-fpm -D
exec nginx -g 'daemon off;'
