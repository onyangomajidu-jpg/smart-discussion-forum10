#!/bin/sh
set -e

# Cache config/routes/views for speed (safe to fail on first boot before APP_KEY exists)
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Create the storage symlink (idempotent)
php artisan storage:link || true

# Run migrations on every boot so schema stays in sync with the deployed code
php artisan migrate --force

# Render sets $PORT; fall back to 10000 for local docker runs
PORT="${PORT:-10000}"
exec php artisan serve --host=0.0.0.0 --port="$PORT"
