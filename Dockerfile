FROM php:8.2-apache

RUN a2enmod rewrite ssl headers expires

RUN apt-get update

RUN apt-get install -y libpq-dev libzip-dev zip poppler-utils

RUN docker-php-ext-install pdo pdo_mysql zip

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"

RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer

RUN php -r "unlink('composer-setup.php');"
