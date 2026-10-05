#!/usr/bin/env bash

# Installs the latest release of every add-on composer.gravitypdf.com lists into tmp/addons/ with Composer.
# GPDF_ADDON_LICENSE is one license, or `{slug}:{license},{slug}:{license}` (`@` works too) with an optional
# `*:{license}` fallback. It is expanded into the `{slug}@{license}, ...` password Composer's repository expects.
# Never add `set -x`: it would print the licenses.
set -euo pipefail

: "${GPDF_ADDON_LICENSE:?Set GPDF_ADDON_LICENSE to a license that covers every add-on, or {slug}:{license} pairs}"

REPO=https://composer.gravitypdf.com

# gravitypdf/gravity-pdf is this branch, so it is replaced rather than installed
ADDONS=()
while read -r addon; do
  ADDONS+=( "$addon" )
done < <(curl -fsS --retry 3 "$REPO/packages.json" |
  jq -r '.["available-packages"] // [] | .[] | select(. != "gravitypdf/gravity-pdf") | sub("^gravitypdf/"; "")')
if [ "${#ADDONS[@]}" -eq 0 ]; then
  echo "::error::$REPO/packages.json has no available-packages list to install from"
  exit 1
fi

DEST=tmp/addons
PROJECT=tmp/addons-composer

# The license for slug $1: its own entry, else the `*` entry, else the whole secret when it has no slugs
license_for() {
  local entry entries slug fallback=
  if [[ "$GPDF_ADDON_LICENSE" != *[:@]* ]]; then
    echo "${GPDF_ADDON_LICENSE//[[:space:]]/}"
    return
  fi

  IFS=',' read -ra entries <<<"$GPDF_ADDON_LICENSE"
  for entry in "${entries[@]}"; do
    entry=${entry//[[:space:]]/}
    slug=${entry%%[:@]*}
    case "$slug" in
      "$1")
        echo "${entry#*[:@]}"
        return
        ;;
      '*') fallback=${entry#*[:@]} ;;
    esac
  done

  echo "$fallback"
}

password=()
require=()
for addon in "${ADDONS[@]}"; do
  license=$(license_for "$addon")
  if [ -z "$license" ]; then
    echo "::error::GPDF_ADDON_LICENSE has no license for $addon"
    exit 1
  fi

  # The expanded password isn't the secret itself, so GitHub would not mask its parts
  [ -n "${GITHUB_ACTIONS:-}" ] && echo "::add-mask::$license"
  password+=( "$addon@$license" )
  require+=( "gravitypdf/$addon" )
done

rm -rf "$DEST" "$PROJECT"
mkdir -p "$DEST" "$PROJECT"

jq -n --arg repo "$REPO" --args '{
  repositories: [ { type: "composer", url: $repo } ],
  require: ( reduce $ARGS.positional[] as $p ( {}; .[$p] = "*" ) ),
  replace: { "gravitypdf/gravity-pdf": "*" },
  extra: { "installer-paths": { "../addons/{$name}/": [ "type:wordpress-plugin" ] } },
  config: { "allow-plugins": { "composer/installers": true } }
}' "${require[@]}" >"$PROJECT/composer.json"

# Credentials go in the environment, not on disk. The username is the site URL the licenses are active for.
COMPOSER_AUTH=$(jq -n --arg host "${REPO#https://}" --arg password "$(IFS=,; echo "${password[*]}" | sed 's/,/, /g')" \
  '{ "http-basic": { ($host): { username: "http://localhost", password: $password } } }')
export COMPOSER_AUTH

# The add-ons run inside wp-env, so this runner's PHP extensions (Imagick for PDF to Image) don't matter
composer update --working-dir="$PROJECT" --no-interaction --no-progress --no-autoloader --ignore-platform-reqs

composer show --working-dir="$PROJECT" 'gravitypdf/*'
