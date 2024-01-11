FROM php:8.3.1-fpm-alpine

COPY ./.env /var/www/gis-laravel/.env
COPY ./composer.json /var/www/gis-laravel/composer.json
COPY ./composer.lock /var/www/gis-laravel/composer.lock
COPY ./package.json /var/www/gis-laravel/package.json
# COPY ./package-lock.json /var/www/gis-laravel/package-lock.json
COPY ./app/ /var/www/gis-laravel/app/
COPY ./bootstrap/ /var/www/gis-laravel/bootstrap/
COPY ./config/ /var/www/gis-laravel/config/
COPY ./database/ /var/www/gis-laravel/database/
COPY ./public/ /var/www/gis-laravel/public/
COPY ./resources/ /var/www/gis-laravel/resources/
COPY ./routes/ /var/www/gis-laravel/routes/
COPY ./storage/ /var/www/gis-laravel/storage/
COPY ./tests/ /var/www/gis-laravel/tests/
# COPY ./vendor /var/www/gis-laravel/vendor
COPY ./artisan /var/www/gis-laravel/artisan
COPY ./phpunit.xml /var/www/gis-laravel/phpunit.xml
COPY ./README.md /var/www/gis-laravel/README.md
COPY ./vite.config.js /var/www/gis-laravel/vite.config.js

# Install system dependencies
RUN apk --update add \
    curl \
    openssl \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    curl-dev \
    oniguruma-dev \
    postgresql-dev \
    postgresql-libs

# Clear cache
RUN apk del gcc g++
RUN rm -rf /var/cache/apk/*

# Install PHP extensions
RUN docker-php-ext-install bcmath curl gd exif mbstring mysqli pdo pdo_mysql pdo_pgsql pcntl xml zip

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/gis-laravel

# Add user for laravel application
RUN addgroup -g 1001 -S webgroup && adduser -u 1000 -S webuser -G webgroup

RUN chmod 770 -R /var/www/gis-laravel/ && chown -R webuser:webgroup /var/www/gis-laravel/

USER webuser

RUN composer install

CMD ["php-fpm"]

EXPOSE 9000
