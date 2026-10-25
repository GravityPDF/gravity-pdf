#!/usr/bin/env bash

# Prints copy/paste commands to verify the download, reading the hash from the SHA256SUMS beside <zip>.
set -euo pipefail

if [ $# -lt 1 ]; then
	echo "usage: $0 <zip>" >&2
	exit 1
fi

zip="$(basename "$1")"
hash=$(cut -d' ' -f1 "$(dirname "$1")/SHA256SUMS")

echo "### Verify the download"
echo
echo "SHA-256: \`${hash}\`"
echo
echo "Run in the folder with \`${zip}\` (macOS / Linux):"
echo
echo '```sh'
echo "echo \"${hash}  ${zip}\" | shasum -a 256 -c"
echo '```'
echo
echo "Windows PowerShell (prints \`True\` if it matches):"
echo
echo '```powershell'
echo "(Get-FileHash ${zip} -Algorithm SHA256).Hash -eq \"${hash}\""
echo '```'
