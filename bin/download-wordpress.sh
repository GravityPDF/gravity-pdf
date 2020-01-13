#!/usr/bin/env bash

# Download and unpack WordPress.
mkdir tmp > /dev/null 2>&1
rm -Rf wordpress && rm -Rf tmp/wordpress-develop-master > /dev/null 2>&1
curl -L https://wordpress.org/latest.zip -o ./tmp/latest.zip
unzip -q ./tmp/latest.zip -d ./tmp
mkdir -p wordpress/src
mv ./tmp/wordpress/* wordpress/src

# Create the upload/wp-config.php directory with permissions that Travis can handle.
mkdir -p wordpress/src/wp-content/uploads
chmod -R 767 wordpress

# Grab the tools we need for WordPress' local-env.
curl -L https://github.com/WordPress/wordpress-develop/archive/master.zip -o ./tmp/wordpress-develop.zip
unzip -q ./tmp/wordpress-develop.zip -d ./tmp
mv \
./tmp/wordpress-develop-master/tools \
./tmp/wordpress-develop-master/tests \
./tmp/wordpress-develop-master/.env \
./tmp/wordpress-develop-master/docker-compose.yml \
./tmp/wordpress-develop-master/wp-cli.yml \
./tmp/wordpress-develop-master/*config-sample.php \
./tmp/wordpress-develop-master/package.json wordpress
