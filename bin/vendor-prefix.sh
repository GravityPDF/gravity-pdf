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

if [ -z "$PLUGIN_DIR" ]; then
  PLUGIN_DIR="./"
fi

if [[ ! -f "${PLUGIN_DIR}php-scoper.phar" ]]; then
  curl -L https://github.com/humbug/php-scoper/releases/download/0.13.2/php-scoper.phar -o  ${PLUGIN_DIR}php-scoper.phar
fi

# Monolog
if exists sudo; then
  sudo chmod -R 777 vendor
else
  chmod -R 777 vendor
fi

if exists php && ! isTravis; then
  PHP_DOCKER=""
  PHP="php"
  COMPOSER="composer"
else
  PHP_DOCKER="yarn env docker-run php "
  PHP="LOCAL_PHP=7.4-fpm ${PHP_DOCKER}php"
  COMPOSER="${PHP_DOCKER}composer"
fi

rm -R "${PLUGIN_DIR}vendor_prefixed"
mkdir "${PLUGIN_DIR}vendor_prefixed"

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

eval "$COMPOSER dump-autoload --working-dir ${PLUGIN_DIR}"