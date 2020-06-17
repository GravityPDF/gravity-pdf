#!/usr/bin/env bash

GF_LICENSE="${GF_LICENSE:=$1}"

# Add new variables / override existing if .env file exists
if [[ -f "wordpress/.env" ]]; then
    set -a
    source wordpress/.env
    set +a
fi

if [[ -f ".env" ]]; then
    set -a
    source .env
    set +a
fi

# Install WordPress
npm run env cli db reset -- --yes --quiet
cd wordpress || exit
npm install dotenv wait-on
npm run env:install
cd ..

# Connect Gravity PDF to WordPress.
rm wordpress/docker-compose.override.yml > /dev/null 2>&1
npm run env connect
npm run env cli plugin activate gravityforms gravity-forms-pdf-extended
npm run env cli option add rg_gforms_key $GF_LICENSE

# Misc
bash ./bin/db.sh

# Output Connection Details
CURRENTURL=$(npm run --silent env cli option get siteurl)

echo "Welcome to..."
echo "_____             _ _          _____ ____  _____  "
echo "|   __|___ ___ _ _|_| |_ _ _   |  _  |    \\|   __|"
echo "|  |  |  _| .'| | | |  _| | |  |   __|  |  |   __| "
echo "|_____|_| |__,|\\_/|_|_| |_  |  |__|  |____/|__|    "
echo ""
echo "Run yarn run build to build the latest version of Gravity PDF, then open $CURRENTURL/wp-login.php to get started."
echo ""
echo "Access the WP install using the following credentials:"
echo "Username: admin"
echo "Password: password"