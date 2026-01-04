# Stage 1 : Composer install
FROM composer:2.7 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --optimize-autoloader

# Stage 2 : Runtime PHP CLI (pour Railway)
FROM php:8.2-cli

# Dépendances système + PostgreSQL
RUN apt-get update && apt-get install -y \
        git \
        unzip \
        libpq-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Extensions PHP avec PostgreSQL
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        mbstring \
        gd \
        zip \
        pcntl \
        bcmath

WORKDIR /var/www

# Copier les fichiers
COPY --chown=www-data:www-data . /var/www
COPY --from=vendor /app/vendor /var/www/vendor

# Préparer les dossiers
RUN mkdir -p storage/framework/{cache,sessions,views} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Port Railway
EXPOSE 8000

# Commande de démarrage
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
