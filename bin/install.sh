#!/usr/bin/env bash

GF_LICENSE="${GF_LICENSE:=$1}"

# Add new variables / override existing if .env file exists
if [[ -f ".env" ]]; then
    set -a
    source .env
    set +a
fi

# Install Gravity PDF Dependencies
rm composer.lock
composer install
composer run prefix

# Start local environment
npm run wp-env start -- --xdebug

# Place CLI config file
npm run wp-env run tests-wordpress cp /var/www/html/wp-content/plugins/gravity-pdf/bin/htaccess-sample /var/www/html/.htaccess

# Fix permissions issues on test container
npm run wp-env run wordpress chmod 777 /var/www/html/wp-content/{plugins,themes,}
npm run wp-env run tests-wordpress chmod 777 /var/www/html/wp-content/{plugins,themes,}
npm run wp-env run tests-wordpress chmod 777 /var/www/html/ /var/www/html/wp-content/plugins/gravity-pdf /var/www/html/wp-content/plugins/gravity-pdf-test-suite/src/fonts/ /var/www/html/wp-content/uploads/

echo "Install Gravity Forms..."
bash ./bin/install-gravityforms.sh

npm run wp-env run cli plugin activate gravityforms gravityformscli gravity-pdf
npm run wp-env run tests-cli plugin activate gravityforms gravityformscli gravityformspolls gravityformssurvey gravityformsquiz gravity-pdf gravity-pdf-test-suite

echo "Run Database changes"
bash ./bin/install-database.sh

if [[ -f "./bin/install-post-actions.sh" ]]; then
  echo "Running Post Install Actions..."
  bash ./bin/install-post-actions.sh
fi