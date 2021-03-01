#!/usr/bin/env bash

echo $0 $1 $2

if [ $# -lt 1 ]; then
	echo "usage: $0 <version> [branch]"
	exit 1
fi

VERSION=$1
BRANCH=${2-development}
TMP_DIR="./tmp/package/"
PACKAGE_DIR="${TMP_DIR}${VERSION}"
WORKING_DIR=$PWD
PACKAGE_NAME="gravity-forms-pdf-extended"

# Create the working directory
mkdir -p ${PACKAGE_DIR}

# Get an archive of our plugin
git archive ${BRANCH} --output ${PACKAGE_DIR}/package.tar.gz
tar -zxf ${PACKAGE_DIR}/package.tar.gz --directory ${PACKAGE_DIR} && rm -f ${PACKAGE_DIR}/package.tar.gz

# Run Composer
yarn --cwd ${PACKAGE_DIR} prebuild
yarn --cwd ${PACKAGE_DIR} build:production
composer install --no-dev  --prefer-dist --optimize-autoloader --working-dir ${PACKAGE_DIR}

BUILD=1 PLUGIN_DIR="$PACKAGE_DIR/" bash ./bin/vendor-prefix.sh

# Cleanup Node JS
rm -f -R ${PACKAGE_DIR}/node_modules

# Cleanup additional build files
FILES=(
"${PACKAGE_DIR}/composer.json"
"${PACKAGE_DIR}/composer.lock"
"${PACKAGE_DIR}/package.json"
"${PACKAGE_DIR}/yarn.lock"
"${PACKAGE_DIR}/gulpfile.js"
"${PACKAGE_DIR}/.babelrc"
"${PACKAGE_DIR}/webpack.config.js"
"${PACKAGE_DIR}/php-scoper.phar"
)

for i in "${FILES[@]}"
do
    rm -f ${i}
done

rm -f -R "${PACKAGE_DIR}/src/assets/css"
rm -f -R "${PACKAGE_DIR}/src/assets/js"
rm -f -R "${PACKAGE_DIR}/bin"
rm -f -R "${PACKAGE_DIR}/.php-scoper"
rm -f -R "${PACKAGE_DIR}/webpack-configs"
rm -R "${PACKAGE_DIR}/vendor/mpdf/mpdf/ttfonts"
