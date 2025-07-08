# ---- Base Stage ----
# Installs PHP, extensions, and Composer
FROM php:8.2-fpm-alpine AS base
WORKDIR /var/www/html

# Install system dependencies for PHP extensions and Nginx
RUN apk add --no-cache nginx curl git unzip oniguruma-dev mariadb-dev postgresql-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev

# Install PHP extensions
RUN docker-php-ext-install -j$(nproc) bcmath exif mbstring pdo pdo_mysql pdo_pgsql zip
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && docker-php-ext-install -j$(nproc) gd

# Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer


# ---- Builder Stage ----
# Installs application dependencies
FROM base AS builder
COPY composer.json composer.lock ./

# Install dependencies without running scripts yet
RUN composer install --no-interaction --no-dev --prefer-dist --no-scripts

# Copy the entire application
COPY . .

# Now run composer scripts and create caches
RUN composer dump-autoload --optimize
RUN php artisan optimize:clear
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache


# ---- Final Stage ----
# This is the final, small production image
FROM base AS final
WORKDIR /var/www/html

# Copy the vendor and optimized application files from the builder stage
COPY --from=builder /var/www/html /var/www/html

# Copy Nginx configuration
# You will need to create this file: nginx.conf
COPY nginx.conf /etc/nginx/nginx.conf

# Set correct permissions for storage and cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 80 for Nginx
EXPOSE 80

# The CMD will be executed when the container starts
# This starts PHP-FPM and Nginx
CMD sh -c "php-fpm & exec nginx -g 'daemon off;'"