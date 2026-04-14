#!/usr/bin/env bash

echo $0 $1

if [ $# -lt 1 ]; then
	echo "usage: $0 <version>"
	exit 1
fi

VERSION=$1
TMP_DIR="./tmp/package/"
PACKAGE_DIR="${TMP_DIR}${VERSION}"
WORKING_DIR=$PWD
PACKAGE_NAME="gravity-pdf"

# Ensure a fresh build
rm -f -r "${PACKAGE_DIR}"

# Create the working directory
mkdir -p ${PACKAGE_DIR}

# Get an archive of our plugin
git archive HEAD ${BRANCH} --output ${PACKAGE_DIR}/package.tar.gz
tar -zxf ${PACKAGE_DIR}/package.tar.gz --directory ${PACKAGE_DIR} && rm -f ${PACKAGE_DIR}/package.tar.gz

# Run Build
yarn install --frozen-lockfile --cwd ${PACKAGE_DIR}
yarn i10n
yarn --cwd ${PACKAGE_DIR} build

# Install all dependencies (including dev)
# Prefix will run as post-install command script - Requires php-scoper from dev dependencies
composer install --prefer-dist --working-dir ${PACKAGE_DIR}

# Run vendor cleanup - Ensures that there's no dev dependencies on production
PLUGIN_DIR="$PACKAGE_DIR" bash ./tools/php-scoper/cleanup.sh

# Cleanup Node JS
rm -f -R ${PACKAGE_DIR}/node_modules

# Cleanup additional build files
FILES=(
"composer.json"
"composer.lock"
"package.json"
"yarn.lock"
".gitignore"
".stylelintrc.json"
"webpack.config.js"
".nvmrc"
".env.example"
"tsconfig.json"
".browserslistrc"
".eslintignore"
".eslintrc.js"
".gitattributes"
"babel.config.js"
"jest.config.js"
)

echo "$PWD"
for i in "${FILES[@]}"
do
    rm -f "${PACKAGE_DIR}/${i}"
done

rm -f -R "${PACKAGE_DIR}/tmp"
rm -f -R "${PACKAGE_DIR}/tools"
rm -f -R "${PACKAGE_DIR}/.claude"

rm -r -f "${PACKAGE_NAME}"
mv ${VERSION} "${PACKAGE_NAME}"
zip -r -q "${PACKAGE_NAME}-${VERSION}.zip" "${PACKAGE_NAME}"
mv "${PACKAGE_NAME}" ${VERSION}
