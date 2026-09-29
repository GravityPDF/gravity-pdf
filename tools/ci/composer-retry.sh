#!/usr/bin/env bash
# Runs `composer "$@"`, retrying with jittered backoff when a download hit a rate
# limit or server error. Composer retries 5xx itself but never a 429; see "CI
# retries" in .claude/CLAUDE.md.
set -u

attempts=4
log=$(mktemp)
for attempt in $(seq 1 "$attempts"); do
	composer "$@" 2>&1 | tee "$log"
	status=${PIPESTATUS[0]}
	[ "$status" -eq 0 ] && exit 0
	# Auth, solver and platform failures fail the same way every time.
	grep -qE 'HTTP/[0-9.]+ (429|5[0-9]{2})|curl error' "$log" || exit "$status"
	[ "$attempt" -eq "$attempts" ] && break
	delay=$(( attempt * 30 + RANDOM % 30 ))
	echo "::warning::composer $1 hit a transient download error (attempt ${attempt}/${attempts}); retrying in ${delay}s"
	sleep "$delay"
	export COMPOSER_MAX_PARALLEL_HTTP=2
done

echo "::error::composer $1 failed after ${attempts} attempts"
exit "$status"
