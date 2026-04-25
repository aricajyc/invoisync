FROM serversideup/php:8.4-fpm-nginx

# Switch to root to install dependencies
USER root

# Install missing PHP extensions required by your Laravel packages
RUN install-php-extensions gd zip bcmath

# Install Node.js (required to compile React/Vite assets)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get update \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Switch back to the unprivileged webuser for security
USER www-data

# Copy the entire Laravel application into the container
COPY --chown=www-data:www-data . /var/www/html/

# Install PHP dependencies (Composer)
RUN composer install --optimize-autoloader --no-dev --no-interaction

# Install NPM dependencies and compile React assets
RUN npm install \
    && npm run build \
    && rm -rf node_modules

# Cache Laravel configurations for maximum performance
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache
