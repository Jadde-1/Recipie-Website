FROM php:8.2-apache

# PDO + MySQL er allerede installeret i dette billede
# Ingen kompilering nødvendig

COPY backend /var/www/backend
COPY frontend /var/www/html

RUN ln -s /var/www/backend/api /var/www/html/api
