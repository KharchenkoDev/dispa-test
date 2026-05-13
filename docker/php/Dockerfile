# --- Base Stage ---
FROM php:8.4-fpm-alpine AS base

WORKDIR /var/www/html

RUN apk add --no-cache \
    unzip \
    libzip-dev \
    icu-dev \
    && docker-php-ext-install \
    pdo_mysql \
    intl \
    zip \
    opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# --- Dev Stage ---
FROM base AS dev
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps

# --- Prod Stage ---
FROM base AS prod
COPY ./app .
RUN composer install --no-dev --optimize-autoloader \
    && mkdir -p var/cache var/log \
    && chown -R www-data:www-data var/
