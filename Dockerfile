FROM php:8.2-apache-bookworm

WORKDIR /var/www/html

COPY --chown=www-data:www-data . /var/www/html/

RUN docker-php-ext-install mysqli pdo pdo_mysql \
    && a2enmod rewrite \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]