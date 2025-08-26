#!/usr/bin/env bash

echo "Enable Permalinks/REST API"
npm run wp-env run cli wp rewrite structure /%postname%/ -- --hard

echo "Activate Plugins"
npm run wp-env run cli wp plugin activate gravityforms gravityformspolls gravityformssurvey gravityformsquiz gravity-pdf
npm run wp-env run tests-cli wp plugin activate gravityforms gravityformspolls gravityformssurvey gravityformsquiz gravity-pdf