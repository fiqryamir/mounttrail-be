# Dockerfile

# Use the official Render PHP image as a base
# It includes Nginx and common tools
FROM render/php:8.2-fpm

# Set the working directory
WORKDIR /var/www/html

# Copy your composer and package files first to leverage Docker caching
COPY composer.json composer.lock ./
COPY package.json package-lock.json ./

# Install PHP dependencies
RUN composer install --no-interaction --no-plugins --no-scripts --no-dev --prefer-dist

# Install Node.js dependencies
RUN npm install

# Copy the rest of your application code
COPY . .

# Build your frontend assets
RUN npm run build

# Generate Laravel-specific cache files
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache

# Set ownership for the web server
RUN chown -R www-data:www-data storage bootstrap/cache

# The Nginx configuration is already handled by the render/php base image.
# We just need to tell it to start the deploy script.
# (We will create this deploy.sh script next)
CMD ["/var/www/html/deploy.sh"]
