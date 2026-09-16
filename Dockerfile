FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql

RUN a2enmod rewrite ssl

RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/ssl/private/ssl-cert-snakeoil.key \
    -out /etc/ssl/certs/ssl-cert-snakeoil.pem \
    -subj "/C=EE/ST=Harju/L=Tallinn/O=TalTech/CN=localhost"

RUN a2ensite default-ssl

WORKDIR /var/www/html

EXPOSE 80 443