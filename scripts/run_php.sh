#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP_BIN="$("$ROOT_DIR/scripts/resolve_php_bin.sh")"

exec "$PHP_BIN" "$@"
