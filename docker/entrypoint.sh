#!/bin/sh
set -e

# Optional: install composer dependencies if vendor empty (dev convenience)
if [ ! -d vendor ]; then
  echo "[entrypoint] Installing composer dependencies..."
  composer install --no-interaction --prefer-dist
fi

# Ensure key exists
php artisan key:generate --quiet || true

# Run migrations if wanted
if [ "$RUN_MIGRATIONS" = "true" ]; then
  echo "[entrypoint] Running migrations..."
  php artisan migrate --force
fi

# Cache (only in production)
if [ "$APP_ENV" = "production" ]; then
  php artisan config:cache || true
  php artisan route:cache || true
  php artisan view:cache || true
fi

exec "$@"
