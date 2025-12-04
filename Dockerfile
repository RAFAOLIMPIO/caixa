# Usa imagem PHP com Apache
FROM php:8.1-apache

# Atualiza pacotes e instala dependências do PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Copia todos os arquivos do projeto para o diretório padrão do Apache
COPY . /var/www/html/

# Corrige permissões (opcional mas recomendado)
RUN chown -R www-data:www-data /var/www/html

# Habilita regravação de URLs (caso queira usar .htaccess no futuro)
RUN a2enmod rewrite

EXPOSE 80
CMD ["apache2-foreground"]
RUN apt-get update && apt-get install -y \
    libpq-dev \
    ca-certificates \
    && update-ca-certificates
