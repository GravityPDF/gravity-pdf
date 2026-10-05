#!/usr/bin/env bash

# Boots this branch with every add-on in tmp/addons/ active, renders a PDF and loads the main pages. Fails on any PHP
# error, warning or notice from Gravity PDF or an add-on, and logs its deprecations as warnings.
set -euo pipefail

ADDONS=tmp/addons
LOG="$ADDONS/debug.log"
URL=http://localhost:8703
COOKIES=$(mktemp)
BODY=$(mktemp)

names=()
for dir in "$ADDONS"/*/; do
  [ -d "$dir" ] && names+=( "$(basename "$dir")" )
done
if [ "${#names[@]}" -eq 0 ]; then
  echo "::error::No add-ons in $ADDONS. Run tools/ci/addons/download.sh, or copy add-on folders there."
  exit 1
fi

# The e2e config, plus the add-ons, with deprecations reported and the E2E-only setup turned off
jq --arg log "/var/www/html/wp-content/plugins/gravity-pdf/$LOG" --args '
  .mappings += ( reduce $ARGS.positional[] as $n ( {}; .["wp-content/plugins/\($n)"] = "./tmp/addons/\($n)" ) )
  | .config += { E2E_TEST_SUITE: false, GPDF_REPORT_DEPRECATIONS: true, WP_DEBUG_LOG: $log }
  | del( .lifecycleScripts )
' "${names[@]}" <tools/wp-env/e2e.json >"$ADDONS/wp-env.json"

wp_env() { yarn --silent wp-env:addons "$@"; }

wp_env start
wp_env run cli wp plugin activate --all
wp_env run cli wp plugin list --status=active --fields=name,version
wp_env run cli wp rewrite structure '/%postname%/' --hard

: >"$LOG"
seeded=$(wp_env run cli wp eval-file wp-content/plugins/gravity-pdf/tools/ci/addons/seed.php | tail -n 1)
read -r form entry pid pdf <<<"$seeded"
if [ -z "${pdf:-}" ]; then
  echo "::error::Seeding the form, entry and PDF failed: $seeded"
  exit 1
fi
echo "Seeded form $form, entry $entry"

curl -fsS -c "$COOKIES" -o /dev/null "$URL/wp-login.php"
curl -fsS -b "$COOKIES" -c "$COOKIES" -o /dev/null \
  --data-urlencode log=admin --data-urlencode pwd=password --data-urlencode testcookie=1 "$URL/wp-login.php"

pages=(
  "/"
  "/wp-admin/"
  "/wp-admin/plugins.php"
  "/wp-admin/admin.php?page=gf_settings&subview=PDF"
  "/wp-admin/admin.php?page=gf_settings&subview=PDF&tab=license"
  "/wp-admin/admin.php?page=gf_settings&subview=PDF&tab=tools"
  "/wp-admin/admin.php?page=gf_edit_forms&view=settings&subview=PDF&id=$form"
  "/wp-admin/admin.php?page=gf_edit_forms&view=settings&subview=PDF&id=$form&pid=$pid"
  "/wp-admin/admin.php?page=gf_entries&id=$form"
  "/wp-admin/admin.php?page=gf_entries&view=entry&id=$form&lid=$entry"
  "${pdf#"$URL"}"
)

failed=0
for page in "${pages[@]}"; do
  status=$(curl -sS -b "$COOKIES" -o "$BODY" -w '%{http_code}' "$URL$page")
  echo "$status $page"
  if [ "$status" != 200 ] || grep -qiE 'Fatal error|There has been a critical error' "$BODY"; then
    echo "::error::$page failed with HTTP $status"
    failed=1
  fi
done

# Only count errors from Gravity PDF or add-on files, or _doing_it_wrong()/_deprecated_*() notices that name our code
ours="/wp-content/plugins/($(IFS='|'; echo "gravity-pdf|${names[*]}"))/|GFPDF|GPDFAPI|GravityPdf"
if grep -E 'PHP (Fatal error|Parse error|Warning|Notice)' "$LOG" | grep -E "$ours"; then
  echo "::error::Gravity PDF or an add-on raised the PHP errors above"
  failed=1
fi

{ grep -E 'PHP Deprecated' "$LOG" | grep -E "$ours" || true; } | sort -u -k4 | while read -r line; do
  echo "::warning::${line#*] }"
done

exit "$failed"
