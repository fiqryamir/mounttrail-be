# ---- Base Stage ----
# Installs PHP and other dependencies
FROM php:8.2-fpm-alpine AS base
WORKDIR /var/www/html
RUN apk add --no-cache \
    nginx \
    git \
    unzip \
    oniguruma-dev \
    mariadb-dev \
    postgresql-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev
RUN docker-php-ext-install -j$(nproc) bcmath exif mbstring pdo pdo_mysql pdo_pgsql zip
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && docker-php-ext-install -j$(nproc) gd
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ---- Builder Stage ----
# Installs application dependencies
FROM base AS builder
COPY . .
RUN composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader

RUN php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"

RUN php artisan config:cache
RUN php artisan route:cache

# ---- Final Stage ----
# This is the final, small production image
FROM base AS final
WORKDIR /var/www/html

COPY --from=builder /var/www/html /var/www/html
COPY nginx.conf /etc/nginx/nginx.conf

# --- ADD THIS LINE ---
# Copy our custom PHP-FPM pool configuration to ensure the socket is created correctly.
COPY zz-www.conf /usr/local/etc/php-fpm.d/zz-my-www.conf

RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
EXPOSE 80
CMD sh -c "php-fpm & exec nginx -g 'daemon off;'"