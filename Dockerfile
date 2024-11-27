FROM php:8.2-apache

RUN a2enmod rewrite ssl headers expires

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    poppler-utils \
    librdkafka-dev \
    default-mysql-client

RUN docker-php-ext-install pdo pdo_mysql zip

RUN pecl install rdkafka \
    && docker-php-ext-enable rdkafka

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html

WORKDIR /var/www/html

RUN composer install

COPY .env.example .env

RUN php artisan key:generate
