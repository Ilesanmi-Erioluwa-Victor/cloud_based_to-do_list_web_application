FROM php:8.2-apache

RUN a2enmod rewrite

RUN sed -ri "s!/var/www/html!/var/www/html/public!g" /etc/apache2/sites-available/*.conf \
    && sed -ri "s!/var/www/!/var/www/html/public!g; s!AllowOverride None!AllowOverride All!g" /etc/apache2/apache2.conf

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libcurl4-openssl-dev \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-install curl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./

RUN composer install --no-interaction --optimize-autoloader --no-dev \
    --ignore-platform-req=ext-mongodb

COPY . .

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

ENV APP_ENV=production

EXPOSE 80

CMD ["apache2-foreground"]
