#!/usr/bin/env bash
#
# Run a yarn PHPUnit script; on failure, re-run only the failed tests via
# --filter derived from the JUnit XML. Exit with the second attempt's status.
#
# Usage: retry.sh <yarn-script> <junit-path-in-container> [extra phpunit args ...]
#
# Both attempts write to the same JUnit path so the uploaded artifact reflects
# the final state. A ::warning:: line is emitted when retry triggers so flakes
# stay visible in the workflow log instead of being silently swallowed.

set -uo pipefail

YARN_SCRIPT="$1"
JUNIT_PATH="$2"
shift 2

yarn "$YARN_SCRIPT" "$@" --log-junit="$JUNIT_PATH"
EXIT=$?

if [ "$EXIT" -eq 0 ]; then
	exit 0
fi

# JUnit lives inside the wp-env container; the bind-mount surfaces it at the
# repo-relative tmp/junit/ path on the runner.
HOST_JUNIT="${JUNIT_PATH#/var/www/html/wp-content/plugins/gravity-pdf/}"

if [ ! -f "$HOST_JUNIT" ]; then
	echo "::warning::retry: $HOST_JUNIT not found; PHPUnit likely crashed before writing JUnit, original failure stands"
	exit "$EXIT"
fi

FAILED=$(php -r '
$xml = @simplexml_load_file($argv[1]);
if (!$xml) { exit(1); }
$tests = [];
foreach ($xml->xpath("//testcase[failure or error]") as $tc) {
	$tests[] = preg_quote((string) $tc["class"], "/") . "::" . preg_quote((string) $tc["name"], "/");
}
echo implode("|", $tests);
' "$HOST_JUNIT")

if [ -z "$FAILED" ]; then
	echo "::warning::retry: no failed tests parsed from $HOST_JUNIT; original failure stands"
	exit "$EXIT"
fi

echo "::warning::retry: re-running failed tests: $FAILED"

# Strip --coverage-clover from the retry: the first attempt already wrote the
# full-suite clover, and re-running with a single-test --filter would overwrite
# it with reduced-subset coverage and skew the PR coverage comment.
RETRY_ARGS=()
for arg in "$@"; do
	[[ "$arg" == --coverage-clover=* ]] || RETRY_ARGS+=("$arg")
done

yarn "$YARN_SCRIPT" "${RETRY_ARGS[@]}" --filter "/^($FAILED)$/" --log-junit="$JUNIT_PATH"
