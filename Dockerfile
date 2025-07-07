# Dockerfile

# ---- Base PHP Stage ----
# Use an official PHP 8.2 FPM image. 'alpine' is a lightweight version.
FROM php:8.2-fpm-alpine AS base

# Set working directory
WORKDIR /var/www/html

# Install system dependencies required by Laravel and Composer.
# This is the line we are fixing by adding more dev packages.
RUN apk add --no-cache \
    curl \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev

# Install required PHP extensions
# This command can now run successfully because the dependencies above are installed.
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    gd \
    zip \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    exif \
    pcntl \
    bcmath \
    sockets

# Install Composer globally
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer


# ---- Builder Stage ----
# This stage builds our dependencies
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
# This is the final image that will be run
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

# The CMD is handled by the `deploy.sh` script, which will start Nginx and PHP-FPM
# (This part is handled by your deploy.sh script, we don't need a CMD here)