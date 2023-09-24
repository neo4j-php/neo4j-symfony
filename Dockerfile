FROM php:8.1-cli
RUN apt-get update \
    && apt-get install -y \
        libzip-dev \
        unzip \
        git \
        wget \
    && docker-php-ext-install -j$(nproc) bcmath sockets \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /opt/project



