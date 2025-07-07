# ---- Builder Stage ----
# This stage builds application dependencies.
FROM base AS builder

# 1. Copy composer files and install PHP dependencies
# This layer is cached as long as composer.json/lock don't change
COPY composer.json composer.lock ./
# We install --no-scripts here and run them after the files are copied.
RUN composer install --no-interaction --no-dev --no-scripts --prefer-dist

# 2. Copy the rest of the application files
COPY . .

# 3. Now that all files are present, run composer scripts and generate caches
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
RUN chown -R www-data:www-data /var/w ww/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

# Run the entrypoint script
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]