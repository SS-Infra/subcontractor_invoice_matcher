FROM php:8.3-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends poppler-utils \
 && rm -rf /var/lib/apt/lists/* \
 && docker-php-ext-install pdo pdo_sqlite \
 && a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html
COPY . /var/www/html

RUN mkdir -p /var/www/html/data/uploads /var/www/html/data/debug \
 && chown -R www-data:www-data /var/www/html/data

EXPOSE 80
