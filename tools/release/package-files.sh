# shellcheck shell=bash
# shellcheck disable=SC2034
# What the zip ships: bin/build.sh prunes to it, verify.sh fails on anything else.
PACKAGE_FILES=(CHANGELOG.md api.php dist gravity-pdf-updater.php pdf.php src vendor vendor_prefixed)
# Removed from inside the kept folders (sources, scoper leftovers).
PACKAGE_EXCLUDE=(src/assets/js src/assets/scss vendor_prefixed/.gitkeep)
PACKAGE_REQUIRED=(pdf.php api.php vendor/autoload.php vendor/composer/installed.json vendor_prefixed/mpdf dist)

is_package_file() {
	[[ " ${PACKAGE_FILES[*]} " == *" $1 "* ]]
}
