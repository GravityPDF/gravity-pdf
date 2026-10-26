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

if [ ! -s "${zip}" ]; then
	echo "FAIL: ${zip} is missing or empty" >&2
	exit 1
fi

entries=$(unzip -Z1 "${zip}")
fail=0

has_entry() {
	grep -q "^gravity-pdf/$1\(/\|$\)" <<<"${entries}"
}

top=$(cut -d/ -f1 <<<"${entries}" | sort -u)
if [ "${top}" != "gravity-pdf" ]; then
	echo "FAIL: expected a single gravity-pdf/ folder at the top of the zip, found: ${top//$'\n'/ }" >&2
	exit 1
fi

for entry in $(awk -F/ 'NF > 1 && $2 != "" { print $2 }' <<<"${entries}" | sort -u); do
	is_package_file "${entry}" || { echo "FAIL: unexpected top-level entry '${entry}'" >&2; fail=1; }
done

for path in "${PACKAGE_REQUIRED[@]}"; do
	has_entry "${path}" || { echo "FAIL: missing ${path}" >&2; fail=1; }
done

for path in "${PACKAGE_EXCLUDE[@]}"; do
	! has_entry "${path}" || { echo "FAIL: ${path} should have been removed" >&2; fail=1; }
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

# Everything not built must match the commit byte for byte, so a compromised build step can't alter source.
unpacked=$(mktemp -d)
trap 'rm -rf "${unpacked}"' EXIT
unzip -q "${zip}" -d "${unpacked}"

files=$(sed -n 's|^gravity-pdf/\(.*[^/]\)$|\1|p' <<<"${entries}")
expected=$(git ls-tree -r --format='%(objectname)%x09%(path)' HEAD -- "${PACKAGE_FILES[@]}")
shipped=$(paste <(cd "${unpacked}/gravity-pdf" && git hash-object --no-filters --stdin-paths <<<"${files}") - <<<"${files}")

source_errors=$(awk -F '\t' -v skip="${PACKAGE_EXCLUDE[*]} ${PACKAGE_BUILT[*]}" '
	BEGIN { n = split(skip, skips, " ") }
	function skipped(path, i) {
		for (i = 1; i <= n; i++) if (path == skips[i] || index(path, skips[i] "/") == 1) return 1
		return 0
	}
	$2 == "" || skipped($2) { next }
	NR == FNR { want[$2] = $1; next }
	!($2 in want) { print "FAIL: " $2 " is in the zip but not in git"; next }
	want[$2] != $1 { print "FAIL: " $2 " differs from git" }
	{ delete want[$2] }
	END { for (path in want) print "FAIL: " path " is in git but missing from the zip" }
' <(printf '%s\n' "${expected}") <(printf '%s\n' "${shipped}"))

if [ -n "${source_errors}" ]; then
	sort >&2 <<<"${source_errors}"
	fail=1
fi

[ "${fail}" -eq 0 ] || exit 1

echo "OK: ${zip} is a gravity-pdf ${version} package with no dev files or dependencies, and its source matches $(git rev-parse --short HEAD)"
