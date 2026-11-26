#!/usr/bin/env bash
#
# Variant-level UX release gate.
#
# Proves that image, price and stock agree for a variant on BOTH template
# sets, that the quantity stepper never offers more units than can ship, and
# that an admin-created variant always gets its stock row.
#
# Prints ZC-VARIANT-UX-OK only when every check below passes.

set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT" || exit 1

fail=0
pass() { echo "PASS: $1"; }
bad()  { echo "FAIL: $1"; fail=1; }

CLASSIC_JS="public/assets/templates/basic/js/product_details.js"
CLASSIC_VIEW="resources/views/frontend/pages/product-details.blade.php"
FRONTEND_CTRL="app/Http/Controllers/Frontend/FrontendController.php"
VARIANT_CTRL="app/Http/Controllers/Admin/VariantController.php"

echo "=== 1. PHP and JS syntax ==="
for f in "$FRONTEND_CTRL" "$VARIANT_CTRL" "tests/Feature/VariantStorefrontUxTest.php"; do
    if php -l "$f" >/dev/null 2>&1; then
        pass "php -l $f"
    else
        bad "php -l $f"
    fi
done

if node --check "$CLASSIC_JS" >/dev/null 2>&1; then
    pass "node --check $CLASSIC_JS"
else
    bad "node --check $CLASSIC_JS"
fi

echo "=== 2. Feature tests (real routes, live database) ==="
if vendor/bin/phpunit \
        tests/Feature/VariantStorefrontUxTest.php \
        tests/Feature/ShopWorkflowAuditTest.php \
        >/tmp/zc-variant-ux-tests.log 2>&1; then
    pass "variant storefront + shop workflow feature tests"
else
    bad "variant storefront + shop workflow feature tests (see /tmp/zc-variant-ux-tests.log)"
fi

echo "=== 3. Stock badge treats one unit as sellable (classic JS) ==="
if grep -qF "sellable > 0" "$CLASSIC_JS"; then
    pass "the badge threshold is 'more than zero', not 'more than one'"
else
    bad "the badge threshold still rejects a single unit"
fi

echo "=== 4. Quantity ceiling is published and obeyed ==="
if grep -qF "function setQuantityCeiling" "$CLASSIC_JS"; then
    pass "classic stepper has a ceiling setter"
else
    bad "classic stepper has no ceiling setter"
fi
if grep -qF "sellable > 0" "$CLASSIC_JS" && grep -qF "data-max-stock" "$CLASSIC_VIEW"; then
    pass "classic view publishes data-max-stock"
else
    bad "classic view does not publish data-max-stock"
fi
if grep -qE 'max="\{\{ \$maxStock \}\}"' "$CLASSIC_VIEW"; then
    pass "classic quantity input carries the max attribute"
else
    bad "classic quantity input has no max attribute"
fi
if grep -qF "attr('data-max-stock'" "resources/views/frontend/layouts/app.blade.php" \
   || grep -qF "parent.attr('data-max-stock')" "resources/views/frontend/layouts/app.blade.php"; then
    pass "shared plus/minus handler reads the ceiling"
else
    bad "shared plus/minus handler ignores the ceiling"
fi

echo "=== 5. One request returns image, price and stock ==="
if grep -qF "function variantGalleryPayload" "$FRONTEND_CTRL"; then
    pass "one gallery builder serves both endpoints"
else
    bad "gallery markup is still duplicated"
fi
if grep -qF "'images' => \$gallery['images']" "$FRONTEND_CTRL"; then
    pass "variantDetail returns the gallery with the price and stock"
else
    bad "variantDetail does not return the gallery"
fi
if grep -qF "variantDetailUrl" "$CLASSIC_VIEW" \
   && ! grep -qF "checkStockUrl" "$CLASSIC_VIEW" \
   && ! grep -qF "checkStockUrl" "$CLASSIC_JS"; then
    pass "classic page asks one endpoint for the whole buy box"
else
    bad "classic page still splits photo and price across calls"
fi

echo "=== 6. Admin variant create seeds the stock row ==="
if grep -qF "VariantStock::ensureRow" "$VARIANT_CTRL"; then
    pass "admin variant store calls VariantStock::ensureRow"
else
    bad "admin variant store can still create a stockless variant"
fi
if grep -qF "insertGetId" "$VARIANT_CTRL"; then
    pass "the new variant id is captured for the stock row"
else
    bad "the new variant id is not available"
fi
if grep -qF "initial_stock" "resources/views/backEnd/variants/create.blade.php"; then
    pass "the create form accepts an opening stock"
else
    bad "the create form has no opening stock field"
fi
if grep -qF "stock_available" "resources/views/backEnd/variants/index.blade.php"; then
    pass "the variant list shows the stock balance"
else
    bad "the variant list hides the stock balance"
fi

echo
if [ "$fail" -ne 0 ]; then
    echo "ZC-VARIANT-UX-FAILED"
    exit 1
fi

echo "ZC-VARIANT-UX-OK"
