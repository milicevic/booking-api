FROM php:8.5-fpm

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libgmp-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_mysql gmp \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www
