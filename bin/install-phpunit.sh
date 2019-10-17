#!/usr/bin/env bash

GF_LICENSE="${GF_LICENSE:=$1}"

# Add new variables / override existing if .env file exists
if [ -f ".env" ]; then
    set -a
    source .env
    set +a
fi

npm run env cli --quiet plugin activate gravityformscli -- --quiet
npm run env cli --quiet gf install gravityformspolls -- --key=$GF_LICENSE --activate --force
npm run env cli --quiet gf install gravityformsquiz -- --key=$GF_LICENSE --activate --force
npm run env cli --quiet gf install gravityformssurvey -- --key=$GF_LICENSE --activate --force