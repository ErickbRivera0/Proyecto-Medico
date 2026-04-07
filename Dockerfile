FROM php:8.1-apache

RUN /bin/sh -c 'set -eu; a2dismod mpm_event mpm_worker || true; a2enmod mpm_prefork || true; exec docker-entrypoint.sh "$@"' -- apache2-foreground


RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin \
    --filename=composer

WORKDIR /var/www/html

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
