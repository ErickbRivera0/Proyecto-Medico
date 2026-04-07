FROM php:8.1-apache

# 🔥 SOLUCIÓN ERROR AH00534 (MPM) EN RAILWAY
RUN a2dismod mpm_event && \
    a2enmod mpm_prefork

# Instalar dependencias del sistema y extensiones PHP necesarias
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libxrender1 \
    libfontconfig1 \
    libxext6 \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql mysqli gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Habilitar mod_rewrite para URLs limpias
RUN a2enmod rewrite

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin \
    --filename=composer

# Directorio de trabajo
WORKDIR /var/www/html

# Copiar el proyecto al contenedor
COPY . /var/www/html/

# Permisos correctos
RUN chown -R www-data:www-data /var/www/html

# Script de arranque: instala dependencias si faltan y arranca Apache
RUN printf '%s\n' \
"#!/bin/bash" \
"set -e" \
"cd /var/www/html" \
"if [ -f composer.json ] && [ ! -d vendor ]; then composer install --no-interaction --prefer-dist --optimize-autoloader; fi" \
"exec apache2-foreground" \
> /usr/local/bin/startup.sh \
&& chmod +x /usr/local/bin/startup.sh

# Exponer puerto
EXPOSE 80

# Comando por defecto
CMD ["/usr/local/bin/startup.sh"]