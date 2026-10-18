#!/bin/sh
set -e

cd /var/www/html

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is not set. Add it to the .env file next to docker-compose.yml" >&2
    echo "(generate one with: docker compose run --rm --no-deps --entrypoint php backend artisan key:generate --show)" >&2
    exit 1
fi

# The storage volume may be empty on first run
mkdir -p storage/app/public storage/app/private storage/logs \
    storage/framework/cache/data storage/framework/sessions \
    storage/framework/views storage/framework/testing bootstrap/cache

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi

# Passport signing keys live in the storage volume unless supplied via env
if [ -z "$PASSPORT_PRIVATE_KEY" ] && [ ! -f storage/oauth-private.key ]; then
    php artisan passport:keys
fi

php docker/ensure-passport-client.php

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
