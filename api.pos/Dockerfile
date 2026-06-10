FROM php:8.2-apache
RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev unzip curl \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && a2enmod rewrite headers \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . /var/www/html
RUN composer install --no-dev --optimize-autoloader
