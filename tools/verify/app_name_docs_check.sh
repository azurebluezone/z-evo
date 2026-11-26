#!/usr/bin/env bash
#
# Release gate for contract gc-mwx9g8h0.
#
# Proves the application name is Z Evo-Commerce at every layer (config, live
# settings, storefront HTML, source strings), that docs/ keeps only the
# documents that are referenced or current, that the full suite is green, and
# that the release is committed and pushed with no generated junk tracked.
#
# Prints ZC-APPNAME-DOCS-SHIP-OK only when every stage holds.

set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT" || exit 1

ARTIFACTS="tools/verify/.artifacts"
mkdir -p "$ARTIFACTS"

fail=0
ok()  { printf 'PASS: %s\n' "$*"; }
bad() { printf 'FAIL: %s\n' "$*"; fail=1; }

echo "=== 1. Application name at every layer ==="
config_name=$(php artisan tinker --execute="echo config('app.name');" 2>&1 | grep -v DEPRECATED | tail -1)
if [ "$config_name" = "Z Evo-Commerce" ]; then
    ok "config('app.name') = $config_name"
else
    bad "config('app.name') = ${config_name:-<empty>}, expected Z Evo-Commerce"
fi

db_name=$(php artisan tinker --execute="echo DB::table('general_settings')->value('name');" 2>&1 | grep -v DEPRECATED | tail -1)
if [ "$db_name" = "Z Evo-Commerce" ]; then
    ok "general_settings.name = $db_name"
else
    bad "general_settings.name = ${db_name:-<empty>}, expected Z Evo-Commerce"
fi

if grep -qx 'APP_NAME="Z Evo-Commerce"' .env.example; then
    ok '.env.example carries APP_NAME="Z Evo-Commerce"'
else
    bad '.env.example does not carry the new APP_NAME'
fi

echo "=== 2. No old brand strings in application source ==="
if hits=$(grep -rnE 'GS Corporation|Sunnah Heaven|SUNNAH HEAVEN' app config database resources 2>/dev/null); then
    printf '%s\n' "$hits" | head -10
    bad "an old brand string is still printed by the application"
else
    ok "no GS Corporation or Sunnah Heaven string under app/, config/, database/ or resources/"
fi

