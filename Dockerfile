# ---- Base Stage ----
# This stage installs PHP, its extensions, and Composer
FROM php:8.2-fpm-alpine AS base
WORKDIR /var/www/html
RUN apk add --no-cache curl git unzip libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev
RUN docker-php-ext-install -j$(nproc) bcmath exif mbstring pdo pdo_mysql zip
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && docker-php-ext-install -j$(nproc) gd
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ---- Builder Stage ----
# This stage installs our application dependencies
FROM base AS builder
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-dev --prefer-dist
COPY . .
# We don't need to cache routes/views for artisan serve
RUN composer dump-autoload --optimize

# ---- Final Stage ----
# This is the final image that will be run. No Nginx!
FROM base AS final
COPY --from=builder /var/www/html /var/www/html

# Run database migrations and start the server
# The CMD will be executed when the container starts
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}