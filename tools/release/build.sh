#!/usr/bin/env bash

# Abort on the first failure. Without this a broken step (a failed composer install skipping the
# php-scoper prefix, say) still produced a zip, just one missing half the plugin.
# No -u: BRANCH is intentionally unset below.
set -eo pipefail

echo $0 $1

if [ $# -lt 1 ]; then
	echo "usage: $0 <version>"
	exit 1
fi

# Everything the build shells out to, checked up front rather than halfway through.
for tool in git tar yarn composer php jq zip; do
	if ! command -v "$tool" > /dev/null 2>&1; then
		echo "FAIL: $0 needs '$tool' on PATH" >&2
		exit 1
	fi
done

# The package dir is a git archive with no auth.json, so hand the checkout's to composer.
if [ -z "${COMPOSER_AUTH:-}" ] && [ -f auth.json ]; then
	COMPOSER_AUTH="$(cat auth.json)"
	export COMPOSER_AUTH
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
# No i18n step: languages/ ships as committed (refresh it with `composer translate`).
yarn --cwd ${PACKAGE_DIR} build

# Install all dependencies (including dev)
# Prefix will run as post-install command script - Requires php-scoper from dev dependencies
composer install --prefer-dist --working-dir ${PACKAGE_DIR}

# Run vendor cleanup - Ensures that there's no dev dependencies on production
PLUGIN_DIR="$PACKAGE_DIR" bash ./tools/php-scoper/cleanup.sh

# Allowlist the top level, so new tooling or config can't leak into the zip.
source "$(dirname "$0")/package-files.sh"
shopt -s dotglob nullglob
for path in "${PACKAGE_DIR}"/*; do
	is_package_file "$(basename "${path}")" || rm -Rf "${path}"
done
shopt -u dotglob nullglob
for path in "${PACKAGE_EXCLUDE[@]}"; do
	rm -Rf "${PACKAGE_DIR:?}/${path}"
done

cd "${PACKAGE_DIR}/../"

rm -r -f "${PACKAGE_NAME}"
mv ${VERSION} "${PACKAGE_NAME}"
zip -r -q "${PACKAGE_NAME}-${VERSION}.zip" "${PACKAGE_NAME}"
mv "${PACKAGE_NAME}" ${VERSION}
