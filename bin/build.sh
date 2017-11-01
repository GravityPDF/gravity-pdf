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
PACKAGE_NAME="gravity-forms-pdf-extended"

# Create the working directory
mkdir -p ${PACKAGE_DIR}

# Get an archive of our plugin
git archive ${BRANCH} --output ${PACKAGE_DIR}/package.tar.gz
tar -zxf ${PACKAGE_DIR}/package.tar.gz --directory ${PACKAGE_DIR} && rm ${PACKAGE_DIR}/package.tar.gz

# Run Composer
composer install --quiet --no-dev  --prefer-dist --optimize-autoloader --working-dir ${PACKAGE_DIR}

# Cleanup Node JS
rm -R ${PACKAGE_DIR}/node_modules

# Cleanup additional build files
FILES=(
"${PACKAGE_DIR}/composer.json"
"${PACKAGE_DIR}/composer.lock"
"${PACKAGE_DIR}/package.json"
"${PACKAGE_DIR}/yarn.lock"
"${PACKAGE_DIR}/gulpfile.js"
"${PACKAGE_DIR}/.babelrc"
"${PACKAGE_DIR}/webpack.config.js"
)

for i in "${FILES[@]}"
do
    rm ${i}
done

rm -R "${PACKAGE_DIR}/src/assets/css"
rm -R "${PACKAGE_DIR}/src/assets/js"

# Create zip package
cd ${TMP_DIR}
rm -R -f ${PACKAGE_NAME}
mv ${VERSION} ${PACKAGE_NAME}
zip -r -q ${PACKAGE_NAME}-${VERSION}.zip ${PACKAGE_NAME}
mv ${PACKAGE_NAME} ${VERSION}