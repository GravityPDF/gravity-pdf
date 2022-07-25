#!/usr/bin/env bash

exists() {
  command -v "$1" >/dev/null 2>&1
}

if [ -z "$PLUGIN_DIR" ]; then
  PLUGIN_DIR="./"
fi

if [[ ! -f "${PLUGIN_DIR}php-scoper.phar" ]]; then
  curl -L https://github.com/humbug/php-scoper/releases/download/0.14.1/php-scoper.phar -o  ${PLUGIN_DIR}php-scoper.phar
fi

chmod -R 777 "${PLUGIN_DIR}vendor"

PHP="php"
COMPOSER="composer"

# Monolog
eval "$PHP ${PLUGIN_DIR}php-scoper.phar add-prefix --output-dir=${PLUGIN_DIR}src/Vendor/Monolog --config=${PLUGIN_DIR}.php-scoper/monolog.php --quiet --force"
cp ${PLUGIN_DIR}vendor/monolog/monolog/* ${PLUGIN_DIR}src/Vendor/Monolog 2>/dev/null
eval "rm -Rf ${PLUGIN_DIR}vendor/monolog"

eval "$COMPOSER dump-autoload --working-dir ${PLUGIN_DIR}"
