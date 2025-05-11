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

# Copia os arquivos do projeto para o diretório padrão do Apache
COPY . /var/www/html/

# Corrige permissões
RUN chown -R www-data:www-data /var/www/html

# Define o diretório de trabalho
WORKDIR /var/www/html

# Expondo a porta 80
EXPOSE 80
