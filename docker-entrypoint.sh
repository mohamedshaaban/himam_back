#!/usr/bin/env bash
set -e

# Managed hosts assign the port; default to 8080 for a plain `docker run`.
: "${PORT:=8080}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# SQLite keeps its database on the container filesystem. Two things bite here:
# this script runs as root while Apache serves as www-data, and SQLite needs to
# write a journal *next to* the file — so the directory must be writable too,
# not just the database. Without this, boot-time migrations succeed as root and
# every request-time INSERT then fails, which looks like a 500 on login only.
DB_CONNECTION="${DB_CONNECTION:-sqlite}"
if [ "$DB_CONNECTION" = "sqlite" ]; then
  DB_PATH="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
  mkdir -p "$(dirname "$DB_PATH")"
  touch "$DB_PATH"
  chown -R www-data:www-data "$(dirname "$DB_PATH")"
  chmod 775 "$(dirname "$DB_PATH")"
  chmod 664 "$DB_PATH"

  echo "NOTE: running on SQLite inside the container. The filesystem is"
  echo "      ephemeral, so all data is lost on every deploy and restart."
  echo "      Set DB_CONNECTION=mysql and the DB_* variables to keep it."
fi

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

# Seed the catalogue when the database is empty. This is idempotent — the
# command checks for existing content first — so a redeploy can never duplicate
# the programme. Free tiers usually have no shell, so boot is the only reliable
# place to do this.
php artisan himam:seed-if-empty --no-interaction

# Force a full re-seed. Deliberate resets only: this WILL duplicate content if
# the catalogue is already populated.
if [ "${RUN_SEEDERS:-false}" = "true" ]; then
  php artisan db:seed --force --no-interaction
fi

# Uploaded images are served from storage/app/public via this symlink. It lives
# on the container filesystem, so on an ephemeral host uploads vanish with the
# instance — point FILESYSTEM_DISK at S3/R2 to keep them.
php artisan storage:link --force || true

php artisan config:cache
php artisan route:cache
php artisan event:cache

exec apache2-foreground
