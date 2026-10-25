# shellcheck shell=bash
# Top-level entries the zip ships: build.sh prunes the rest, verify.sh fails on it.
# shellcheck disable=SC2034
PACKAGE_FILES=(CHANGELOG.md api.php build gravity-pdf-updater.php languages pdf.php src vendor vendor_prefixed)
PACKAGE_ASSETS=build/assets
