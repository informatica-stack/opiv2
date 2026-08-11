FROM php:8.2-apache

# 1. Instalar extensiones de base de datos MySQL (mysqli y pdo_mysql)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# 2. Copiar los archivos de la aplicación al directorio web
COPY . /var/www/html/

# 3. Habilitar mod_rewrite de Apache (útil para URLs amigables)
RUN a2enmod rewrite

# 4. Configurar límites de subida de archivos y memoria en PHP
RUN echo "upload_max_filesize = 50M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini

# 5. Permisos de lectura/escritura para el servidor web
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80