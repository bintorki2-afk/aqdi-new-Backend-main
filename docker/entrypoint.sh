#!/usr/bin/env bash
set -e

cd /var/www/html

# Ensure writable dirs (bind-mounted volumes may reset ownership)
chown -R www-data:www-data storage bootstrap/cache || true

# Storage symlink (ignore if it already exists)
php artisan storage:link || true

# Run migrations on boot (safe with --force). Comment out if you migrate manually.
php artisan migrate --force || true

# Cache config/routes/views for production performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
