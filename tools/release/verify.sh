#!/usr/bin/env bash

# Release gate: fails if the built zip has the wrong layout, version or dependencies.
set -euo pipefail

if [ $# -lt 2 ]; then
	echo "usage: $0 <zip> <version>" >&2
	exit 1
fi

zip="$1"
version="$2"

source "$(dirname "$0")/package-files.sh"
REQUIRED=(pdf.php api.php vendor/autoload.php vendor/composer/installed.json vendor_prefixed/mpdf "${PACKAGE_ASSETS}")

if [ ! -s "${zip}" ]; then
	echo "FAIL: ${zip} is missing or empty" >&2
	exit 1
fi

entries=$(unzip -Z1 "${zip}")
fail=0

top=$(cut -d/ -f1 <<<"${entries}" | sort -u)
if [ "${top}" != "gravity-pdf" ]; then
	echo "FAIL: expected a single gravity-pdf/ folder at the top of the zip, found: ${top//$'\n'/ }" >&2
	exit 1
fi

for entry in $(awk -F/ 'NF > 1 && $2 != "" { print $2 }' <<<"${entries}" | sort -u); do
	[[ " ${PACKAGE_FILES[*]} " == *" ${entry} "* ]] || { echo "FAIL: unexpected top-level entry '${entry}'" >&2; fail=1; }
done

for path in "${REQUIRED[@]}"; do
	grep -q "^gravity-pdf/${path}\(/\|$\)" <<<"${entries}" || { echo "FAIL: missing ${path}" >&2; fail=1; }
done

# Catches a tag pushed before the version bump.

pdf_php=$(unzip -p "${zip}" gravity-pdf/pdf.php)
header=$(sed -n 's/^Version: *//p' <<<"${pdf_php}")
constant=$(sed -n "s/^define( 'PDF_EXTENDED_VERSION', '\([^']*\)' );.*/\1/p" <<<"${pdf_php}")

if [ "${header}" != "${version}" ]; then
	echo "FAIL: pdf.php's Version header is '${header}', expected '${version}'" >&2
	fail=1
fi

if [ "${constant}" != "${version}" ]; then
	echo "FAIL: PDF_EXTENDED_VERSION is '${constant}', expected '${version}'" >&2
	fail=1
fi

if [ "$(unzip -p "${zip}" gravity-pdf/vendor/composer/installed.json 2>/dev/null | jq '.dev')" != "false" ]; then
	echo "FAIL: vendor/ was installed with dev dependencies" >&2
	fail=1
fi

[ "${fail}" -eq 0 ] || exit 1

echo "OK: ${zip} is a gravity-pdf ${version} package with no dev files or dependencies"
