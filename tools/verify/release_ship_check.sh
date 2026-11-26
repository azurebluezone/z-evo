#!/usr/bin/env bash
#
# Contract gc-mwx7bjha - release gate for the variant / stock slice.
#
# Proves, in this order:
#   1. the touched PHP and JS files still parse
#   2. the companion-attribute regression test plus the two variant suites pass
#   3. no temporary debug script is left behind
#   4. every task path is committed (nothing staged, nothing dirty)
#   5. the commit sits on the remote branch tip
#
# Prints ZC-RELEASE-PUSHED-OK only when all of it holds.

set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT" || exit 1

ARTIFACTS=tools/verify/.artifacts
mkdir -p "$ARTIFACTS"

fail=0
pass() { echo "PASS: $1"; }
bad()  { echo "FAIL: $1"; fail=1; }

# The slice this contract ships, relative to the repo root.
SHIP_PATHS=(
    app/Http/Controllers/Admin/ProductController.php
    app/Http/Controllers/Admin/VariantController.php
    app/Http/Controllers/Frontend/FrontendController.php
    app/Models/Product.php
    app/Models/VariantStock.php
    app/Models/Campaign.php
    app/Services/InventoryLedger.php
    app/Services/Admin/AdminDashboardService.php
    app/Support/SeedStock.php
    app/Console/Commands/SeedDemoProductsCommand.php
    database/migrations/2026_11_26_000001_add_deleted_at_to_products_table.php
    database/migrations/2026_11_26_000002_drop_stock_from_products_table.php
    public/assets/templates/basic/js/product_details.js
    public/frontend/js/fm-product-detail.js
    public/frontend/js/fm-qty-ceiling.js
    resources/views/backEnd/product/_row.blade.php
    resources/views/backEnd/product/edit.blade.php
    resources/views/backEnd/product/price_edit.blade.php
    resources/views/backEnd/reports/stock.blade.php
    resources/views/backEnd/variants/create.blade.php
    resources/views/backEnd/variants/index.blade.php
    resources/views/frontend/fm/layouts.blade.php
    resources/views/frontend/fm/pages/product-details.blade.php
    resources/views/frontend/layouts/app.blade.php
    resources/views/frontend/layouts/pages/details.blade.php
    resources/views/frontend/pages/product-details.blade.php
    tests/Feature/ProductVariantImageStoreTest.php
    tests/Feature/ShopWorkflowAuditTest.php
    tests/Feature/StockReportFilterTest.php
    tests/Feature/StockReportReadPathTest.php
    tests/Feature/VariantCompanionAttributePillTest.php
    tests/Feature/VariantStorefrontUxTest.php
    tools/verify/backups/products-before-stock-drop.sql
    tools/verify/cdp_variant_e2e.sh
    tools/verify/cdp_variant_e2e.mjs
    tools/verify/cdp_variant_e2e_check.php
    tools/verify/drop_legacy_stock.sh
    tools/verify/order-status-soft-delete.sh
    tools/verify/release_ship_check.sh
    tools/verify/soft_delete_products.php
    tools/verify/variant_ceiling_live.mjs
    tools/verify/variant_ceiling_live.sh
    tools/verify/variant_ux_audit.sh
)

echo "=== 1. Syntax ==="

for file in \
    tests/Feature/VariantCompanionAttributePillTest.php \
    tests/Feature/VariantStorefrontUxTest.php \
    tests/Feature/ProductVariantImageStoreTest.php \
    app/Http/Controllers/Frontend/FrontendController.php \
    app/Http/Controllers/Admin/ProductController.php \
    app/Http/Controllers/Admin/VariantController.php \
    app/Services/InventoryLedger.php \
    app/Support/SeedStock.php
do
    if php -l "$file" >/dev/null 2>&1; then
        pass "php -l $file"
    else
        bad "php -l $file"
    fi
done

for file in \
    public/frontend/js/fm-product-detail.js \
    public/frontend/js/fm-qty-ceiling.js \
    public/assets/templates/basic/js/product_details.js \
    tools/verify/cdp_variant_e2e.mjs \
    tools/verify/variant_ceiling_live.mjs
do
    if node --check "$file" >/dev/null 2>&1; then
        pass "node --check $file"
    else
        bad "node --check $file"
    fi
done

[ "$fail" -eq 0 ] || exit 1

echo "=== 2. Variant regression tests ==="

php artisan test \
    tests/Feature/VariantCompanionAttributePillTest.php \
    tests/Feature/VariantStorefrontUxTest.php \
    tests/Feature/ProductVariantImageStoreTest.php \
    2>&1 | tee "$ARTIFACTS/release-tests.log" | tail -25
TEST_RC=${PIPESTATUS[0]}

if [ "$TEST_RC" -eq 0 ]; then
    pass "companion pill + variant storefront + product image suites"
else
    bad "variant regression tests (exit $TEST_RC)"
fi

[ "$fail" -eq 0 ] || exit 1

echo "=== 3. Temporary files ==="

LEFTOVER="$(ls tools/verify/.tmp_* 2>/dev/null)"
if [ -z "$LEFTOVER" ]; then
    pass "no temporary debug scripts under tools/verify"
else
    bad "temporary debug scripts still present: $LEFTOVER"
fi

if [ -z "$(git ls-files -- 'tools/verify/.tmp_*' 'tools/verify/.artifacts/*')" ]; then
    pass "no debug or artifact file is tracked"
else
    bad "a debug or artifact file is tracked by git"
fi

echo "=== 4. Committed slice ==="

DIRTY="$(git status --porcelain --untracked-files=all -- "${SHIP_PATHS[@]}")"
if [ -z "$DIRTY" ]; then
    pass "every task path is committed and the index is clean for it"
else
    bad "uncommitted task paths:"
    echo "$DIRTY"
fi

if git ls-files --error-unmatch tests/Feature/VariantCompanionAttributePillTest.php >/dev/null 2>&1; then
    pass "the new companion-attribute test is tracked"
else
    bad "the new companion-attribute test is not tracked"
fi

if git log -1 --format=%s | grep -qi 'variant'; then
    pass "the head commit is the variant/stock release commit"
else
    bad "the head commit subject does not mention the variant work"
fi

echo "=== 5. Remote ==="

BRANCH="$(git rev-parse --abbrev-ref HEAD)"
if [ "$BRANCH" != "main" ]; then
    bad "expected branch main, on $BRANCH"
fi

if timeout 45 git fetch --quiet origin main 2>/dev/null; then
    pass "git fetch origin main"
else
    bad "git fetch origin main failed"
fi

LOCAL="$(git rev-parse HEAD)"
REMOTE="$(git rev-parse origin/main 2>/dev/null)"

if [ -n "$REMOTE" ] && [ "$LOCAL" = "$REMOTE" ]; then
    pass "local HEAD equals origin/main ($LOCAL)"
else
    bad "local HEAD $LOCAL differs from origin/main ${REMOTE:-<missing>}"
fi

[ "$fail" -eq 0 ] || { echo "RELEASE_CHECK_FAILED"; exit 1; }

echo ZC-RELEASE-PUSHED-OK
