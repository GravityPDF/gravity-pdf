#!/usr/bin/env bash

# Release gate: the lockfiles must match their manifests, and no shipped dependency may have a known advisory.
# Dev/build-only advisories are reported, never fatal. Prints a Markdown report; failures also go to stderr.
set -uo pipefail

# composer audit fetches advisories, so it gets the 429 retry.
audit=(bash tools/ci/composer-retry.sh audit --locked --abandoned=report)
fail=0

# check <label> fatal|report <command...>; returns the command's status.
check() {
	local label="$1" mode="$2" output
	shift 2
	if output=$("$@" 2>&1); then
		echo "- ${label}: ok"
		return 0
	fi
	if [ "${mode}" = fatal ]; then
		echo "FAIL: ${label}" >&2
		echo "${output}" >&2
		echo "- **${label}: FAIL**"
		fail=1
	else
		echo "- ${label}: advisories found (report-only)"
	fi
	printf '\n  <details><summary>Output</summary>\n\n%s\n%s\n%s\n\n  </details>\n' '```' "${output}" '```'
	return 1
}

echo "### Dependency audit"
echo

check "composer.lock matches composer.json" fatal composer validate --check-lock --no-check-publish
# A clean full audit covers the shipped subset, saving a second advisory fetch.
if ! check "Composer, all packages (incl. dev)" report "${audit[@]}"; then
	check "Composer, shipped packages" fatal "${audit[@]}" --no-dev
fi
# yarn.lock drift is caught by build.sh's --frozen-lockfile install.
check "Yarn, shipped packages" fatal yarn audit --groups dependencies --summary
check "Yarn, dev packages" report yarn audit --groups devDependencies --summary

exit "${fail}"
