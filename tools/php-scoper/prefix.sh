#!/usr/bin/env bash

# Abort on the first failure — a partial run would otherwise ship a half-scoped vendor_prefixed/.
set -euo pipefail

if [ -z "${PLUGIN_DIR:-}" ]; then
  PLUGIN_DIR="./"
fi

# Normalise to exactly one trailing slash so callers can pass either form
PLUGIN_DIR="${PLUGIN_DIR%/}/"

PHP="php"
COMPOSER="composer"
SCOPER_DIR="${PLUGIN_DIR}tools/php-scoper"
SCOPER="${SCOPER_DIR}/vendor/bin/php-scoper"
STAGE="${PLUGIN_DIR}tmp/php-scoper"

# php-scoper is installed outside the plugin's dependency tree so it can require a modern PHP
# while composer.json stays pinned to the 7.4 platform. See tools/php-scoper/composer.json.
if [ ! -f "$SCOPER" ]; then
  eval "$COMPOSER install --no-interaction --working-dir ${SCOPER_DIR}"
fi

# add-prefix wipes its output directory, so each pass gets its own staging tree and the
# results are merged. Nothing touches vendor_prefixed/ until every pass has succeeded.
rm -Rf "$STAGE"
mkdir -p "$STAGE/merged"

# Each pass stages into $STAGE/<pass>/<path it should occupy under vendor_prefixed>
scope() {
  local pass="$1" subdir="$2"
  eval "$PHP $SCOPER add-prefix --output-dir=${STAGE}/${pass}/${subdir} --config=${SCOPER_DIR}/config/${pass}.php -n -vvv"
  cp -R "${STAGE}/${pass}/." "${STAGE}/merged/"
}

scope monolog monolog
scope url-signer ''
scope querypath ''
scope upload gravitypdf/upload
scope mpdf ''

rm -Rf "${PLUGIN_DIR}vendor_prefixed"
mv "${STAGE}/merged" "${PLUGIN_DIR}vendor_prefixed"
touch "${PLUGIN_DIR}vendor_prefixed/.gitkeep"
rm -Rf "$STAGE"

# Strip the originals only once every pass has succeeded, so a failed run is re-runnable
VENDOR_DIRECTORIES=(
  "monolog"
  "spatie"
  "league"
  "masterminds"
  "mpdf"
  "setasign"
  "myclabs"
  "gravitypdf/querypath"
  "gravitypdf/upload"
  "psr"
)

for dir in "${VENDOR_DIRECTORIES[@]}"; do
  eval "rm -Rf ${PLUGIN_DIR}vendor/${dir}"
done

eval "$COMPOSER dump-autoload --optimize --working-dir ${PLUGIN_DIR}"
