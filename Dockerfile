# ---- Base Stage ----
# This stage installs PHP, its extensions, and Composer
FROM php:8.2-fpm-alpine AS base
WORKDIR /var/www/html
RUN apk add --no-cache curl git unzip oniguruma-dev mariadb-dev postgresql-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev
RUN docker-php-ext-install -j$(nproc) bcmath exif mbstring pdo pdo_mysql pdo_pgsql zip
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && docker-php-ext-install -j$(nproc) gd
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ---- Builder Stage ----
# This stage installs our application dependencies
FROM base AS builder
COPY composer.json composer.lock ./

# --- FIX IS HERE: Added --no-scripts to prevent running artisan commands before the files exist ---
RUN composer install --no-interaction --no-dev --prefer-dist --no-scripts

# Now, copy the rest of the application files
COPY . .

# Now that the 'artisan' file exists, we can run the scripts and generate caches
RUN composer dump-autoload --optimize
# Note: dump-autoload will trigger the post-autoload-dump scripts like package:discover

# ---- Final Stage ----
# This is the final image that will be run. No Nginx!
FROM base AS final
COPY --from=builder /var/www/html /var/www/html

# Run database migrations and start the server
# The CMD will be executed when the container starts
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}