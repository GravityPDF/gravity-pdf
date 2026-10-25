# shellcheck shell=bash
# Top-level entries the zip ships: bin/build.sh prunes the rest, verify.sh fails on it.
# shellcheck disable=SC2034
PACKAGE_FILES=(CHANGELOG.md api.php dist gravity-pdf-updater.php pdf.php src vendor vendor_prefixed)
PACKAGE_ASSETS=dist
