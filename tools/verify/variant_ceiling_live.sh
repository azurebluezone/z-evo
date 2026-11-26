#!/usr/bin/env bash
#
# Live-theme variant quantity ceiling gate.
#
# Drives the real ceiling module in Node, checks the FM page loads it before
# the page script, and re-runs the storefront + shop workflow feature tests.
#
# Prints ZC-VARIANT-CEILING-LIVE-OK only when every check passes.

set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT" || exit 1

fail=0
pass() { echo "PASS: $1"; }
bad()  { echo "FAIL: $1"; fail=1; }

MODULE="public/frontend/js/fm-qty-ceiling.js"
PAGE_JS="public/frontend/js/fm-product-detail.js"
FM_VIEW="resources/views/frontend/fm/pages/product-details.blade.php"

echo "=== 1. JS syntax ==="
for f in "$MODULE" "$PAGE_JS"; do
    if node --check "$f" >/dev/null 2>&1; then
        pass "node --check $f"
    else
        bad "node --check $f"
    fi
done

echo "=== 2. Behaviour harness (real module, base 4 -> variant 1 -> reset 4) ==="
if node tools/verify/variant_ceiling_live.mjs >/tmp/zc-variant-ceiling-node.log 2>&1 \
   && grep -qF "VARIANT_CEILING_HARNESS_OK" /tmp/zc-variant-ceiling-node.log; then
    pass "ceiling module keeps the value and the buttons in step"
    grep -E "^PASS: " /tmp/zc-variant-ceiling-node.log | sed 's/^/    /'
else
    bad "ceiling harness (see /tmp/zc-variant-ceiling-node.log)"
    sed -n '1,40p' /tmp/zc-variant-ceiling-node.log | sed 's/^/    /'
fi

echo "=== 3. The FM page loads the controller before the page script ==="
ceiling_line="$(grep -n "script src=.*fm-qty-ceiling\.js" "$FM_VIEW" | head -1 | cut -d: -f1)"
page_line="$(grep -n "script src=.*fm-product-detail\.js" "$FM_VIEW" | head -1 | cut -d: -f1)"

if [ -n "$ceiling_line" ] && [ -n "$page_line" ] && [ "$ceiling_line" -lt "$page_line" ]; then
    pass "fm-qty-ceiling.js loads at line $ceiling_line, before fm-product-detail.js at line $page_line"
else
    bad "ceiling controller is missing or loads after the page script (ceiling=$ceiling_line page=$page_line)"
fi

echo "=== 4. The page script actually uses the controller ==="
if grep -qF "window.FmQtyCeiling.create" "$PAGE_JS"; then
    pass "the stepper is built from window.FmQtyCeiling"
else
    bad "the page script does not create the ceiling controller"
fi
if grep -qF "setQtyCeiling(qty);" "$PAGE_JS"; then
    pass "applyStock moves the ceiling with the stock"
else
    bad "applyStock leaves the ceiling behind"
fi
if grep -qF "setQtyCeiling(baseMaxQty);" "$PAGE_JS"; then
    pass "clearing the pick restores the base ceiling"
else
    bad "the base ceiling is never restored"
fi
if grep -qF "setQtyCeiling(baseMaxQty);" "$PAGE_JS" \
   && [ "$(grep -cF 'setQtyCeiling(baseMaxQty);' "$PAGE_JS")" -ge 2 ]; then
    pass "the base ceiling is applied at load and on reset"
else
    bad "the base ceiling is not published at load"
fi

echo "=== 5. Feature tests (real routes, live database) ==="
if vendor/bin/phpunit \
        tests/Feature/VariantStorefrontUxTest.php \
        tests/Feature/ShopWorkflowAuditTest.php \
        >/tmp/zc-variant-ceiling-tests.log 2>&1; then
    pass "variant storefront + shop workflow feature tests"
    grep -E "^(OK|Tests: )" /tmp/zc-variant-ceiling-tests.log | tail -2 | sed 's/^/    /'
else
    bad "feature tests (see /tmp/zc-variant-ceiling-tests.log)"
    tail -20 /tmp/zc-variant-ceiling-tests.log | sed 's/^/    /'
fi

echo
if [ "$fail" -ne 0 ]; then
    echo "ZC-VARIANT-CEILING-LIVE-FAILED"
    exit 1
fi

echo "ZC-VARIANT-CEILING-LIVE-OK"
