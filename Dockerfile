# Stage 1: Build Frontend Assets (Vite)
FROM node:20-alpine AS node_builder
WORKDIR /app
COPY package*.json vite.config.js ./
RUN npm install
COPY resources resources/
COPY public public/
RUN npm run build

# Stage 2: PHP & Web Server
FROM php:8.2-cli-alpine

# Install ekstensi PostgreSQL dan dependensi
RUN apk add --no-cache libpq-dev libzip-dev zip unzip \
    && docker-php-ext-install pdo pdo_pgsql zip bcmath

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Salin source code proyek
COPY . .
COPY --from=node_builder /app/public/build public/build

# Install dependensi PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permission storage
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8080

# Jalankan server dan migrasi
CMD sh -c "php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8080"