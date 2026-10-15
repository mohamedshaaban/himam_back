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

  echo "============================================================"
  echo "WARNING: running on SQLite inside the container."
  echo ""
  echo "  The container filesystem is ephemeral. EVERY DEPLOY AND"
  echo "  RESTART DELETES THE DATABASE: accounts readers registered,"
  echo "  content entered in the dashboard, uploaded files and every"
  echo "  issued API token. The seeded demo data reappears because it"
  echo "  is re-created, not because it survived."
  echo ""
  echo "  Attach a real database and set DB_CONNECTION to pgsql or"
  echo "  mysql with the matching DB_* variables. render.yaml"
  echo "  provisions one and wires it up automatically."
  echo "============================================================"
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

# Uploaded images are served from storage/app/public via this symlink. It lives
# on the container filesystem, so on an ephemeral host uploads vanish with the
# instance — set MEDIA_DISK=s3 with the AWS_* variables to keep them.
# A one-off data migration, and a decision rather than a deploy step: it moves
# every book's exam from its sections onto the book itself. It runs only when
# MOVE_EXAM_TO_BOOK is set, because no deploy should quietly rewrite how the
# catalogue is examined.
#
# The command is idempotent — a book already examined as a whole is skipped, and
# so is one with no questions to move — so leaving the variable set costs a few
# queries per deploy and changes nothing. Remove it once it has run.
#
# A failure here must not stop the application booting: the old behaviour is
# still correct, it is simply not what was asked for.
if [ "${MOVE_EXAM_TO_BOOK:-false}" = "true" ]; then
  echo "MOVE_EXAM_TO_BOOK is set — moving each book's exam onto the book."
  php artisan himam:exam-on-book --no-interaction     || echo "WARNING: himam:exam-on-book failed. The catalogue is unchanged."
fi

php artisan storage:link --force || true

# Sections point at the placeholder document by path, and on an ephemeral
# filesystem that path outlives the file. Re-publishing it costs a 3 KB copy and
# stops those sections pointing at nothing after a deploy. Real uploads need
# object storage; this only covers the file that ships with the repository.
php artisan himam:publish-sample-pdf || true

php artisan config:cache
php artisan route:cache
php artisan event:cache

# Seeding happens after the port is open, not before it.
#
# Against a remote database the catalogue takes minutes to insert — one network
# round trip per row — and every second of that used to pass before Apache
# bound its port. Managed hosts watch for that port and give up waiting: the
# first TiDB deploy spent seven minutes logging "no open ports detected" and
# came within moments of being declared a failed deploy.
#
# So the order is: schema first, because serving requests against a database
# with no tables is a guaranteed 500; then the port; then the content. A reader
# arriving in that window sees an empty catalogue rather than an error, and only
# on a database that was empty to begin with.
#
# `seed-if-empty` checks for existing content, so this can never duplicate the
# programme on a redeploy.
seed_in_background() {
  php artisan himam:seed-if-empty --no-interaction

  # Deliberate resets only: this WILL duplicate content if the catalogue is
  # already populated.
  if [ "${RUN_SEEDERS:-false}" = "true" ]; then
    php artisan db:seed --force --no-interaction
  fi
}

if [ "${RUN_SEEDERS_IN_BACKGROUND:-true}" = "true" ]; then
  seed_in_background &
else
  # For a one-shot job, or any host that would rather wait than serve an
  # empty catalogue.
  seed_in_background
fi

exec apache2-foreground
