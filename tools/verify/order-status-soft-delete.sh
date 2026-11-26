#!/usr/bin/env bash
#
# Release check for two fixes:
#   1. the per-row order status dropdown actually submits (delegated change
#      listener -> AJAX POST -> order_status saved)
#   2. products uses a real soft delete (deleted_at + SoftDeletes) and raw
#      queries do not leak trashed rows into the cart or admin pickers
#
# Prints ZC-ORDER-STATUS-SOFT-DELETE-OK only when every assertion passes.
#
# Usage: bash tools/verify/order-status-soft-delete.sh

set -uo pipefail

cd "$(dirname "$0")/../.." || exit 1

FAIL=0
pass() { echo "PASS: $1"; }
fail() { echo "FAIL: $1"; FAIL=$((FAIL + 1)); }
need_file() { [ -f "$1" ] && pass "file exists: $1" || fail "missing file: $1"; }
need_grep() { grep -qF -- "$2" "$1" && pass "$3" || fail "$3"; }

JS="public/admin/assets/js/admin-page-order-index.js"
ROW="resources/views/backEnd/order/_row.blade.php"
IDX="resources/views/backEnd/order/index.blade.php"
PRODUCT="app/Models/Product.php"
PC="app/Http/Controllers/Admin/ProductController.php"
VC="app/Http/Controllers/Admin/VariantController.php"
SC="app/Http/Controllers/Frontend/ShoppingController.php"
MIG=$(ls database/migrations/*_add_deleted_at_to_products_table.php 2>/dev/null | head -1)

need_file "$JS"
need_file "$ROW"
need_file "$IDX"
need_file "$PRODUCT"
need_file "$PC"
need_file "$VC"
need_file "$SC"

if [ -n "$MIG" ]; then
    pass "migration present: $MIG"
else
    fail "soft-delete migration missing"
fi

# --- 1. per-row status dropdown -------------------------------------------
if node --check "$JS" >/dev/null 2>&1; then
    pass "order index JS parses"
else
    fail "order index JS syntax error"
fi

need_grep "$JS" "on('change', 'select.row-status-select'" "delegated change listener for the row dropdown"
need_grep "$JS" "closest('form.row-status-form').trigger('submit')" "change listener submits the row form"
need_grep "$JS" "on('submit', 'form.row-status-form'" "AJAX submit handler still present"
need_grep "$ROW" 'class="d-inline row-status-form"' "row form class matches the JS selector"
need_grep "$ROW" 'class="row-status-select"' "row select class matches the JS selector"
need_grep "$ROW" 'name="order_ids[]"' "row form posts order_ids[]"
need_grep "$IDX" "filemtime(public_path('admin/assets/js/admin-page-order-index.js'))" "script URL is cache-busted"

# --- 2. products soft delete ----------------------------------------------
if [ -n "$MIG" ]; then
    need_grep "$MIG" 'softDeletes()' "migration adds softDeletes()"
fi

need_grep "$PRODUCT" 'use Illuminate\Database\Eloquent\SoftDeletes;' "Product imports SoftDeletes"
need_grep "$PRODUCT" 'use SoftDeletes;' "Product uses the SoftDeletes trait"
need_grep "$PC" 'findOrFail($request->hidden_id)' "product destroy is null-safe"
need_grep "$SC" "whereNull('deleted_at')" "cart/product lookup excludes trashed rows"
need_grep "$VC" "whereNull('deleted_at')" "variant product pickers exclude trashed rows"

for f in "$PRODUCT" "$PC" "$VC" "$SC" "$MIG"; do
    if php -l "$f" >/dev/null 2>&1; then
        pass "php -l $f"
    else
        fail "php -l $f"
    fi
done

if php tools/verify/soft_delete_products.php; then
    pass "soft-delete round trip"
else
    fail "soft-delete round trip"
fi

mkdir -p /tmp/opencode
if php artisan test --filter=AdminOrderDatatableTest >/tmp/opencode/zc-order-datatable-test.log 2>&1; then
    pass "AdminOrderDatatableTest"
else
    fail "AdminOrderDatatableTest (see /tmp/opencode/zc-order-datatable-test.log)"
fi

if [ "$FAIL" -eq 0 ]; then
    echo "ZC-ORDER-STATUS-SOFT-DELETE-OK"
    exit 0
fi

echo "FAILURES: $FAIL"
exit 1
