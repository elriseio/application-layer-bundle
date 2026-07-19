#!/usr/bin/env bash
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
HOOKS_DIR="$REPO_ROOT/.githooks"

if [[ ! -d "$HOOKS_DIR" ]]; then
    echo "Error: hooks directory not found at $HOOKS_DIR" >&2
    exit 1
fi

git -C "$REPO_ROOT" config core.hooksPath "$HOOKS_DIR"
echo "Git hooks installed from $HOOKS_DIR"
echo "Run composer install once to make sure vendor/ is up to date."