echo "=== 3. Live storefront prints the new name ==="
home=$(curl -s --max-time 20 http://127.0.0.1:8000/ || true)
if [ -z "$home" ]; then
    bad "http://127.0.0.1:8000/ did not answer"
else
    new_hits=$(printf '%s' "$home" | grep -c 'Z Evo-Commerce' || true)
    old_hits=$(printf '%s' "$home" | grep -ci 'GS Corporation\|Sunnah Heaven' || true)
    if [ "$new_hits" -ge 1 ] && [ "$old_hits" -eq 0 ]; then
        ok "home page: $new_hits new-name hits, 0 old-name hits"
    else
        bad "home page: new=$new_hits old=$old_hits (expected new>=1, old=0)"
    fi
fi

echo "=== 4. docs/ keeps only the referenced and current documents ==="
keep='admin-form-inputs.md
COURIER-API-FEATURES-2026-11-15.md
COURIER-ORDER-FLOW-2026-11-15.md
DEMO-IMAGES.md
DEV-CATALOG-SEED-2026-11-16.md
DEV-ORDER-SEED-2026-11-16.md
IMAGE-INVENTORY-2026-11-12.md
image-inventory.json
LANDING-CAMPAIGNS-2026-11-10.md
LANDING-ORDER-FORM-2026-11-14.md
LANDING-PAGES-2026-11-11.md
LANDING-VERIFICATION-2026-11-12.md
PLAN-notifications-2026-11-16.md
PLAN-reports-analytics-2026-11-16.md
SECURITY-php2shell-2026-10-24.md
SEED-ASSETS-ADORA-2026.md
visitor-analytics-redesign-plan.md
specs/2026-10-28-job-a-admin-pages-sidebar-courier.md
specs/2026-10-28-job-b-homepage-events-3h-cache.md'

while IFS= read -r doc; do
    [ -n "$doc" ] || continue
    if [ -f "docs/$doc" ]; then
        ok "kept: docs/$doc"
    else
        bad "missing kept document: docs/$doc"
    fi
    if grep -Fq "$(basename "$doc")" docs/README.md; then
        ok "indexed: $(basename "$doc")"
    else
        bad "docs/README.md does not index $doc"
    fi
done <<< "$keep"

drop='ADMIN-flash-sale-2026-10-24.md
ADMIN-footer-2026-10-31.md
ADMIN-icons-2026-10-24.md
ADMIN-UX-LOG-2026-10-25.md
admin-materialize-review.html
admin-open-problems-2026-10-12.md
admin-open-problems-2026-10-12-proof.md
audio-script-adorazstyle-brief-2026-11-23-bn.txt
audio-script-kajer-hisab-2026-10-28-bn.txt
audio-script-owner-answers-2026-11-23-bn.txt
audio-script-owner-decision-2026-11-23-bn.txt
audio-script-server-media-2026-11-23-bn.txt
AUDIT-admin-responsive-2026-10-11.md
AUDIT-coupon-2026-10-24.md
AUDIT-courier-2026-10-24.md
AUDIT-fresh-journey-analysis-2026-10-28.md
BACKLOG-2026-10-24.md
COLOUR-pending-2026-10-11.md
FIX-admin-responsive-2026-10-11.md
HEADER-STATUS-2026-10-24.md
IMPL-tracking-t0-2026-10-24.md
IMPL-tracking-t1-2026-10-24.md
IMPL-tracking-t2-2026-10-24.md
IMPL-tracking-t3-2026-10-24.md
IMPL-tracking-t4-2026-10-24.md
PLAN-admin-unified-2026-10-25.md
PLAN-answers.md
PLAN-category-brand.md
PLAN-demo-catalogue.md
PLAN-jannat-delivery-2026-10-24.md
PLAN-master-work.md
PLAN-part3-product-card.md
PLAN-tracking-2026-10-24.md
PRODUCT-PHOTOS-commons-2026-10-11.md
RECOVERY-audit-2026-10-24.md
ROUTE-AUDIT-2026-10-24.md
VARIANT-bundle-2026-10-11.md
WATERMARK-plan-2026-11-01.md'

dropped_left=0
while IFS= read -r doc; do
    [ -n "$doc" ] || continue
    if [ -e "docs/$doc" ]; then
        bad "stale document still present: docs/$doc"
        dropped_left=$((dropped_left + 1))
    fi
done <<< "$drop"
if [ "$dropped_left" -eq 0 ]; then
    ok "all 38 stale session documents are gone"
fi

echo "=== 5. Full PHPUnit suite ==="
if php artisan test > "$ARTIFACTS/app-name-suite.log" 2>&1; then
    if grep -q 'FAILED' "$ARTIFACTS/app-name-suite.log"; then
        bad "suite exited 0 but printed a FAILED line"
    else
        grep -E '^  Tests: ' "$ARTIFACTS/app-name-suite.log" | tail -1
        ok "php artisan test exited 0"
    fi
else
    grep -E '^   FAILED|^  Tests: ' "$ARTIFACTS/app-name-suite.log" | tail -10
    bad "php artisan test exited non-zero"
fi

echo "=== 6. No generated junk is tracked ==="
junk=$(git ls-files | grep -E '(__pycache__|\.pyc$|/\.artifacts/|\.zombiecoder/|^public/reports/)' || true)
if [ -z "$junk" ]; then
    ok "no pycache, session dump, verification artifact or nested agent state is committed"
else
    printf '%s\n' "$junk" | head -10
    bad "generated junk is tracked"
fi

echo "=== 7. In-scope tree is clean ==="
dirty=$(git status --porcelain --untracked-files=all -- \
        app config database docs public resources routes storage tests tools \
        composer.json composer.lock package.json README.md STATUS.md .env.example \
    | grep -vE '__pycache__|\.pyc$|tools/verify/\.artifacts/|\.zombiecoder/|public/reports/' || true)
if [ -z "$dirty" ]; then
    ok "every in-scope change is committed"
else
    printf '%s\n' "$dirty" | head -15
    bad "uncommitted in-scope paths remain"
fi

echo "=== 8. Remote equality ==="
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
    echo "APP NAME AND DOCS GATE FAILED"
    exit 1
fi

echo ZC-APPNAME-DOCS-SHIP-OK
