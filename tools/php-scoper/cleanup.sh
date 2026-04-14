#!/usr/bin/env bash

if [ -z "$PLUGIN_DIR" ]; then
  PLUGIN_DIR=""
fi

COMPOSER="composer"

# Temporarily remove post-install script to avoid re-execution
COMPOSER_JSON="${PLUGIN_DIR}/composer.json"
jq 'del(.scripts["post-install-cmd"])' "${COMPOSER_JSON}" > "${COMPOSER_JSON}.tmp" && mv "${COMPOSER_JSON}.tmp" "${COMPOSER_JSON}"

# Reinstall with only production dependencies without triggering post-install script
rm -r -f ${PLUGIN_DIR}/vendor
composer install --no-dev --prefer-dist --working-dir ${PLUGIN_DIR}

# Restore post-install script
jq '.scripts["post-install-cmd"] = "bash ./bin/vendor-prefix.sh"' "${COMPOSER_JSON}" > "${COMPOSER_JSON}.tmp" && mv "${COMPOSER_JSON}.tmp" "${COMPOSER_JSON}"

VENDOR_DIRECTORIES=(
  "monolog"
  "spatie"
  "league"
  "masterminds"
  "mpdf"
  "setasign"
  "myclabs"
  "gravitypdf"
)

for dir in "${VENDOR_DIRECTORIES[@]}";
do
  rm -r -f "${PLUGIN_DIR}/vendor/${dir}"
done

eval "$COMPOSER dump-autoload --optimize --working-dir ${PLUGIN_DIR}"
