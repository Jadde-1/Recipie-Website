FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql

COPY backend /var/www/backend
COPY frontend /var/www/html

RUN ln -s /var/www/backend/api /var/www/html/api
