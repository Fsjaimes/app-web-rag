FROM php:8.2-fpm

# Extensiones del sistema necesarias para compilar extensiones PHP
RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libicu-dev libonig-dev libzip-dev \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libxml2-dev libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_pgsql pgsql \
        bcmath intl \
        gd zip \
        opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer viene de su imagen oficial — más limpio que instalarlo manual
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configuración personalizada de PHP
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www

# Alinea el UID con el usuario de tu Mac para evitar problemas de permisos
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data