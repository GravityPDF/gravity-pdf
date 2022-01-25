#!/usr/bin/env bash

exists() {
  command -v "$1" >/dev/null 2>&1
}

if [ -z "$PLUGIN_DIR" ]; then
  PLUGIN_DIR="./"
fi

if [[ ! -f "${PLUGIN_DIR}php-scoper.phar" ]]; then
  curl -L https://github.com/humbug/php-scoper/releases/download/0.14.0/php-scoper.phar -o  ${PLUGIN_DIR}php-scoper.phar
fi

if exists sudo; then
  sudo chmod -R 777 "${PLUGIN_DIR}vendor"
else
  chmod -R 777 "${PLUGIN_DIR}vendor"
fi

PHP_DOCKER=""
PHP="php"
COMPOSER="composer"

rm -R "${PLUGIN_DIR}vendor_prefixed"
mkdir "${PLUGIN_DIR}vendor_prefixed"
touch "${PLUGIN_DIR}vendor_prefixed/.gitkeep"

# Monolog
eval "$PHP ${PLUGIN_DIR}php-scoper.phar add-prefix --output-dir=${PLUGIN_DIR}vendor_prefixed/monolog --config=${PLUGIN_DIR}.php-scoper/monolog.php --quiet"
eval "${PHP_DOCKER}rm -Rf ${PLUGIN_DIR}vendor/monolog"

# URL Signer
eval "$PHP ${PLUGIN_DIR}php-scoper.phar add-prefix --output-dir=${PLUGIN_DIR}vendor_prefixed --config=${PLUGIN_DIR}.php-scoper/url-signer.php --quiet"
eval "${PHP_DOCKER}rm -Rf ${PLUGIN_DIR}vendor/spatie"
eval "${PHP_DOCKER}rm -Rf ${PLUGIN_DIR}vendor/league"

# Querypath
eval "$PHP ${PLUGIN_DIR}php-scoper.phar add-prefix --output-dir=${PLUGIN_DIR}vendor_prefixed --config=${PLUGIN_DIR}.php-scoper/querypath.php --quiet"
eval "${PHP_DOCKER}rm -Rf ${PLUGIN_DIR}vendor/querypath"
eval "${PHP_DOCKER}rm -Rf ${PLUGIN_DIR}vendor/masterminds"

# Codeguy
eval "$PHP ${PLUGIN_DIR}php-scoper.phar add-prefix --output-dir=${PLUGIN_DIR}vendor_prefixed/upload --config=${PLUGIN_DIR}.php-scoper/upload.php --quiet"
eval "${PHP_DOCKER}rm -Rf ${PLUGIN_DIR}vendor/codeguy"

# Mpdf
eval "$PHP ${PLUGIN_DIR}php-scoper.phar add-prefix --output-dir=${PLUGIN_DIR}vendor_prefixed --config=${PLUGIN_DIR}.php-scoper/mpdf.php" --quiet
eval "${PHP_DOCKER}rm -Rf ${PLUGIN_DIR}vendor/mpdf"
eval "${PHP_DOCKER}rm -Rf ${PLUGIN_DIR}vendor/setasign"
eval "${PHP_DOCKER}rm -Rf ${PLUGIN_DIR}vendor/myclabs"

eval "$COMPOSER dump-autoload --optimize --working-dir ${PLUGIN_DIR}"
