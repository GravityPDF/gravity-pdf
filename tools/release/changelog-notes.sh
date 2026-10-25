#!/usr/bin/env bash

# Prints the version's '### <version>' section of CHANGELOG.md for the release notes, falling back to the base version
# for a prerelease (6.18.0-RC1 -> 6.18.0), or nothing if there isn't one.
set -euo pipefail

if [ $# -lt 1 ]; then
	echo "usage: $0 <version>" >&2
	exit 1
fi

version="$1"
changelog="$(dirname "$0")/../../CHANGELOG.md"

section_for() {
	awk -v heading="### $1" '
		$0 == heading { capture = 1; next }
		/^##/ { if (capture) exit }
		capture { print }
	' "${changelog}"
}

section=$(section_for "${version}")
if [ -z "${section}" ]; then
	section=$(section_for "${version%%-*}")
fi

if [ -n "${section}" ]; then
	printf '### Changelog\n%s\n\n' "${section}"
fi
