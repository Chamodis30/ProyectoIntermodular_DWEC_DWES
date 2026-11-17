# PHP 8.3 + Apache con la extensión pdo_mysql. El DocumentRoot apunta a public/ (lo único accesible).
FROM php:8.3-apache
RUN docker-php-ext-install pdo_mysql
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf
