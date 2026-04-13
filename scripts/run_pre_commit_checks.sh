#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
MODE="staged"

if [[ "${1:-}" == "--all" ]]; then
    MODE="all"
    shift
fi

if [[ $# -gt 0 ]]; then
    printf 'Usage: %s [--all]\n' "${0##*/}" >&2
    exit 1
fi

cd "$ROOT_DIR"

log() {
    printf '[pre-commit] %s\n' "$*"
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

declare -a PRETTIER_CMD=()

resolve_prettier_cmd() {
    if [[ -x "$ROOT_DIR/node_modules/.bin/prettier" ]]; then
        PRETTIER_CMD=("$ROOT_DIR/node_modules/.bin/prettier")
        return 0
    fi

    if command -v npx >/dev/null 2>&1; then
        PRETTIER_CMD=(npx --no-install prettier)
        return 0
    fi

    return 1
}

declare -a candidate_files=()

if [[ "$MODE" == "all" ]]; then
    while IFS= read -r file; do
        candidate_files+=("$file")
    done < <(git ls-files --cached --others --exclude-standard)
else
    while IFS= read -r file; do
        candidate_files+=("$file")
    done < <(git diff --cached --name-only --diff-filter=ACMR)
fi

declare -a php_files=()
declare -a prettier_files=()
declare -a format_tracked_files=()

for file in "${candidate_files[@]}"; do
    [[ -f "$file" ]] || continue

    case "$file" in
        *.php)
            php_files+=("$file")
            format_tracked_files+=("$file")
            ;;
        *.md|*.json|*.yml|*.yaml)
            prettier_files+=("$file")
            format_tracked_files+=("$file")
            ;;
    esac
done

if (( ${#php_files[@]} == 0 && ${#prettier_files[@]} == 0 )); then
    log "No ${MODE} PHP, Markdown, JSON or YAML files to check."
    exit 0
fi

if [[ "$MODE" == "staged" ]]; then
    declare -a partially_staged_files=()

    for file in "${format_tracked_files[@]}"; do
        if ! git diff --quiet -- "$file" && ! git diff --cached --quiet -- "$file"; then
            partially_staged_files+=("$file")
        fi
    done

    if (( ${#partially_staged_files[@]} > 0 )); then
        log 'Refusing to auto-format partially staged files:' >&2
        printf '  - %s\n' "${partially_staged_files[@]}" >&2
        fail 'Stage or stash the remaining hunks and retry.'
    fi
fi

if (( ${#php_files[@]} > 0 )); then
    PHP_BIN="$(resolve_php_bin)" || fail 'PHP not found. Install PHP 8.4 or provide .tools/php84-common/php.'

    [[ -f "$ROOT_DIR/vendor/bin/php-cs-fixer" ]] || fail 'Missing vendor/bin/php-cs-fixer. Run composer install.'

    log "Linting ${#php_files[@]} PHP file(s)."
    for file in "${php_files[@]}"; do
        "$PHP_BIN" -l "$file" >/dev/null
    done

    log "Formatting ${#php_files[@]} PHP file(s) with PHP CS Fixer."
    "$PHP_BIN" vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --using-cache=no --path-mode=intersection --quiet -- "${php_files[@]}"

    if [[ "$MODE" == "staged" ]]; then
        git add -- "${php_files[@]}"
    fi
fi

if (( ${#prettier_files[@]} > 0 )); then
    resolve_prettier_cmd || fail 'Prettier not found. Run npm ci.'

    log "Formatting ${#prettier_files[@]} docs/config file(s) with Prettier."
    "${PRETTIER_CMD[@]}" --write --ignore-unknown -- "${prettier_files[@]}" >/dev/null

    if [[ "$MODE" == "staged" ]]; then
        git add -- "${prettier_files[@]}"
    fi
fi

log 'Pre-commit checks passed.'
