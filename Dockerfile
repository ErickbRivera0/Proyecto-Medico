FROM php:8.1-apache

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    default-mysql-client \
    libxrender1 \
    libfontconfig1 \
    libxext6 \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql mysqli gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2dismod mpm_event mpm_worker || true \
    && a2enmod mpm_prefork rewrite || true

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

COPY src/ /var/www/html/
COPY config/ /var/www/html/config/

RUN chown -R www-data:www-data /var/www/html

RUN cat > /usr/local/bin/startup.sh <<'BASH'\n#!/bin/bash\nset -e\ncd /var/www/html\n# Esperar a que la BD esté lista (si mysqladmin está disponible)\nDB_HOST="${DB_HOST:-localhost}"\nDB_PORT="${DB_PORT:-3306}"\nif command -v mysqladmin >/dev/null 2>&1; then\n  echo "Waiting for database $DB_HOST:$DB_PORT..."\n  until mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" --silent; do\n    sleep 1\n  done\nfi\nif [ -f composer.json ] && [ ! -d vendor ]; then\n  composer install --no-interaction --prefer-dist --optimize-autoloader\nfi\nphp init-db.php\nexec apache2-foreground\nBASH
RUN chmod +x /usr/local/bin/startup.sh

EXPOSE 80

CMD ["/usr/local/bin/startup.sh"]