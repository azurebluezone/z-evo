#!/usr/bin/env bash
#
# Release check for DROP-LEGACY-STOCK.
#
# Proves, against the real project and live schema, that:
#   1. the `products.stock` column is gone (migration 2026_11_26_000002 ran)
#   2. no application, view, test or seeder still reads/writes it
#   3. every active variant has a variant_stocks row (the projection is intact)
#   4. the stock-flow feature tests pass on the dropped schema
#
# Prints ZC-DROP-LEGACY-STOCK-OK only when every assertion passes.
#
# Usage: bash tools/verify/drop_legacy_stock.sh

set -uo pipefail

cd "$(dirname "$0")/../.." || exit 1

FAIL=0
pass() { echo "PASS: $1"; }
fail() { echo "FAIL: $1"; FAIL=$((FAIL + 1)); }

echo "=== 1. run the drop migration ==="
if php artisan migrate --force >/tmp/zc-migrate.log 2>&1; then
    pass "php artisan migrate --force"
else
    fail "php artisan migrate --force"
    tail -20 /tmp/zc-migrate.log
fi

echo "=== 2. products.stock column is absent ==="
COL=$(php artisan tinker --execute="echo Schema::hasColumn('products','stock') ? 'HAS' : 'GONE';" 2>/dev/null | tr -d '[:space:]')
if [ "$COL" = "GONE" ]; then
    pass "products.stock column is gone"
else
    fail "products.stock column still present (got: '$COL')"
fi

echo "=== 3. no legacy reads/writes remain ==="
# Property access ->stock, excluding the admin request field and comments.
LEGACY_ACCESS=$(grep -rnE -- "->stock\b" app resources/views tests database/seeders 2>/dev/null \
    | grep -vE "request->stock|input\('stock'|->stock\(\)|^\S+:[0-9]+:\s*(\*|//|#)" || true)
if [ -z "$LEGACY_ACCESS" ]; then
    pass "no ->stock property access in app/views/tests/seeders"
else
    fail "legacy ->stock access remains:"
    echo "$LEGACY_ACCESS"
fi

# SQL column references (products.stock / p.stock), excluding comments.
LEGACY_SQL=$(grep -rnE -- "products\.stock|p\.stock\b" app resources/views tests database/seeders 2>/dev/null \
    | grep -vE "^\S+:[0-9]+:\s*(\*|//|#)" || true)
if [ -z "$LEGACY_SQL" ]; then
    pass "no products.stock SQL reference in app/views/tests/seeders"
else
    fail "legacy products.stock SQL reference remains:"
    echo "$LEGACY_SQL"
fi

echo "=== 4. variant projection is complete ==="
PROJECTION=$(php artisan tinker --execute="
\$missing = DB::table('product_variants')->whereNotExists(function (\$q) {
    \$q->from('variant_stocks')->whereColumn('variant_stocks.variant_id', 'product_variants.id');
})->count();
echo 'missing_stock_rows=' . \$missing;
" 2>/dev/null | tr -d '[:space:]')
if [ "$PROJECTION" = "missing_stock_rows=0" ]; then
    pass "every variant has a variant_stocks row"
else
    fail "variant projection incomplete (got: '$PROJECTION')"
fi

echo "=== 5. stock-flow feature tests ==="
if vendor/bin/phpunit \
        tests/Feature/StockReportReadPathTest.php \
        tests/Feature/ShopWorkflowAuditTest.php \
        tests/Feature/ProductVariantImageStoreTest.php >/tmp/zc-stock-tests.log 2>&1; then
    pass "stock-flow feature tests"
else
    fail "stock-flow feature tests"
    tail -40 /tmp/zc-stock-tests.log
fi

echo
if [ "$FAIL" -eq 0 ]; then
    echo "ZC-DROP-LEGACY-STOCK-OK"
    exit 0
fi

echo "ZC-DROP-LEGACY-STOCK-FAILED ($FAIL check(s))"
exit 1
