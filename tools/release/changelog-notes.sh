#!/usr/bin/env bash

# Prints the version's '### <version>' section of CHANGELOG.md for the release notes, or nothing if there isn't one.
set -euo pipefail

if [ $# -lt 1 ]; then
	echo "usage: $0 <version>" >&2
	exit 1
fi

version="$1"
changelog="$(dirname "$0")/../../CHANGELOG.md"

section=$(awk -v heading="### ${version}" '
	$0 == heading { capture = 1; next }
	/^##/ { if (capture) exit }
	capture { print }
' "${changelog}")

if [ -n "${section}" ]; then
	printf '### Changelog\n%s\n\n' "${section}"
fi
