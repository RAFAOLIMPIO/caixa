FROM php:8.2-apache

RUN apt-get update && apt-get install -y libzip-dev zip libpq-dev \
    && docker-php-ext-install pdo_pgsql zip \
    && a2enmod rewrite

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

WORKDIR /var/www/html

EXPOSE 80
