#!/usr/bin/env bash

# Release gate: the lockfiles must match their manifests, and no shipped dependency may have a known advisory.
# Dev/build-only advisories are reported, never fatal. Prints a Markdown report; failures also go to stderr.
set -uo pipefail

scoper_dir="tools/php-scoper"
# composer audit fetches advisories, so it gets the 429 retry.
audit=(bash tools/ci/composer-retry.sh audit --locked --abandoned=report)
fail=0

# $1 = label, $2 = fatal (1) or report-only (0), rest = the command.
check() {
	local label="$1" fatal="$2" output
	shift 2
	if output=$("$@" 2>&1); then
		echo "- ${label}: ok"
		return
	fi
	if [ "${fatal}" -eq 1 ]; then
		echo "FAIL: ${label}" >&2
		echo "${output}" >&2
		echo "- **${label}: FAIL**"
		fail=1
	else
		echo "- ${label}: advisories found (report-only)"
	fi
	printf '\n  <details><summary>Output</summary>\n\n%s\n%s\n%s\n\n  </details>\n' '```' "${output}" '```'
}

echo "### Dependency audit"
echo

check "composer.lock matches composer.json" 1 composer validate --check-lock --no-check-publish
check "${scoper_dir}/composer.lock matches its composer.json" 1 \
	composer validate --check-lock --no-check-publish --working-dir "${scoper_dir}"
check "Composer, shipped packages" 1 "${audit[@]}" --no-dev
check "Composer, all packages (incl. dev)" 0 "${audit[@]}"
check "Composer, php-scoper toolchain" 0 "${audit[@]}" --working-dir "${scoper_dir}"
# yarn.lock drift is caught by build.sh's --frozen-lockfile install.
check "Yarn, shipped packages" 1 yarn audit --groups dependencies --summary
check "Yarn, dev packages" 0 yarn audit --groups devDependencies --summary

exit "${fail}"
