FROM php:8.3-apache

# Extensões necessárias
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    && docker-php-ext-install curl \
    && rm -rf /var/lib/apt/lists/*

# Habilita mod_rewrite
RUN a2enmod rewrite

# Copia o app
COPY app/ /var/www/html/

# Permissões
RUN chown -R www-data:www-data /var/www/html

# PHP config para uploads grandes
RUN echo "upload_max_filesize = 10M\npost_max_size = 12M\nmax_execution_time = 120" \
    > /usr/local/etc/php/conf.d/unify.ini
