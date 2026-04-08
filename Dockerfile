FROM php:8.1-apache


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
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql mysqli gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*


RUN a2enmod rewrite


RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


WORKDIR /var/www/html


RUN chown -R www-data:www-data /var/www/html


RUN printf '%s\n' "#!/bin/bash" "set -e" "cd /var/www/html" "if [ -f composer.json ] && [ ! -d vendor ]; then composer install --no-interaction --prefer-dist --optimize-autoloader; fi" "php init-db.php" "exec apache2-foreground" > /usr/local/bin/startup.sh \
    && chmod +x /usr/local/bin/startup.sh


EXPOSE 80

CMD ["/usr/local/bin/startup.sh"]