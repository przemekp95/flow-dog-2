#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

if command -v php >/dev/null 2>&1; then
    command -v php
    exit 0
fi

if [[ -x "$ROOT_DIR/.tools/php84-common/php" ]]; then
    printf '%s\n' "$ROOT_DIR/.tools/php84-common/php"
    exit 0
fi

printf '%s\n' 'PHP not found. Install PHP 8.4 or provide .tools/php84-common/php.' >&2
exit 1
