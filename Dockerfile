FROM php:7.3-cli
ARG DEBIAN_FRONTEND=noninteractive
WORKDIR /app

RUN apt-get -y upgrade && \
    apt-get - dist-upgrade && \
    apt-get update && \
    apt-get install -yqq zip git

RUN pecl install redis && \
    pecl install xdebug && \
    docker-php-ext-enable redis xdebug

RUN wget https://github.com/composer/composer/releases/download/1.9.1/composer.phar -q &&\
    mv composer.phar /usr/bin/composer && \
    chmod +x /usr/bin/composer

