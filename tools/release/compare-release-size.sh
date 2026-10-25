#!/usr/bin/env bash

# Prints a size comparison with the newest full release below <version>, so a hot-patch compares with its own line.
# Informational: it never fails the build.
set -euo pipefail

if [ $# -lt 2 ]; then
	echo "usage: $0 <zip> <version>" >&2
	exit 1
fi

zip="$1"
version="$2"
repo="${GITHUB_REPOSITORY:-GravityPDF/gravity-pdf}"

size=$(wc -c < "${zip}")

prev_tag=""
prev_size=""
# One "tag<TAB>zip size" row per full release.
if rows=$(gh api "repos/${repo}/releases?per_page=100" --paginate --jq '.[] | select(.draft or .prerelease | not) | [.tag_name, ([.assets[] | select(.name | test("^gravity-pdf-.*\\.zip$")) | .size] | first // "")] | @tsv' 2>/dev/null); then
	prev_tag=$(printf '%s\n%s\n' "$(cut -f1 <<<"${rows}")" "${version}" | sort -V -u | awk -v v="${version}" '$0 == v { print prev; exit } { prev = $0 }')
	prev_size=$(awk -F'\t' -v t="${prev_tag}" '$1 == t { print $2; exit }' <<<"${rows}")
fi

mb() {
	if [ -z "$1" ]; then echo "n/a"; return; fi
	awk -v b="$1" 'BEGIN { printf "%.2f MB", b / 1048576 }'
}

delta() {
	if [ -z "$2" ]; then echo "n/a"; return; fi
	awk -v n="$1" -v o="$2" 'BEGIN { printf "%+.2f%%", ((n - o) / o) * 100 }'
}

echo "### Package size"
echo
if [ -n "${prev_tag}" ]; then
	echo "Compared with the full release \`${prev_tag}\`:"
else
	echo "_No earlier full release found to compare against._"
fi
echo
echo "| Package | This build | Previous | Change |"
echo "| --- | --- | --- | --- |"
echo "| \`gravity-pdf\` | $(mb "${size}") | $(mb "${prev_size}") | $(delta "${size}" "${prev_size}") |"
