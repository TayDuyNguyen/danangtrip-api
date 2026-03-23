#!/bin/sh
set -eu

cd /var/www

if [ -f composer.json ]; then
  if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
  elif [ -f composer.lock ] && [ -f vendor/composer/installed.json ] && [ composer.lock -nt vendor/composer/installed.json ]; then
    composer install --no-interaction --prefer-dist
  elif [ -f composer.json ] && [ -f vendor/composer/installed.json ] && [ composer.json -nt vendor/composer/installed.json ]; then
    composer install --no-interaction --prefer-dist
  fi
fi

exec "$@"
