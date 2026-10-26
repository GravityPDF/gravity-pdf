# shellcheck shell=bash
# shellcheck disable=SC2034
# What the zip ships: build.sh prunes to it, verify.sh fails on anything else.
PACKAGE_FILES=(CHANGELOG.md api.php build gravity-pdf-updater.php languages pdf.php src vendor vendor_prefixed)
# Removed from inside the kept folders (sources, scoper leftovers).
PACKAGE_EXCLUDE=(src/assets/js src/assets/scss)
PACKAGE_REQUIRED=(pdf.php api.php vendor/autoload.php vendor/composer/installed.json vendor_prefixed/mpdf build/assets)
# Build output inside PACKAGE_FILES; verify.sh fails unless everything else matches the commit's blobs exactly.
PACKAGE_BUILT=(build/assets vendor vendor_prefixed)
# PHP in these is flagged by scan.php.
PACKAGE_ASSETS=(build src/assets)
# Build-only composer projects with their own lock; audit-dependencies.sh validates and reports on them.
BUILD_COMPOSER_DIRS=(tools/php-scoper)

is_package_file() {
	[[ " ${PACKAGE_FILES[*]} " == *" $1 "* ]]
}
