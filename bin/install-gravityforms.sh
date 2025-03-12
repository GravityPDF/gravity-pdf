#!/usr/bin/env bash

# Add new variables / override existing if .env file exists
if [ -f ".env" ]; then
    set -a
    source .env
    set +a
fi

# Install in both development + test environments
# --version=beta is not currently supported by add-ons.
npm run wp-env run cli wp gf install -- -- --key=$GF_LICENSE --activate --force --version=latest
npm run wp-env run tests-cli wp gf install -- -- --key=$GF_LICENSE --activate --force --version=latest