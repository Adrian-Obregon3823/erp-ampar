FROM php:8.3-apache

# Dependencias de sistema (Firebird + GD + zip + composer)
RUN apt-get update && apt-get install -y \
    firebird-dev \
    libfbclient2 \
    libzip-dev \
    unzip \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    pkg-config \
    git \
 && rm -rf /var/lib/apt/lists/*

# Extensiones PHP: pdo_firebird, zip, gd, interbase
RUN docker-php-ext-install pdo_firebird zip \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuración PHP
RUN echo "memory_limit = 256M" > /usr/local/etc/php/conf.d/memory.ini \
 && echo "upload_max_filesize = 50M" >> /usr/local/etc/php/conf.d/memory.ini \
 && echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/memory.ini \
 && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/memory.ini


WORKDIR /var/www/html
EXPOSE 80
# Copiar proyecto (en runtime quedará “tapado” por tu volumen, pero sirve para el build)
COPY . /var/www/html

# Apache
RUN a2enmod rewrite
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Permisos
RUN chown -R www-data:www-data /var/www/html
