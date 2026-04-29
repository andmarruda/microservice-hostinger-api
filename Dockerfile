# Stage 1: PHP/Composer dependencies
FROM php:8.4-cli-alpine AS composer-builder

WORKDIR /app

RUN apk add --no-cache unzip git libzip-dev oniguruma-dev libxml2-dev linux-headers \
    && docker-php-ext-install zip mbstring xml

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev

# Stage 2: Build frontend assets (needs PHP for wayfinder:generate)
FROM node:22-alpine AS node-builder

WORKDIR /app

RUN apk add --no-cache \
    php84 \
    php84-ctype \
    php84-dom \
    php84-fileinfo \
    php84-mbstring \
    php84-openssl \
    php84-phar \
    php84-pdo \
    php84-pdo_sqlite \
    php84-session \
    php84-simplexml \
    php84-sqlite3 \
    php84-tokenizer \
    php84-xml \
    php84-xmlreader \
    php84-xmlwriter \
    && ln -sf /usr/bin/php84 /usr/bin/php

#Copy all files without vendor
COPY . .

#install dependencies for app
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

RUN npm ci --ignore-scripts
RUN npm run build

# Stage 3: Final application image
FROM php:8.4-cli-alpine AS app

WORKDIR /var/www/html

RUN apk add --no-cache \
    bash \
    curl \
    sqlite \
    sqlite-dev \
    libzip-dev \
    oniguruma-dev \
    libxml2-dev \
    linux-headers

RUN docker-php-ext-install \
    pdo \
    pdo_sqlite \
    mbstring \
    xml \
    bcmath \
    pcntl \
    zip \
    opcache

# Copy full app from node-builder (has vendor + compiled assets)
COPY --from=node-builder /app .

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    PHP_CLI_SERVER_WORKERS=4

EXPOSE 8000

USER www-data

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
