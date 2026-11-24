#!/usr/bin/env bash

GF_LICENSE="${GF_LICENSE:=$1}"

# Add new variables / override existing if .env file exists
if [ -f ".env" ]; then
    set -a
    source .env
    set +a
fi

# Gravity Forms core is pinned, since `wp gf install` only offers the latest release
GF_VERSION="3.2.3.1"
GF_ZIP="tmp/gravityforms-$GF_VERSION.zip"

mkdir -p tmp
curl -sSfL --retry 3 -u "$GF_LICENSE:http://localhost" -o "$GF_ZIP" "https://composer.gravity.io/downloads/?plugin=gravityforms&version=$GF_VERSION" || exit 1

# Install in both development + test environments
npm run wp-env run cli wp plugin install /var/www/html/wp-content/plugins/gravity-pdf/$GF_ZIP -- -- --activate --force
npm run wp-env run tests-cli wp plugin install /var/www/html/wp-content/plugins/gravity-pdf/$GF_ZIP -- -- --activate --force
rm -f "$GF_ZIP"

# Install add-ons in the test environment
npm run wp-env run tests-cli wp gf install gravityformspolls -- --key=$GF_LICENSE --activate --force
npm run wp-env run tests-cli wp gf install gravityformsquiz -- --key=$GF_LICENSE --activate --force
npm run wp-env run tests-cli wp gf install gravityformssurvey -- --key=$GF_LICENSE --activate --force
