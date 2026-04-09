### Builder: install composer dependencies ###
FROM php:8.1-cli AS builder

# Install system deps needed for PHP extensions and composer packages
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions required by packages (gd, mbstring, zip)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd mbstring zip

# Provide composer binary from the official composer image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files first to leverage Docker cache
COPY src/composer.json src/composer.lock* /app/

# Install PHP dependencies (no-dev, optimized autoloader)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Copy the rest of the application source (excluding vendor which composer created)
COPY src/ /app/


### Final: runtime image ###
FROM php:8.1-fpm

# Install runtime system packages, nginx and supervisor
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    nginx \
    supervisor \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions used at runtime
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mysqli gd mbstring zip

# Copy Nginx, PHP-FPM and supervisor configs
COPY config/nginx.conf /etc/nginx/sites-available/default
COPY config/php-fpm.conf /usr/local/etc/php-fpm.conf
COPY config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

RUN mkdir -p /var/log/supervisor

WORKDIR /var/www/html

# Copy application files and set ownership
COPY --chown=www-data:www-data src/ /var/www/html/

# Copy vendor from builder stage
COPY --from=builder /app/vendor /var/www/html/vendor

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
