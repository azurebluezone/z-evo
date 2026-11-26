#!/usr/bin/env bash
#
# Release gate for contract gc-mwx7rjsm.
#
# Proves the remaining application work is committed, that the full test suite
# runs green against the live database, that no generated junk (session dumps,
# pycache, verification artifacts, nested agent state) got tracked, that the
# in-scope tree is clean, and that local HEAD equals origin/main.
#
# Prints ZC-FULL-SHIP-OK only when every stage holds.

set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT" || exit 1

ARTIFACTS="tools/verify/.artifacts"
mkdir -p "$ARTIFACTS"

fail=0
note() { printf 'PASS: %s\n' "$*"; }
bad()  { printf 'FAIL: %s\n' "$*"; fail=1; }

echo "=== 1. Syntax of the committed slice ==="
while IFS= read -r f; do
    [ -n "$f" ] || continue
    [ -f "$f" ] || continue
    case "$f" in
        *.php)
            out=$(php -l "$f" 2>&1) || { bad "php -l $f -> $out"; continue; }
            ;;
        *.js)
            out=$(node --check "$f" 2>&1) || { bad "node --check $f -> $out"; continue; }
            ;;
        *)
            continue
            ;;
    esac
    printf 'PASS: %s\n' "$f"
done < <(git show --name-only --format='' HEAD)

echo "=== 2. Full PHPUnit suite ==="
if php artisan test > "$ARTIFACTS/ship-suite.log" 2>&1; then
    if grep -q 'FAILED' "$ARTIFACTS/ship-suite.log"; then
        bad "suite exited 0 but printed a FAILED line"
    else
        grep -E '^  Tests: ' "$ARTIFACTS/ship-suite.log" | tail -1
        note "php artisan test exited 0"
    fi
else
    grep -E '^   FAILED|^  Tests: ' "$ARTIFACTS/ship-suite.log" | tail -10
    bad "php artisan test exited non-zero"
fi

echo "=== 3. No generated junk is tracked ==="
junk=$(git ls-files | grep -E '(__pycache__|\.pyc$|/\.artifacts/|\.zombiecoder/|^public/reports/)' || true)
if [ -z "$junk" ]; then
    note "no pycache, session dump, verification artifact or nested agent state is committed"
else
    printf '%s\n' "$junk" | head -10
    bad "generated junk is tracked"
fi

echo "=== 4. In-scope tree is clean ==="
dirty=$(git status --porcelain --untracked-files=all -- \
        app config database docs public resources routes storage tests tools \
        composer.json composer.lock \
    | grep -vE '^\?\? tools/verify/\.artifacts/' || true)
if [ -z "$dirty" ]; then
    note "every in-scope change is committed"
else
    printf '%s\n' "$dirty" | head -15
    bad "uncommitted in-scope paths remain"
fi

echo "=== 5. Remote equality ==="
if git fetch -q origin main; then
    head=$(git rev-parse HEAD)
    remote=$(git rev-parse origin/main)
    if [ "$head" = "$remote" ]; then
        note "HEAD == origin/main ($head)"
    else
        bad "HEAD $head != origin/main $remote"
    fi
else
    bad "git fetch origin main failed"
fi

if [ "$fail" -ne 0 ]; then
    echo "RELEASE GATE FAILED"
    exit 1
fi

echo ZC-FULL-SHIP-OK
