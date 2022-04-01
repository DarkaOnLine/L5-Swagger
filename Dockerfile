#
# Base install
#
FROM amd64/php:8.0-apache as base

LABEL vendor="L5 Swagger"

# Arguments defined in docker-compose.yml
ARG user
ARG uid

# Set common env variables
ENV TZ="UTC"

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip memcached libmemcached-dev libmemcached-tools nano

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

RUN pecl install memcached

RUN pecl install -f xdebug \
    && docker-php-ext-enable xdebug

RUN a2enmod rewrite

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

COPY --chown=root:root docker/php/php.ini /usr/local/etc/php/php.ini
COPY --chown=root:root docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY --chown=www-data:www-data . /app

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

RUN chown -R $user:$user /app

WORKDIR /app

USER $user
RUN alias composer='/usr/local/bin/php -dxdebug.mode=off /usr/local/bin/composer'

RUN /usr/local/bin/php -dxdebug.mode=off /usr/local/bin/composer install --prefer-dist -vvv

RUN /usr/local/bin/php -dxdebug.mode=off /usr/local/bin/composer create-project laravel/laravel l5-swagger-app --no-interaction

WORKDIR /app/l5-swagger-app

RUN /usr/local/bin/php -dxdebug.mode=off /usr/local/bin/composer config repositories.l5-swagger path '../'

RUN /usr/local/bin/php -dxdebug.mode=off /usr/local/bin/composer require 'darkaonline/l5-swagger:dev-master'

RUN ln -s /app/tests/storage/annotations/ app/annotations

RUN chown -R $user:$user .
#
# Build dev stuff
#
FROM base as local

ENV PHP_IDE_CONFIG="serverName=l5-swagger"
ENV APP_ENV="local"
ENV APACHE_DOCUMENT_ROOT="/app/l5-swagger-app/public"
ENV L5_SWAGGER_GENERATE_ALWAYS="true"
