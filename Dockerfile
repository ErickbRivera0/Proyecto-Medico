FROM php:8.1-fpm

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Install Nginx and supervisor to manage both processes
RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
    && rm -rf /var/lib/apt/lists/*

# Copy Nginx site configuration
COPY config/nginx.conf /etc/nginx/sites-available/default

# Copy supervisor configuration
COPY config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Ensure supervisor log directory exists
RUN mkdir -p /var/log/supervisor

# Set working directory
WORKDIR /var/www/html

# Fix permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port
EXPOSE 80

# Start PHP-FPM and Nginx via supervisor
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
