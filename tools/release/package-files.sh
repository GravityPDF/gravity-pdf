# shellcheck shell=bash
# shellcheck disable=SC2034
# What the zip ships: bin/build.sh prunes to it, verify.sh fails on anything else.
PACKAGE_FILES=(CHANGELOG.md api.php dist gravity-pdf-updater.php pdf.php src vendor vendor_prefixed)
# Removed from inside the kept folders (sources, scoper leftovers).
PACKAGE_EXCLUDE=(src/assets/js src/assets/scss vendor_prefixed/.gitkeep)
PACKAGE_REQUIRED=(pdf.php api.php vendor/autoload.php vendor/composer/installed.json vendor_prefixed/mpdf dist)
# Build output inside PACKAGE_FILES; verify.sh fails unless everything else matches the commit's blobs exactly.
PACKAGE_BUILT=(dist/assets src/assets/languages/gravity-pdf.pot vendor vendor_prefixed)
# PHP in these is flagged by scan.php.
PACKAGE_ASSETS=(dist src/assets)

is_package_file() {
	[[ " ${PACKAGE_FILES[*]} " == *" $1 "* ]]
}
