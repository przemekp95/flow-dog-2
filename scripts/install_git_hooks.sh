#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(git rev-parse --show-toplevel)"

cd "$ROOT_DIR"

chmod +x .githooks/pre-commit .githooks/pre-push scripts/run_pre_commit_checks.sh scripts/run_pre_push_checks.sh scripts/install_git_hooks.sh
git config core.hooksPath .githooks

printf 'Git hooks installed. core.hooksPath=%s\n' "$(git config --get core.hooksPath)"
