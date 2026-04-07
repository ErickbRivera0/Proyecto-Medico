FROM php:8.1-fpm


RUN apt-get update && apt-get install -y apache2 libapache2-mod-fcgid \
    && a2dismod mpm_prefork mpm_worker \
    && a2enmod mpm_event \
    && a2enmod proxy_fcgi setenvif \
    && echo '<FilesMatch .php$>\n    SetHandler "proxy:unix:/run/php/php8.1-fpm.sock|fcgi://localhost/"\n</FilesMatch>' > /etc/apache2/conf-available/php8.1-fpm.conf \
    && a2enconf php8.1-fpm


RUN a2enmod rewrite


RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin \
    --filename=composer


WORKDIR /var/www/html


COPY . /var/www/html/


RUN chown -R www-data:www-data /var/www/html

RUN cat <<'EOF' > /usr/local/bin/startup.sh
#!/bin/bash
set -e
cd /var/www/html
if [ -f composer.json ] && [ ! -d vendor ]; then composer install --no-interaction --prefer-dist --optimize-autoloader; fi
exec apache2-foreground
EOF

RUN chmod +x /usr/local/bin/startup.sh

EXPOSE 80


CMD ["/usr/local/bin/startup.sh"]