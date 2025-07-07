#!/usr/bin/env bash

# Exit on error
set -o errexit

# Run database migrations
echo "Running migrations..."
php artisan migrate --force

# Start PHP-FPM in the background
php-fpm -D

# Start Nginx in the foreground
echo "Starting Nginx..."
nginx -g "daemon off;"