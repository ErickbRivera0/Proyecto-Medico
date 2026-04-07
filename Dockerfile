FROM php:8.1-fpm


RUN a2dismod mpm_event && \
    a2enmod mpm_prefork

RUN apt-get update && apt-get install -y apache2 libapache2-mod-fcgid \
    && a2enmod proxy_fcgi setenvif \
    && a2enconf php8.1-fpm \
    && a2dismod mpm_prefork \
    && a2enmod mpm_event


RUN a2enmod rewrite


RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin \
    --filename=composer


WORKDIR /var/www/html


COPY . /var/www/html/


RUN chown -R www-data:www-data /var/www/html


RUN printf '%s\n' \
"#!/bin/bash" \
"set -e" \
"cd /var/www/html" \
"if [ -f composer.json ] && [ ! -d vendor ]; then composer install --no-interaction --prefer-dist --optimize-autoloader; fi" \
"exec apache2-foreground" \
> /usr/local/bin/startup.sh \
&& chmod +x /usr/local/bin/startup.sh

EXPOSE 80


CMD ["/usr/local/bin/startup.sh"]