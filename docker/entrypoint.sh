#!/bin/sh
set -eu

cd /var/www

PORT="${PORT:-10000}"

if [ -f composer.json ]; then
  if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
  elif [ -f composer.lock ] && [ -f vendor/composer/installed.json ] && [ composer.lock -nt vendor/composer/installed.json ]; then
    composer install --no-interaction --prefer-dist
  elif [ -f composer.json ] && [ -f vendor/composer/installed.json ] && [ composer.json -nt vendor/composer/installed.json ]; then
    composer install --no-interaction --prefer-dist
  fi
fi

if [ -f /etc/nginx/templates/default.conf.template ]; then
  envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf
fi

mkdir -p /run/php
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

exec "$@"
