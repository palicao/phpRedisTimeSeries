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

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php -r "if (hash_file('sha384', 'composer-setup.php') === 'a5c698ffe4b8e849a443b120cd5ba38043260d5c4023dbf93e1558871f1f07f58274fc6f4c93bcfd858c6bd0775cd8d1') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" && \
    php composer-setup.php --install-dir /usr/bin --filename composer && \
    php -r "unlink('composer-setup.php');"
