FROM php:8.2-apache

# 1. Instalar dependencias del sistema y extensiones de PHP (MySQL, Zip, GD, etc.)
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install mysqli pdo pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

# 2. Copiar los archivos de la aplicación al directorio web
COPY . /var/www/html/

# 3. Habilitar mod_rewrite de Apache (útil para URLs amigables)
RUN a2enmod rewrite

# 4. Configurar límites de subida de archivos y memoria en PHP
RUN echo "upload_max_filesize = 50M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini

# 5. Crear directorio uploads y configurar permisos de lectura/escritura para el servidor web (www-data)
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/uploads

EXPOSE 80