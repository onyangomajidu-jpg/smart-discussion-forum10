# Dockerfile for deploying the Laravel app (in ./laravel) to Render
# Place this file at the ROOT of your repo (same level as README.md)

FROM php:8.2-cli

# --- System dependencies -----------------------------------------------
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libonig-dev libxml2-dev libpq-dev curl \
    && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd zip \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# --- Composer -------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy only the Laravel app (adjust if your repo layout differs)
COPY laravel/ ./

# --- PHP deps ---------------------------------------------------------
RUN composer install --no-dev --optimize-autoloader --no-interaction

# --- Front-end build (Vite/Tailwind) ------------------------------------
RUN npm install && npm run build

# --- Permissions --------------------------------------------------------
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 10000
ENTRYPOINT ["docker-entrypoint.sh"]
