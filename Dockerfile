FROM php:8.1-apache

Limpiar completamente archivos de configuración MPM conflictivas
RUN rm -f /etc/apache2/mods-enabled/mpm_.load && \
    rm -f /etc/apache2/mods-available/mpm_worker. && \
    rm -f /etc/apache2/mods-available/mpm_event.* && \
    a2enmod mpm_prefork rewrite

RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin \
    --filename=composer

WORKDIR /var/www/html

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]