ARG PHP_VERSION=8.3

FROM php:${PHP_VERSION}-cli-alpine

COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer/composer:2-bin /composer /usr/local/bin/composer

RUN install-php-extensions mbstring pcov

WORKDIR /app
