#!/usr/bin/env sh

# Exit on error
set -o errexit

# Wait for the database to be ready (optional but good practice)
# You might need to add logic here to wait for your database service on Render

# Run database migrations
echo "Running migrations..."
php artisan migrate --force

# Start PHP-FPM in the background
php-fpm -D

# Start Nginx in the foreground
echo "Starting Nginx..."
nginx -g "daemon off;"