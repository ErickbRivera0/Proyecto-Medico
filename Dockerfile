FROM php:8.1-apache

# Instalar extensiones necesarias
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Asegurar que solo mpm_prefork esté activo (requerido por mod_php)
# Deshabilitamos todos los MPMs primero y luego habilitamos únicamente mpm_prefork
RUN a2dismod mpm_event mpm_worker mpm_async || true \
 && a2enmod mpm_prefork \
 && a2enmod rewrite \
 && apache2ctl configtest

# Establecer el directorio de trabajo
WORKDIR /var/www/html

# Cambiar permisos
RUN chown -R www-data:www-data /var/www/html

# Exponer puerto
EXPOSE 80

# Comando por defecto
CMD ["apache2-foreground"]
