#!/usr/bin/env sh

# Exit on error
set -o errexit

# Run database migrations
echo "Running migrations..."
php artisan migrate --force

# Start PHP-FPM in the background
php-fpm -D

# =================================================================
# --- DIAGNOSTIC BLOCK ---
# We will inspect the Nginx config file before starting Nginx.
# This will show us any hidden characters or wrong line endings.
# =================================================================
echo "--- Starting Nginx config diagnostics ---"
echo "File permissions and size:"
ls -l /etc/nginx/conf.d/default.conf

echo ""
echo "Dumping file content with 'cat -A' (shows hidden chars):"
cat -A /etc/nginx/conf.d/default.conf

echo ""
echo "--- End of diagnostics ---"
# =================================================================

# Start Nginx in the foreground
echo "Starting Nginx..."
nginx -g "daemon off;"