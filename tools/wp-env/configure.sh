#!/usr/bin/env bash

echo "Enable Permalinks/REST API"
npm run wp-env run cli wp rewrite structure /%postname%/ -- --hard

echo "Activate Plugins"
npm run wp-env run cli wp plugin activate gravityforms gravityformspolls gravityformssurvey gravityformsquiz gravity-pdf
npm run wp-env run tests-cli wp plugin activate gravityforms gravityformspolls gravityformssurvey gravityformsquiz gravity-pdf

echo "Setup content on test site"
npm run wp-env run tests-cli wp option add freshinstall yes
npm run wp-env run tests-cli wp user create editor editor@test.com -- --role=editor --user_pass=password --quiet