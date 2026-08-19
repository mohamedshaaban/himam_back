# Himam API — portable production image.
#
# Works unchanged on any Docker-based host (Render, Koyeb, Fly, Railway). The
# listening port is taken from $PORT at boot because managed hosts assign one
# rather than letting the container pick.

FROM php:8.2-apache

# intl backs locale-aware date formatting; pdo_mysql is the database driver;
# zip is what Composer uses to unpack packages.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev libzip-dev unzip git \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Opcache is off by default in the CLI image and is the single biggest cheap win
# on a 512 MB free instance.
RUN { \
      echo 'opcache.enable=1'; \
      echo 'opcache.memory_consumption=96'; \
      echo 'opcache.max_accelerated_files=10000'; \
      echo 'opcache.validate_timestamps=0'; \
    } > /usr/local/etc/php/conf.d/opcache.ini \
    && { \
      echo 'expose_php=Off'; \
      echo 'memory_limit=256M'; \
      echo 'upload_max_filesize=8M'; \
      echo 'post_max_size=8M'; \
    } > /usr/local/etc/php/conf.d/himam.ini

# Laravel serves from public/, never from the project root — exposing the root
# would publish .env and the whole source tree.
# Containers have an ephemeral filesystem, so file logs vanish with the
# instance and are invisible in a host's log stream. Writing to stderr puts
# exceptions where the platform actually collects them.
ENV LOG_CHANNEL=stderr
ENV LOG_STACK=stderr

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf \
    && a2enmod rewrite headers

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dependencies first, so a source-only change doesn't reinstall the vendor tree.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rw storage bootstrap/cache

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8080
ENTRYPOINT ["docker-entrypoint.sh"]
