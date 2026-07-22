#!/bin/sh
set -e

# Make sure Laravel's writable directories genuinely exist every time the
# container boots (not just at image build time) — Blade, sessions, and the
# cache framework all fail hard if these are missing or unwritable.
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Clear any stale cached config from a previous boot before doing anything else
php artisan config:clear || true

# Create the storage symlink (idempotent — fine if it already exists)
php artisan storage:link || true

# Run migrations on every boot so schema stays in sync with the deployed code
php artisan migrate --force

# NOTE: intentionally NOT running config:cache / route:cache / view:cache.
# These are optional performance optimizations; skipping them avoids an
# entire class of "works at build time, breaks at runtime" path bugs and
# costs almost nothing on an app this size.

# Render sets $PORT; fall back to 10000 for local docker runs
PORT="${PORT:-10000}"
exec php artisan serve --host=0.0.0.0 --port="$PORT"
