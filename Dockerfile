# ---- Base Stage ----
# Installs PHP, its extensions, and Composer
FROM php:8.2-fpm-alpine AS base
WORKDIR /var/www/html
# Added tokenizer extension for full compatibility
RUN apk add --no-cache \
    nginx \
    curl \
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
# This stage installs our application dependencies and prepares assets
FROM base AS builder
COPY . .
RUN composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader
# Publish Swagger assets so the Nginx server can find them
RUN php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"

# --- CRITICAL FIX ---
# We DO NOT run config:cache or route:cache here.
# This ensures the application reads the live environment variables from Render at runtime.

# ---- Final Stage ----
# This is the final image that will be run with a proper production server.
FROM base AS final
WORKDIR /var/www/html
COPY --from=builder /var/www/html /var/www/html
COPY nginx.conf /etc/nginx/nginx.conf
COPY zz-www.conf /usr/local/etc/php-fpm.d/zz-my-www.conf

# Set correct permissions so the web server can write to logs/cache
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80
# Start the production services
CMD sh -c "php-fpm & exec nginx -g 'daemon off;'"