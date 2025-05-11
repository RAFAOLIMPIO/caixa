FROM php:8.2-apache

# Instala dependências do sistema e extensões do PHP
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    && docker-php-ext-install \
    mysqli \
    pdo \
    pdo_mysql \
    zip \
    && a2enmod rewrite

# Habilita o mod_rewrite do Apache (para URLs amigáveis)
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Define o diretório de trabalho
WORKDIR /var/www/html

# Expõe a porta 80
EXPOSE 80
