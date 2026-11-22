FROM php:8.2.31-apache

WORKDIR /var/www/html

COPY --chown=www-data:www-data . /var/www/html/
COPY php.ini "$PHP_INI_DIR/php.ini"

COPY --chown=www-data:www-data deploy/apache/app-security.conf /etc/apache2/conf-available/app-security.conf

RUN docker-php-ext-install mysqli pdo pdo_mysql opcache \
    && a2enmod rewrite \
    && a2enconf app-security \
    && chmod -R 755 /var/www/html \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]