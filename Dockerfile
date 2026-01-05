FROM php:8.4-cli
RUN apt-get update \
    && apt-get install -y \
        libzip-dev \
        unzip \
        git \
        wget \
    && docker-php-ext-install -j$(nproc) bcmath sockets \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configure git to allow the mounted project directory
RUN git config --global --add safe.directory /opt/project

RUN echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo "xdebug.mode=debug,develop" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

WORKDIR /opt/project

CMD ["php", "-S", "0.0.0.0:80", "-t", "/opt/project/tests/App"]
