#!/usr/bin/env bash

# Exit on error
set -e

# Run database migrations
echo "Running migrations..."
php artisan migrate --force

# Start the PHP-FPM and Nginx services
# This is managed by the base image's entrypoint script
/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf