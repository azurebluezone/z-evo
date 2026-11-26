#!/usr/bin/env bash
#
# Release gate for contract gc-mwxb6p2g: a measured Laravel 10 -> 12
# assessment, not an upgrade.
#
# Stage 1 re-runs composer's own resolver and saves the raw output.
# Stage 2..4 prove the document exists, is indexed, and every blocker it names
# is present in that live resolver output.
# Stage 5..6 prove the application structure claims match the real files and
# that the risky call sites are recorded.
# Stage 7..9 prove the assessment did not break the app: suite green, tree
# clean, local HEAD equals origin/main.
#
# Prints ZC-LARAVEL12-ASSESS-OK only when every stage holds.

set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT" || exit 1

ARTIFACTS="tools/verify/.artifacts"
RESOLVER="$ARTIFACTS/why-not-laravel12.log"
DOC="docs/UPGRADE-laravel12-2026-11-26.md"
mkdir -p "$ARTIFACTS"

fail=0
ok()  { printf 'PASS: %s\n' "$*"; }
bad() { printf 'FAIL: %s\n' "$*"; fail=1; }

echo "=== 1. Live resolver output ==="
composer why-not laravel/framework 12.0 > "$RESOLVER" 2>&1
rc=$?
if [ "$rc" -eq 0 ]; then
    bad "the resolver now accepts laravel/framework 12.0, so an upgrade was applied"
elif grep -qF 'laravel/framework (^10.0)' "$RESOLVER"; then
    ok "the resolver refuses 12.0 (exit $rc) and names the ^10.0 root constraint"
else
    tail -5 "$RESOLVER"
    bad "the resolver output does not name the ^10.0 root constraint"
fi

echo "=== 2. Assessment document and index ==="
if [ -s "$DOC" ]; then
    ok "$DOC exists ($(wc -c < "$DOC" | tr -d ' ') bytes)"
else
    bad "$DOC is missing or empty"
fi

if grep -qF 'UPGRADE-laravel12-2026-11-26.md' docs/README.md; then
    ok "docs/README.md indexes the assessment"
else
    bad "docs/README.md does not index the assessment"
fi

if grep -qF 'ZC-BLOCKERS:BEGIN' "$DOC"; then
    ok "the document carries a machine-readable blocker block"
else
    bad "the document has no ZC-BLOCKERS block"
fi

echo "=== 3. Every named blocker appears in the resolver output ==="
block=$(awk '/ZC-BLOCKERS:BEGIN/{f=1;next}/ZC-BLOCKERS:END/{f=0}f' "$DOC")
count=0
while IFS='|' read -r pkg action; do
    pkg=$(printf '%s' "${pkg:-}" | tr -d '[:space:]')
    [ -z "$pkg" ] && continue
    count=$((count + 1))
    if grep -qF -- "$pkg" "$RESOLVER"; then
        ok "blocker $pkg is in the resolver output (${action:-no action recorded})"
    else
        bad "blocker $pkg is missing from the resolver output"
    fi
done <<< "$block"
if [ "$count" -ge 15 ]; then
    ok "$count blockers parsed from the document"
else
    bad "only $count blockers parsed from the document"
fi

echo "=== 4. Direct blockers match the installed versions ==="
for pkg in laravel/sanctum spatie/laravel-permission nunomaduro/collision; do
    version=$(php -r '
        $l = json_decode(file_get_contents("composer.lock"), true);
        foreach (array_merge($l["packages"], $l["packages-dev"]) as $p) {
            if ($p["name"] === $argv[1]) { echo $p["version"]; return; }
        }
        echo "missing";
    ' "$pkg")
    if [ "$version" != "missing" ] && grep -qF -- "$version" "$RESOLVER"; then
        ok "$pkg $version is the pinned blocker in the resolver output"
    else
        bad "$pkg installed version ($version) is not in the resolver output"
    fi
done

echo "=== 5. Application structure claims match the real files ==="
if [ -f app/Http/Kernel.php ] && grep -qF 'app/Http/Kernel.php: present' "$DOC"; then
    ok "app/Http/Kernel.php exists and the document says it stays"
else
    bad "app/Http/Kernel.php state does not match the document"
fi

if [ -f app/Console/Kernel.php ] && grep -qF 'app/Console/Kernel.php: present' "$DOC"; then
    ok "app/Console/Kernel.php exists and the document says it stays"
else
    bad "app/Console/Kernel.php state does not match the document"
fi

if grep -qF 'Illuminate\Foundation\Application' bootstrap/app.php \
   && grep -qF 'bootstrap/app.php: legacy' "$DOC"; then
    ok "bootstrap/app.php is still the legacy bootstrap and the document says so"
else
    bad "bootstrap/app.php state does not match the document"
fi

echo "=== 6. Code-level risks are recorded ==="
for token in 'app/Http/Kernel.php:67' 'config/sanctum.php:62' 'tests/TestCase.php:23' 'diffInDays' "float('balance')" 'schema:dump'; do
    if grep -qF -- "$token" "$DOC"; then
        ok "the document records $token"
    else
        bad "the document does not record $token"
    fi
done

echo "=== 7. Assessment only: the framework constraint is unchanged ==="
if grep -qF '"laravel/framework": "^10.0"' composer.json; then
    ok "composer.json still requires laravel/framework ^10.0"
else
    bad "composer.json no longer requires laravel/framework ^10.0"
fi

installed=$(php -r '
    $l = json_decode(file_get_contents("composer.lock"), true);
    foreach ($l["packages"] as $p) {
        if ($p["name"] === "laravel/framework") { echo $p["version"]; return; }
    }
    echo "missing";
')
case "$installed" in
    10.*) ok "composer.lock still installs laravel/framework $installed" ;;
    *)    bad "composer.lock installs laravel/framework $installed, expected a 10.x build" ;;
esac

echo "=== 8. Full PHPUnit suite ==="
if php artisan test > "$ARTIFACTS/laravel12-assess-suite.log" 2>&1; then
    if grep -q 'FAILED' "$ARTIFACTS/laravel12-assess-suite.log"; then
        bad "suite exited 0 but printed a FAILED line"
    else
        grep -E '^  Tests: ' "$ARTIFACTS/laravel12-assess-suite.log" | tail -1
        ok "php artisan test exited 0"
    fi
else
    grep -E '^   FAILED|^  Tests: ' "$ARTIFACTS/laravel12-assess-suite.log" | tail -10
    bad "php artisan test exited non-zero"
fi

echo "=== 9. In-scope tree is clean ==="
dirty=$(git status --porcelain --untracked-files=all -- \
        composer.json composer.lock app bootstrap config docs tools/verify README.md STATUS.md \
    | grep -vE 'tools/verify/\.artifacts/' || true)
if [ -z "$dirty" ]; then
    ok "every in-scope change is committed"
else
    printf '%s\n' "$dirty" | head -15
    bad "uncommitted in-scope paths remain"
fi

echo "=== 10. Remote equality ==="
if git fetch -q origin main; then
    head=$(git rev-parse HEAD)
    remote=$(git rev-parse origin/main)
    if [ "$head" = "$remote" ]; then
        ok "HEAD == origin/main ($head)"
    else
        bad "HEAD $head != origin/main $remote"
    fi
else
    bad "git fetch origin main failed"
fi

if [ "$fail" -ne 0 ]; then
    echo "LARAVEL12 ASSESSMENT GATE FAILED"
    exit 1
fi

echo ZC-LARAVEL12-ASSESS-OK
