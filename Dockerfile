FROM php:8.1-fpm

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Install Nginx, supervisor, and utilities required by Composer
RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        curl \
        unzip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy Nginx site configuration
COPY config/nginx.conf /etc/nginx/sites-available/default

# Copy PHP-FPM configuration
# clear_env=no is set inside this file so Railway's injected environment
# variables (MYSQLHOST, MYSQLUSER, MYSQLPASSWORD, etc.) are visible to PHP.
COPY config/php-fpm.conf /usr/local/etc/php-fpm.conf

# Copy supervisor configuration
COPY config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Ensure supervisor log directory exists
RUN mkdir -p /var/log/supervisor

# Set working directory
WORKDIR /var/www/html

# Copy Composer manifests first to leverage Docker layer caching.
# Dependencies are only re-installed when composer.json or composer.lock changes.
COPY --chown=www-data:www-data src/composer.json src/composer.lock* /var/www/html/

# Install PHP dependencies (including Dompdf).
# --no-dev:               skip development-only packages
# --optimize-autoloader:  generate a classmap for faster autoloading in production
# --no-interaction:       never prompt for user input during the build
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy application source into the webroot and set ownership
# Use --chown so files have correct owner without extra RUN layer
COPY --chown=www-data:www-data src/ /var/www/html/

# Fix permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port
EXPOSE 80

# Start PHP-FPM and Nginx via supervisor
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
