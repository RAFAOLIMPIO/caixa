# Use imagem oficial do PHP com Apache
FROM php:8.1-apache

# Instale extensões se necessário
RUN docker-php-ext-install pdo pdo_pgsql

# Copie seu código para o diretório padrão do Apache
COPY . /var/www/html/

# (Opcional) ajuste permissões
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]