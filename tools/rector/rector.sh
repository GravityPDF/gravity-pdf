#!/usr/bin/env bash

set -euo pipefail

RECTOR_DIR="$(cd "$(dirname "$0")" && pwd)"

if [ ! -f "${RECTOR_DIR}/vendor/bin/rector" ]; then
  composer install --no-interaction --working-dir "${RECTOR_DIR}"
fi

php "${RECTOR_DIR}/vendor/bin/rector" process --config "${RECTOR_DIR}/rector.php" "$@"
