#!/usr/bin/env bash
#
# Contract gc-mwx5vjl7 - CDP variant end-to-end release gate.
#
# Proves, with a real Chromium session over CDP plus the live database:
#   - an admin can create a product with multiple gallery images
#   - two variants can be given their own photo, price and opening stock
#   - the storefront renders the picked variant's photo, price, stock and ceiling
#   - a guest can buy two units of a specific variant
#   - the admin status dropdown moves the order to Delivered
#   - the stock ledger then drops on_hand/available by exactly two and records
#     a SALE movement linked to the order
#
# Prints ZC-CDP-VARIANT-E2E-OK only when every check passes.

set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT" || exit 1

fail=0
pass() { echo "PASS: $1"; }
bad()  { echo "FAIL: $1"; fail=1; }

mkdir -p tools/verify/.artifacts

echo "=== 1. Syntax ==="

if php -l tools/verify/cdp_variant_e2e_check.php >/dev/null 2>&1; then
    pass "php -l cdp_variant_e2e_check.php"
else
    bad "php -l cdp_variant_e2e_check.php"
fi

if node --check tools/verify/cdp_variant_e2e.mjs >/dev/null 2>&1; then
    pass "node --check cdp_variant_e2e.mjs"
else
    bad "node --check cdp_variant_e2e.mjs"
fi

[ "$fail" -eq 0 ] || exit 1

echo "=== 2. Dev server ==="

SERVER_PID=""

reachable() {
    node -e "fetch('http://127.0.0.1:8000/').then(r=>process.exit(r.status<500?0:1)).catch(()=>process.exit(1))" 2>/dev/null
}

if reachable; then
    pass "the app answers on 127.0.0.1:8000"
else
    php artisan serve --host=127.0.0.1 --port=8000 > tools/verify/.artifacts/serve.log 2>&1 &
    SERVER_PID=$!

    for _ in $(seq 1 40); do
        reachable && break
        sleep 0.25
    done

    if reachable; then
        pass "php artisan serve started for the run"
    else
        bad "the app did not answer on 127.0.0.1:8000"
    fi
fi

cleanup() {
    if [ -n "${SERVER_PID:-}" ]; then
        kill "$SERVER_PID" 2>/dev/null || true
    fi
}
trap cleanup EXIT

[ "$fail" -eq 0 ] || exit 1

echo "=== 3. Browser end-to-end (Chromium over CDP) ==="

RUN_LOG=tools/verify/.artifacts/runner.out
node tools/verify/cdp_variant_e2e.mjs 2>&1 | tee "$RUN_LOG"
RUN_RC=${PIPESTATUS[0]}

if [ "$RUN_RC" -eq 0 ]; then
    pass "the Chromium run finished"
else
    bad "the Chromium run failed (exit $RUN_RC)"
fi

MANIFEST="$(sed -n 's/^CDP_E2E_MANIFEST=//p' "$RUN_LOG" | tail -1)"

if [ -n "$MANIFEST" ] && [ -f "$MANIFEST" ]; then
    pass "the run manifest was written"
else
    bad "the run manifest is missing"
    MANIFEST=""
fi

[ "$fail" -eq 0 ] || exit 1

echo "=== 4. Database, storefront and ledger cross-check ==="

php tools/verify/cdp_variant_e2e_check.php verify "$MANIFEST"
VERIFY_RC=$?

if [ "$VERIFY_RC" -eq 0 ]; then
    pass "the database, the rendered storefront and the ledger agree"
else
    bad "the database cross-check failed (exit $VERIFY_RC)"
fi

[ "$fail" -eq 0 ] || exit 1

echo ZC-CDP-VARIANT-E2E-OK
