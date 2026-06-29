# syntax=docker/dockerfile:1.7

# ========================================
# base image PHP-FPM
# ========================================
FROM php:8.5-fpm AS php-base

WORKDIR /var/www/app

# System dependencies
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        bash \
        git \
        tzdata \
        curl \
        libfcgi-bin \
        zip \
        unzip \
        libpq-dev \
        libzip-dev \
        librabbitmq-dev \
        libssl-dev \
    && rm -rf /var/lib/apt/lists/*

# Use mlocati/php-extension-installer
COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions

# PHP extensions
RUN install-php-extensions \
    intl \
    opcache \
    pdo_pgsql \
    zip \
    redis \
    amqp \
    bcmath \
    ctype \
    fileinfo \
    json \
    mbstring \
    tokenizer \
    xml

# Copy Composer
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# PHP Configs
COPY docker/php/conf/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/conf/php-fpm.conf /usr/local/etc/php-fpm.conf
COPY docker/php/conf/www.conf /usr/local/etc/php-fpm.d/www.conf

RUN mkdir -p /var/www/app && \
    chown -R www-data:www-data /var/www/app && \
    mkdir -p /var/www/.config/psysh && \
    chown -R www-data:www-data /var/www/.config && \
    chmod -R 755 /var/www/.config


# ========================================
# Dev image (with Xdebug)
# ========================================
FROM php-base AS dev

ENV APP_ENV=local \
    APP_DEBUG=true \
    COMPOSER_HOME=/tmp/composer

ARG HOST_UID=1000
ARG HOST_GID=1000

RUN install-php-extensions xdebug

COPY docker/php/conf/xdebug.ini /usr/local/etc/php/conf.d/zz-xdebug.ini

RUN groupmod -o -g "${HOST_GID}" www-data && \
    usermod -o -u "${HOST_UID}" -g "${HOST_GID}" www-data && \
    mkdir -p /tmp/composer && \
    chown -R www-data:www-data /tmp/composer

USER www-data

CMD ["php-fpm"]


# ========================================
# Build image (optimize for Laravel)
# ========================================
FROM php-base AS build

ENV APP_ENV=production \
    APP_DEBUG=false

COPY src/composer.json src/composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-progress \
    --no-interaction \
    --no-scripts

COPY src ./

RUN composer dump-autoload --classmap-authoritative --no-dev && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan event:cache


# ========================================
# Prod image (runtime)
# ========================================
FROM php-base AS prod

ENV APP_ENV=production \
    APP_DEBUG=false

COPY --from=build /var/www/app /var/www/app

RUN chown -R www-data:www-data /var/www/app && \
    chmod -R 775 /var/www/app/storage && \
    chmod -R 775 /var/www/app/bootstrap/cache

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
  CMD SCRIPT_NAME=/fpm-ping SCRIPT_FILENAME=/fpm-ping REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000 | grep -q "pong" || exit 1

USER www-data

CMD ["php-fpm"]
