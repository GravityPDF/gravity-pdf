#!/usr/bin/env bash

# Resolves the Gravity Forms build a test run mounts and downloads it to <dest>/gravityforms.zip.
#   lock      what composer.lock pins: already in vendor/, so nothing is downloaded
#   next      the channel-master build from GravityPDF/gravityforms-test-builds (private; needs GH_TOKEN)
#   floor     $required_gf_version from pdf.php, from composer.gravity.io (needs GF_LICENSE)
#   X.Y[.Z]   that exact release from composer.gravity.io, for bisecting
# Appends built, age_hours and line (a one-line description for Slack) to $GITHUB_OUTPUT, and line to
# $GITHUB_STEP_SUMMARY, when set.
# Never add `set -x`: it would print the credentials.
set -euo pipefail

usage='Usage: fetch-gravityforms.sh <lock|next|floor|version> <dest-dir>'
target=${1:?$usage}
dest=${2:?$usage}

CHANNEL=channel-master
BUILDS_REPO=GravityPDF/gravityforms-test-builds
REGISTRY=https://composer.gravity.io

built='' age_hours=''
mkdir -p "$dest"

zip=$dest/gravityforms.zip

# Sets $version from the zipped plugin header.
read_version() {
  version=$(unzip -p "$zip" gravityforms/gravityforms.php | sed -n 's/^[ *]*Version: *//p' | head -n1) || true
  [ -n "$version" ] || { echo "::error::$1 has no gravityforms/gravityforms.php ($(file -b "$zip"), $(wc -c <"$zip") bytes)"; exit 1; }
}

case "$target" in
  lock)
    version=$(jq -r '(.packages + .["packages-dev"])[] | select(.name == "gravity/gravityforms") | .version' composer.lock)
    source='lock: composer.lock'
    ;;

  next)
    if [ -z "${GH_TOKEN:-}" ]; then
      echo "::error::The next target needs the GF_TEST_BUILDS_TOKEN secret (Contents: read on ${BUILDS_REPO}). It is missing, so this run cannot fetch ${CHANNEL}."
      exit 1
    fi
    built=$(gh release view "$CHANNEL" -R "$BUILDS_REPO" --json assets \
      --jq '.assets[] | select(.name == "gravityforms-master.zip") | .updatedAt')
    [ -n "$built" ] || { echo "::error::${CHANNEL} has no gravityforms-master.zip asset"; exit 1; }
    age_hours=$(( ( $(date -u +%s) - $(date -u -d "$built" +%s) ) / 3600 ))
    gh release download "$CHANNEL" -R "$BUILDS_REPO" -p gravityforms-master.zip -O "$zip" --clobber
    read_version "$CHANNEL"
    source="next: ${CHANNEL}, built ${built}"
    ;;

  *)
    if [ "$target" = floor ]; then
      wanted=$(sed -n "s/.*\$required_gf_version *= *'\([^']*\)'.*/\1/p" pdf.php)
      [ -n "$wanted" ] || { echo "::error::No \$required_gf_version in pdf.php"; exit 1; }
      source="floor: pdf.php \$required_gf_version"
    else
      wanted=$target source=pinned
    fi
    [[ "$wanted" =~ ^[0-9]+(\.[0-9]+)+$ ]] || { echo "::error::'$wanted' is not a Gravity Forms version"; exit 1; }
    : "${GF_LICENSE:?The ${target} target needs the GF_LICENSE secret for ${REGISTRY}}"

    # The catalogue is public; pdf.php's 2.5 means 2.5.0 when only that is published.
    catalogue=$(curl -fsS --retry 3 "$REGISTRY/packages.json")
    version=$(jq -r --arg v "$wanted" '.packages["gravity/gravityforms"] | [$v, "\($v).0"] as $c
      | first($c[] as $k | select(has($k)) | $k) // empty' <<<"$catalogue")
    if [ -z "$version" ]; then
      oldest=$(jq -r '.packages["gravity/gravityforms"] | keys[]' <<<"$catalogue" | sort -V | head -n1)
      echo "::error::Gravity Forms ${wanted} (${target}) is not published on ${REGISTRY}; the oldest release there is ${oldest}. Raise \$required_gf_version in pdf.php to a published release."
      exit 1
    fi
    curl -fsSL --retry 3 -u "${GF_LICENSE}:http://localhost" -o "$zip" \
      "$REGISTRY/downloads/?plugin=gravityforms&version=${version}" \
      || { echo "::error::Could not download Gravity Forms ${version} from ${REGISTRY}; check GF_LICENSE"; exit 1; }
    read_version "Gravity Forms ${version}"
    ;;
esac

line="Gravity Forms \`${version:-?}\` (${source})"
echo "$line"
if [ -n "${GITHUB_STEP_SUMMARY:-}" ]; then
  printf '### Gravity Forms under test\n\n%s\n' "$line" >>"$GITHUB_STEP_SUMMARY"
fi
if [ -n "${GITHUB_OUTPUT:-}" ]; then
  {
    echo "built=$built"
    echo "age_hours=$age_hours"
    echo "line=$line"
  } >>"$GITHUB_OUTPUT"
fi
