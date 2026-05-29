# ==========================================
# Stage 1: Build (PHP + Node.js + Composer)
# ==========================================
FROM php:8.4-cli-alpine AS builder

WORKDIR /app

RUN apk add --no-cache \
    unzip git \
    libzip-dev oniguruma-dev libxml2-dev sqlite-dev postgresql-dev linux-headers \
    icu-dev icu-libs libpng-dev libjpeg-turbo-dev freetype-dev \
    nodejs npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install zip mbstring xml dom simplexml bcmath pcntl intl gd exif pdo pdo_sqlite pdo_pgsql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev

RUN npm ci --ignore-scripts \
    && php artisan wayfinder:generate \
    && npm run build


# ==========================================
# Stage 2: Production app (PHP-FPM)
# ==========================================
FROM php:8.4-fpm-alpine AS app

ARG APP_WORKDIR=/var/www/html
WORKDIR ${APP_WORKDIR}

RUN apk add --no-cache \
    libzip-dev oniguruma-dev libxml2-dev sqlite-dev postgresql-dev linux-headers \
    icu-dev icu-libs libpng-dev libjpeg-turbo-dev freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_sqlite pdo_pgsql mbstring xml dom simplexml bcmath pcntl intl gd exif zip \
    && docker-php-ext-enable opcache

COPY --from=builder /app ${APP_WORKDIR}

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

EXPOSE 9000

USER www-data

CMD ["php-fpm"]
