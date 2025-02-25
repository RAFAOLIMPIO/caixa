# Usa a imagem oficial do PHP com Apache
FROM php:8.2-apache

# Instala extensões necessárias, como MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copia os arquivos do projeto para o servidor
COPY . /var/www/html/

# Expõe a porta 80 (padrão do Apache)
EXPOSE 80

# Inicia o Apache
CMD ["apache2-foreground"]