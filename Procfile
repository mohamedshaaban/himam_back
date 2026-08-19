# For buildpack-style hosts (Koyeb native PHP, Heroku-likes). Docker-based
# hosts use the Dockerfile instead and ignore this file.
web: vendor/bin/heroku-php-apache2 public/
release: php artisan migrate --force
