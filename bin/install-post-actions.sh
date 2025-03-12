#!/usr/bin/env bash

# Install add-ons in the test environment
npm run wp-env run tests-cli wp gf install gravityformspolls -- --key=$GF_LICENSE --activate --force
npm run wp-env run tests-cli wp gf install gravityformsquiz -- --key=$GF_LICENSE --activate --force
npm run wp-env run tests-cli wp gf install gravityformssurvey -- --key=$GF_LICENSE --activate --force
