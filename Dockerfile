FROM php:8.1-apache

# Instalar extensiones necesarias
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Asegurar que solo mpm_prefork esté activo (requerido por mod_php)
# Borramos symlinks directamente para garantizar que no quede ningún MPM residual
RUN rm -f /etc/apache2/mods-enabled/mpm_event.conf \
          /etc/apache2/mods-enabled/mpm_event.load \
          /etc/apache2/mods-enabled/mpm_worker.conf \
          /etc/apache2/mods-enabled/mpm_worker.load \
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
