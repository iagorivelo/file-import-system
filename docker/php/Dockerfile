# syntax=docker/dockerfile:1
FROM php:8.5-fpm-bookworm

ARG UID=1000
ARG GID=1000

# install-php-extensions resolve extensões e suas dependências de sistema
# de forma robusta (https://github.com/mlocati/docker-php-extension-installer).
COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions \
        pdo_mysql \
        pdo_sqlite \
        sqlite3 \
        mbstring \
        bcmath \
        zip \
        gd \
        intl \
        pcntl \
        opcache \
        redis

# Configuração customizada do PHP
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini

# Composer (binário oficial)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Usuário não-root espelhando o UID/GID do host
# (evita problemas de permissão nos volumes montados em desenvolvimento)
RUN groupadd -g ${GID} app \
    && useradd -u ${UID} -g app -m -s /bin/bash app

WORKDIR /var/www/html

USER app

EXPOSE 9000

CMD ["php-fpm", "-F"]
