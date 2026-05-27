FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && a2enmod rewrite headers remoteip ratelimit \
    && rm -rf /var/lib/apt/lists/*

RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

COPY . /var/www/html
COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-security.ini

RUN mkdir -p /var/www/html/storage/logs /var/www/html/storage/backups \
    && chown -R www-data:www-data /var/www/html/storage \
    && find /var/www/html/storage -type d -exec chmod 775 {} \; \
    && find /var/www/html/storage -type f -exec chmod 664 {} \;

EXPOSE 80
