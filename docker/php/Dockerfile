FROM php:8.5-fpm-alpine AS php-base

WORKDIR /var/www

RUN apk add --no-cache \
        git \
        icu-libs \
        libpq \
        sqlite-libs \
        libzip \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libpq-dev \
        sqlite-dev \
        libzip-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql \
    && docker-php-ext-install -j"$(nproc)" pdo_sqlite \
    && docker-php-ext-install -j"$(nproc)" intl \
    && docker-php-ext-install -j"$(nproc)" zip \
    && apk del .build-deps

RUN git config --system --add safe.directory /var/www

FROM php-base AS vendor

WORKDIR /app

COPY --from=composer:2.8 /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --no-scripts \
    --optimize-autoloader

FROM php-base AS vendor-dev

WORKDIR /app

COPY --from=composer:2.8 /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./

RUN composer install \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --no-scripts \
    --optimize-autoloader

FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY resources ./resources
COPY public ./public
COPY vite.config.js ./

RUN npm run build

FROM php-base AS app

COPY . ./
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build
COPY docker/app/entrypoint.sh /usr/local/bin/app-entrypoint

RUN mkdir -p bootstrap/cache storage/framework/cache storage/framework/views storage/framework/sessions storage/logs \
    && chown -R www-data:www-data bootstrap/cache storage \
    && php artisan package:discover --ansi \
    && chmod +x /usr/local/bin/app-entrypoint

ENTRYPOINT ["/usr/local/bin/app-entrypoint"]
CMD ["php-fpm"]

FROM app AS app-dev

RUN apk add --no-cache --virtual .xdebug-build \
        $PHPIZE_DEPS \
        linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .xdebug-build

COPY --from=composer:2.8 /usr/bin/composer /usr/local/bin/composer
COPY --from=vendor-dev /app/vendor ./vendor
COPY docker/php/conf.d/99-xdebug.ini /usr/local/etc/php/conf.d/99-xdebug.ini

RUN php artisan package:discover --ansi
