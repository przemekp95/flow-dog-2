#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
COMPOSER_PHAR="$ROOT_DIR/.tools/composer/composer.phar"

download_composer_phar() {
    local php_bin="$1"

    mkdir -p "$(dirname "$COMPOSER_PHAR")"

    if command -v curl >/dev/null 2>&1; then
        curl --fail --silent --show-error --location \
            https://getcomposer.org/download/latest-stable/composer.phar \
            --output "$COMPOSER_PHAR"
        return 0
    fi

    "$php_bin" -r '
        $target = $argv[1];
        $url = "https://getcomposer.org/download/latest-stable/composer.phar";
        $body = @file_get_contents($url);
        if ($body === false) {
            fwrite(STDERR, "Failed to download composer.phar\n");
            exit(1);
        }
        if (file_put_contents($target, $body) === false) {
            fwrite(STDERR, "Failed to write composer.phar\n");
            exit(1);
        }
    ' "$COMPOSER_PHAR"
}

if command -v composer >/dev/null 2>&1; then
    exec composer "$@"
fi

PHP_BIN="$("$ROOT_DIR/scripts/resolve_php_bin.sh")"

if [[ ! -f "$COMPOSER_PHAR" ]]; then
    download_composer_phar "$PHP_BIN"
fi

exec "$PHP_BIN" "$COMPOSER_PHAR" "$@"
