#!/usr/bin/env bash

exists() {
  command -v "$1" >/dev/null 2>&1
}

isTravis() {
  if [ "$TRAVIS" = "true" ]; then
    return 0
  fi

  return 1
}

if [[ ! -f "php-scoper.phar" ]]; then
  curl -L https://github.com/humbug/php-scoper/releases/download/0.13.2/php-scoper.phar -o php-scoper.phar
fi

# Monolog
if exists sudo; then
  sudo chmod -R 777 vendor
else
  chmod -R 777 vendor
fi

if exists php && ! isTravis; then
  PHP="php"
  COMPOSER="composer"
else
  PHP="LOCAL_PHP=7.4-fpm yarn env docker-run php php"
  COMPOSER="yarn env docker-run php composer"
fi

eval "$PHP ./php-scoper.phar add-prefix --output-dir=./src/Vendor/Monolog --config=.php-scoper/monolog.php --force --quiet"
cp ./vendor/monolog/monolog/* ./src/Vendor/Monolog 2>/dev/null
rm -R ./vendor/monolog

eval "$COMPOSER dump-autoload"