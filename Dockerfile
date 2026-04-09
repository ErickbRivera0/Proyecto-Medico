FROM php:8.1-apache

# Instalar extensiones necesarias
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Habilitar mod_rewrite para URLs limpias
RUN a2enmod rewrite

# Establecer el directorio de trabajo
WORKDIR /var/www/html

# Cambiar permisos
RUN chown -R www-data:www-data /var/www/html

# Exponer puerto
EXPOSE 80

# Comando por defecto
CMD ["apache2-foreground"]
