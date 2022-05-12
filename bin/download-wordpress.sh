#!/usr/bin/env bash

mkdir tmp > /dev/null 2>&1

# Download and unpack WordPress.
#curl -L http://api.wordpress.org/core/version-check/1.7/ -o ./tmp/wp-latest.json
#grep '[0-9]+\.[0-9]+(\.[0-9]+)?' ./tmp/wp-latest.json
#LATEST_VERSION=$(grep -o '"version":"[^"]*' ./tmp/wp-latest.json | sed 's/"version":"//')
LATEST_VERSION="6.0-RC2"

rm -Rf wordpress && rm -Rf tmp/wordpress-develop-$LATEST_VERSION > /dev/null 2>&1
curl -L "https://wordpress.org/wordpress-$LATEST_VERSION.zip" -o ./tmp/latest.zip
unzip -q ./tmp/latest.zip -d ./tmp
mkdir -p wordpress/src
mv ./tmp/wordpress/* wordpress/src

# Create the upload directory
mkdir -p wordpress/src/wp-content/uploads

# Grab the tools we need for WordPress' local-env.

curl -L "https://github.com/WordPress/wordpress-develop/archive/refs/heads/6.0.zip" -o ./tmp/wordpress-develop.zip
unzip -q ./tmp/wordpress-develop.zip -d ./tmp
mv \
./tmp/wordpress-develop-6.0/tools \
./tmp/wordpress-develop-6.0/tests \
./tmp/wordpress-develop-6.0/.env \
./tmp/wordpress-develop-6.0/docker-compose.yml \
./tmp/wordpress-develop-6.0/wp-cli.yml \
./tmp/wordpress-develop-6.0/*config-sample.php \
./tmp/wordpress-develop-6.0/package.json \
./tmp/wordpress-develop-6.0/package-lock.json wordpress