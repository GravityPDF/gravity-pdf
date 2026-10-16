#!/usr/bin/env bash

# Unpacks a built release zip and points the e2e wp-env at it in place of the
# working tree, through tools/wp-env/e2e.override.json.
set -euo pipefail

if [ $# -lt 1 ]; then
	echo "usage: $0 <zip>" >&2
	exit 1
fi

zip="$1"
root="$(cd "$(dirname "$0")/../.." && pwd)"
out="${root}/tmp/e2e-package"
override="${root}/tools/wp-env/e2e.override.json"

rm -Rf "${out}"
unzip -q "${zip}" -d "${out}"

plugin="${out}/gravity-pdf"
if [ ! -f "${plugin}/pdf.php" ]; then
	echo "FAIL: ${zip} has no gravity-pdf/pdf.php" >&2
	exit 1
fi

# WP_DEBUG_LOG points into the plugin's tmp/, which the zip does not ship.
mkdir -p "${plugin}/tmp"

# Same mount key as tools/wp-env/e2e.json.
jq -n --arg p "${plugin}" '{mappings: {"wp-content/plugins/gravity-pdf": $p}}' > "${override}"

echo "wp-env:e2e now mounts ${plugin}; delete ${override} and restart wp-env to go back." >&2
