#!/usr/bin/env bash

# Fail fast so a broken step can't still zip a partial plugin. No -u: BRANCH is intentionally unset below.
set -eo pipefail

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

# Run Composer
yarn install --cwd ${PACKAGE_DIR}
yarn --cwd ${PACKAGE_DIR} build:production
composer install --no-dev  --prefer-dist --optimize-autoloader --working-dir ${PACKAGE_DIR}

PLUGIN_DIR="$PACKAGE_DIR/" bash ./bin/vendor-prefix.sh

# Allowlist the top level, so new tooling or config can't leak into the zip.
source "$(dirname "$0")/../tools/release/package-files.sh"
shopt -s dotglob nullglob
for path in "${PACKAGE_DIR}"/*; do
	[[ " ${PACKAGE_FILES[*]} " == *" $(basename "${path}") "* ]] || rm -Rf "${path}"
done
shopt -u dotglob nullglob

rm -f "${PACKAGE_DIR}/vendor_prefixed/.gitkeep"
rm -f -R "${PACKAGE_DIR}/src/assets/scss"
rm -f -R "${PACKAGE_DIR}/src/assets/js"

# Generate language files
cd "${PACKAGE_DIR}"
npm install --global wp-pot-cli
wp-pot --domain gravity-pdf --src 'src/**/*.php' --src 'pdf.php' --src 'api.php' --src 'gravity-pdf-updater.php' --package 'Gravity PDF' --dest-file src/assets/languages/gravity-pdf.pot > /dev/null

# Create zip package
cd "../"

rm -r -f "${PACKAGE_NAME}"
mv ${VERSION} "${PACKAGE_NAME}"
zip -r -q "${PACKAGE_NAME}-${VERSION}.zip" "${PACKAGE_NAME}"
mv "${PACKAGE_NAME}" ${VERSION}
