# Laravel 12 + PHP 8.3 + Node.js + Yarn + Postgres uyumlu Dockerfile
FROM php:8.3-fpm

# Sistem bağımlılıkları ve araçlar
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpq-dev libonig-dev libxml2-dev \
    libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring zip exif pcntl bcmath gd

# Composer kurulumu
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Node.js & Yarn kurulumu
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs && \
    npm install -g yarn

WORKDIR /var/www

COPY . .

RUN composer install --no-interaction --prefer-dist --optimize-autoloader
RUN yarn install && yarn build

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Laravel 12 için optimizasyon komutları
RUN php artisan key:generate --force
RUN php artisan optimize

EXPOSE 9000

CMD ["php-fpm"]
