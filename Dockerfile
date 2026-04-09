FROM php:8.1-apache

# Instalar extensiones necesarias
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Forzar mpm_prefork (requerido por mod_php) y deshabilitar MPMs alternativos
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true \
 && a2enmod mpm_prefork \
 && a2enmod rewrite

# Establecer el directorio de trabajo
WORKDIR /var/www/html

# Cambiar permisos
RUN chown -R www-data:www-data /var/www/html

# Exponer puerto
EXPOSE 80

# Comando por defecto
CMD ["apache2-foreground"]
