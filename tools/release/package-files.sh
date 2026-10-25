# shellcheck shell=bash
# shellcheck disable=SC2034
# What the zip ships: build.sh prunes to it, verify.sh fails on anything else.
PACKAGE_FILES=(CHANGELOG.md api.php build gravity-pdf-updater.php languages pdf.php src vendor vendor_prefixed)
# Removed from inside the kept folders (sources, scoper leftovers).
PACKAGE_EXCLUDE=(src/assets/js src/assets/scss)
PACKAGE_REQUIRED=(pdf.php api.php vendor/autoload.php vendor/composer/installed.json vendor_prefixed/mpdf build/assets)

is_package_file() {
	[[ " ${PACKAGE_FILES[*]} " == *" $1 "* ]]
}
