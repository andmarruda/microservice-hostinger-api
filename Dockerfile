# ==========================================
# Stage 1: PHP & Composer's dependecies
# ==========================================
FROM php:8.4-cli-alpine AS php-builder

WORKDIR /app

RUN apk add --no-cache unzip git libzip-dev oniguruma-dev libxml2-dev sqlite-dev postgresql-dev linux-headers \
    && docker-php-ext-install zip mbstring xml dom bcmath pcntl pdo pdo_sqlite pdo_pgsql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev


# ==========================================
# Stage 2: Frontend build (Node.js)
# ==========================================
FROM node:22-alpine AS node-builder

WORKDIR /app

COPY --from=php-builder /usr/local/bin/php /usr/local/bin/php
COPY --from=php-builder /usr/local/lib/php /usr/local/lib/php
COPY --from=php-builder /usr/local/include/php /usr/local/include/php

RUN apk add --no-cache \
    bash \
    curl \
    sqlite \
    sqlite-dev \
    postgresql-dev \
    libzip-dev \
    oniguruma-dev \
    libxml2-dev \
    linux-headers

COPY --from=php-builder /app /app

RUN npm ci --ignore-scripts
RUN npm run build

RUN php artisan wayfinder:generate

FROM php:8.4-cli-alpine AS app

WORKDIR /var/www/html

RUN docker-php-ext-install \
    pdo \
    pdo_sqlite \
    pdo_pgsql \
    mbstring \
    xml \
    dom \
    bcmath \
    pcntl \
    zip \
    opcache

COPY --from=node-builder /app /var/www/html

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    PHP_CLI_SERVER_WORKERS=4

EXPOSE 8000

USER www-data

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]