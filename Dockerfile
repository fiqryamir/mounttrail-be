# ---- Base Stage ----
# Contains PHP, its extensions, and Composer.
FROM php:8.2-fpm-alpine AS base

WORKDIR /var/www/html

# Install system dependencies.
RUN apk add --no-cache \
    build-base autoconf \
    curl git unzip \
    linux-headers \
    oniguruma-dev \
    libzip-dev zlib-dev \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    postgresql-dev \
    mariadb-dev

# Install PHP extensions
RUN docker-php-ext-install -j$(nproc) \
    bcmath \
    exif \
    mbstring \
    pcntl \
    pdo pdo_mysql pdo_pgsql \
    sockets \
    zip

# Configure and install GD
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer


# ---- Builder Stage ----
# This stage builds application dependencies for an API-only Laravel app.
FROM base AS builder

# 1. Copy composer files and install PHP dependencies
COPY composer.json composer.lock ./
# We install --no-scripts here and run them after the full source code is copied.
RUN composer install --no-interaction --no-dev --no-scripts --prefer-dist

# 2. Copy the rest of the application files
COPY . .

# 3. Now that all files are present, run composer scripts and generate Laravel caches
RUN composer dump-autoload --no-scripts --optimize && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache


# ---- Final Stage ----
# This is the final, optimized image that will be run.
FROM base AS final

# Install Nginx for the final stage
RUN apk add --no-cache nginx

# Remove development packages to keep the image small
RUN apk del build-base autoconf

# Copy built application from the builder stage
COPY --from=builder /var/www/html /var/www/html

# Copy our custom Nginx configuration and entrypoint script
COPY .docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY .docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

# Run the entrypoint script
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]