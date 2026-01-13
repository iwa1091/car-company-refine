FROM php:8.3-fpm

COPY php.ini /usr/local/etc/php/

RUN apt-get update \
  && apt-get install -y --no-install-recommends \
    git curl unzip \
    libzip-dev zlib1g-dev \
    libpng-dev libjpeg-dev libwebp-dev libfreetype6-dev \
  && docker-php-ext-install pdo_mysql zip \
  && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
  && docker-php-ext-install gd \
  && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php \
  && mv composer.phar /usr/local/bin/composer

WORKDIR /var/www
