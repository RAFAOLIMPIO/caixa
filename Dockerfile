# ===========================================
# AUTO GEST - PHP 8.2 + Apache + PostgreSQL
# Deploy compatível com Render.com
# ===========================================

FROM php:8.2-apache

# Definir timezone do sistema
ENV TZ=America/Sao_Paulo
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Atualizar pacotes e instalar dependências necessárias
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip unzip curl \
    ca-certificates \
    && update-ca-certificates \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd mbstring zip pdo pdo_pgsql pgsql opcache

# Habilitar módulos do Apache
RUN a2enmod rewrite headers expires

# Copiar arquivos do projeto para o contêiner
COPY . /var/www/html/

# Garantir permissões corretas
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configurar PHP para produção
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Ajustar diretório de trabalho
WORKDIR /var/www/html

# Expor porta padrão
EXPOSE 80

# Comando padrão
CMD ["apache2-foreground"]
