# Dockerfile

# ---- Base PHP Stage ----
# Use an official PHP 8.2 FPM image. 'alpine' is a lightweight version.
FROM php:8.2-fpm-alpine AS base

# Set working directory
WORKDIR /var/www/html

# Install system dependencies required by Laravel and Composer.
# build-base, autoconf: for compiling extensions from source.
# postgresql-dev, mariadb-dev: for pdo_pgsql and pdo_mysql.
# libzip-dev, zlib-dev: for the zip extension.
# libpng-dev, libjpeg-turbo-dev, freetype-dev: for the gd extension.
# oniguruma-dev: for mbstring.
RUN apk add --no-cache \
    build-base autoconf \
    curl git unzip \
    oniguruma-dev \
    libzip-dev zlib-dev \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    postgresql-dev \
    mariadb-dev

# Install base PHP extensions. The -j$(nproc) flag speeds up compilation.
RUN docker-php-ext-install -j$(nproc) \
    mbstring \
    pdo \
    exif \
    pcntl \
    bcmath \
    sockets

# Install database-specific extensions separately.
RUN docker-php-ext-install -j$(nproc) pdo_mysql pdo_pgsql

# Configure and install the GD extension separately.
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

# Configure and install the zip extension.
RUN docker-php-ext-configure zip \
    && docker-php-ext-install -j$(nproc) zip

# Install Composer globally
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer


# ---- Builder Stage ----
# This stage builds our application dependencies
FROM base AS builder

# Copy only the dependency files to leverage Docker cache
COPY composer.json composer.lock ./
COPY package.json package-lock.json ./
COPY vite.config.js ./
COPY resources/ resources/

# Install Composer dependencies
RUN composer install --no-interaction --no-dev --no-scripts --prefer-dist

# Install Node.js for building assets
RUN apk add --no-cache nodejs npm
RUN npm install

# Copy the rest of the application
COPY . .

# Build frontend assets
RUN npm run build

# Generate Laravel caches
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache

# Cleanup - remove dev files
RUN rm -rf node_modules resources/js resources/css


# ---- Final Nginx Stage ----
# This is the final image that will be run in production
FROM nginx:1.25-alpine

# Set working directory
WORKDIR /var/www/html

# Copy the built application files from the builder stage
COPY --from=builder /var/www/html .

# Copy our custom Nginx configuration
COPY .docker/nginx.conf /etc/nginx/conf.d/default.conf

# Set correct permissions for storage and bootstrap cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 80 for the web server
EXPOSE 80