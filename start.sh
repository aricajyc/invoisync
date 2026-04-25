#!/bin/sh
set -e

echo "Running database migrations..."
php artisan migrate --force

echo "Clearing and caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Starting server..."
exec /start.sh
