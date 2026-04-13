#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"

if [[ $# -gt 0 ]]; then
    printf 'Usage: %s\n' "${0##*/}" >&2
    exit 1
fi

cd "$ROOT_DIR"

log() {
    printf '[pre-push] %s\n' "$*"
}

fail() {
    log "$*" >&2
    exit 1
}

resolve_php_bin() {
    if command -v php >/dev/null 2>&1; then
        command -v php
        return 0
    fi

    if [[ -x "$ROOT_DIR/.tools/php84-common/php" ]]; then
        printf '%s\n' "$ROOT_DIR/.tools/php84-common/php"
        return 0
    fi

    return 1
}

PHP_BIN="$(resolve_php_bin)" || fail 'PHP not found. Install PHP 8.4 or provide .tools/php84-common/php.'

for dependency in bin/phpunit vendor/bin/deptrac; do
    [[ -f "$ROOT_DIR/$dependency" ]] || fail "Missing $dependency. Run composer install."
done

log 'Running PHPUnit.'
APP_ENV=test "$PHP_BIN" bin/phpunit

log 'Running Deptrac.'
"$PHP_BIN" vendor/bin/deptrac analyse --no-progress

log 'Pre-push checks passed.'
