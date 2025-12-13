# Stage 1 : Composer install #
FROM composer:2.5 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts

# Stage 2 : Runtime PHP-FPM  #
FROM php:8.2-fpm

# Dépendances système minimales
RUN apt-get update && apt-get install -y \
        git \
        unzip \
        libpng-dev libjpeg-dev libfreetype6-dev \
        libonig-dev libxml2-dev libzip-dev \
        default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring gd zip

WORKDIR /var/www

COPY --chown=www-data:www-data . /var/www
COPY --from=vendor /app/vendor /var/www/vendor

RUN php artisan key:generate || true \
 && mkdir -p storage/framework/{cache,sessions,views} \
 && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8080
CMD ["php-fpm"]
