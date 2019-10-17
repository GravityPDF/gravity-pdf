#!/usr/bin/env bash

GF_LICENSE="${GF_LICENSE:=$1}"

# Add new variables / override existing if .env file exists
if [ -f ".env" ]; then
    set -a
    source .env
    set +a
fi

npm run env cli --quiet plugin install gravityformscli -- --activate --quiet
npm run env cli --quiet gf install -- --key=$GF_LICENSE --activate --force