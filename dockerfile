# Usar uma imagem oficial do PHP com Apache
FROM php:8.1-apache

# Instalar extensões do PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copiar os arquivos do projeto para o servidor
COPY . /var/www/html/

# Expor a porta 80
EXPOSE 80

# Comando para iniciar o Apache
CMD ["apache2-foreground"]