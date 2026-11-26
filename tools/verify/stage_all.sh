#!/usr/bin/env bash
# Stage the remaining in-scope application work, excluding generated junk.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

git add -A -- \
    app config database docs public resources routes storage tests tools \
    composer.json composer.lock \
    ':(exclude)public/reports/**' \
    ':(exclude)**/__pycache__/**' \
    ':(exclude)**/*.pyc' \
    ':(exclude)**/.zombiecoder/**' \
    ':(exclude)tools/verify/.artifacts/**'

echo "=== staged count ==="
git diff --cached --name-only | wc -l

echo "=== junk that must be zero ==="
git diff --cached --name-only | grep -E '(__pycache__|\.pyc$|/\.artifacts/|\.zombiecoder/|^public/reports/)' | wc -l

echo "=== biggest staged files ==="
git diff --cached --name-only -z | xargs -0 du -b 2>/dev/null | sort -rn | head -5

echo "=== by area ==="
git diff --cached --name-only | cut -d/ -f1 | sort | uniq -c | sort -rn

echo "=== stat ==="
git diff --cached --stat | tail -2
