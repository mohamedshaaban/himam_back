#!/usr/bin/env bash
set -e

# Managed hosts assign the port; default to 8080 for a plain `docker run`.
: "${PORT:=8080}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

if [ -z "${APP_KEY:-}" ]; then
  echo "WARNING: APP_KEY is empty. Generate one with 'php artisan key:generate --show'"
  echo "         and set it as an environment variable, or sessions and encrypted"
  echo "         values will not survive a restart."
fi

# Schema first, then caches — caching config before the database exists would
# bake a half-built container's view of the world into the image's cache files.
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  php artisan migrate --force --no-interaction
fi

# Seed only when explicitly asked, so a redeploy never silently duplicates or
# resets the programme's content.
if [ "${RUN_SEEDERS:-false}" = "true" ]; then
  php artisan db:seed --force --no-interaction
fi

php artisan config:cache
php artisan route:cache
php artisan event:cache

exec apache2-foreground
