#!/usr/bin/env bash

GF_LICENSE="${GF_LICENSE:=$1}"

# Add new variables / override existing if .env file exists
if [ -f ".env" ]; then
    set -a
    source .env
    set +a
fi

# Install in both development + test environments
npm run wp-env run cli --quiet gf install -- --key=$GF_LICENSE --activate --force
npm run wp-env run tests-cli --quiet gf install -- --key=$GF_LICENSE --activate --force

# Install add-ons in the test environment
npm run wp-env run tests-cli --quiet gf install gravityformspolls -- --key=$GF_LICENSE --activate --force
npm run wp-env run tests-cli --quiet gf install gravityformsquiz -- --key=$GF_LICENSE --activate --force
npm run wp-env run tests-cli --quiet gf install gravityformssurvey -- --key=$GF_LICENSE --activate --force