#!/bin/sh
set -e

echo "Running database migrations..."
php artisan migrate --force

echo "Seeding Admin User..."
php artisan db:seed --class=AdminUserSeeder --force

echo "Clearing and caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Starting server..."
exec /init
