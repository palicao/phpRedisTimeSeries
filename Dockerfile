ARG PHP_VERSION=7.4
FROM php:${PHP_VERSION}-cli

ARG DEBIAN_FRONTEND=noninteractive
ARG COMPOSER_VERSION=2.5.8
ARG XDEBUG_PACKAGE=xdebug-3.1.5

WORKDIR /app

ENV XDEBUG_MODE=coverage

RUN apt-get -y upgrade && \
    apt-get - dist-upgrade && \
    apt-get update && \
    apt-get install -yqq zip git wget

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions igbinary redis xdebug

RUN wget https://github.com/composer/composer/releases/download/${COMPOSER_VERSION}/composer.phar -q && \
    mv composer.phar /usr/bin/composer && \
    chmod +x /usr/bin/composer
