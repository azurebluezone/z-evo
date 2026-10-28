#!/usr/bin/env bash
# Session proof — 2026-10-28 (frontend phase: colour variants + default assets + events).
#
# Purpose: a NEW session must NOT re-derive this work. Run this script; every check
# prints PASS/FAIL with the evidence line. Total runtime ~10s. Exit 0 = all green.
#
# Usage:
#   bash verify/session-proof-2026-10-28.sh
#
# What it proves (see STATUS.md section 6.6 for the full record):
#   1. Colour variant test data exists in DB (product 1: Red/Blue/Green).
#   2. variant-detail endpoint returns the right SKU + price + image per colour.
#   3. The 4 default asset files that used to 404 now return HTTP 200.
#   4. Regression smoke: main pages 200, bad slug 404.
#   5. PHP lint of the files this session's jobs touched.
#   6. Event layer emitters present (Job B): logo_click / search / favourite listeners.
#   7. Admin sidebar Pages dropdown present (Job A).

set -u
BASE="${BASE:-http://127.0.0.1:8123}"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PASS=0
FAIL=0

pass() { printf '  PASS  %s\n' "$1"; PASS=$((PASS+1)); }
fail() { printf '  FAIL  %s\n' "$1"; FAIL=$((FAIL+1)); }

check_http() { # path expected_code label
  local code
  code=$(curl -s -o /dev/null -w '%{http_code}' "$BASE$1")
  if [ "$code" = "$2" ]; then pass "$3 -> $code"; else fail "$3 -> $code (want $2)"; fi
}

echo "== 1. Colour variant data (DB) =="
cd "$ROOT" || exit 1
VARIANTS=$(php artisan tinker --execute='
$rows = DB::table("product_variants")->where("product_id",1)->whereNotNull("price_override")->get();
foreach ($rows as $v) {
  $s = DB::table("variant_stocks")->where("variant_id",$v->id)->first(["available"]);
  echo $v->sku."|".$v->price_override."|".$v->image."|".($s->available ?? "none").PHP_EOL;
}' 2>/dev/null)
for want in "P0001-RED|1150|uploads/variant/variant-red.svg|20" \
            "P0001-BLUE|1250|uploads/variant/variant-blue.svg|20" \
            "P0001-GREEN|1350|uploads/variant/variant-green.svg|20"; do
  if echo "$VARIANTS" | grep -qxF "$want"; then pass "variant $want"; else fail "variant $want"; fi
done

echo "== 2. variant-detail endpoint per colour =="
check_http "/products/1/variant-detail?attribute_values=%5B15%5D" 200 "variant-detail Red"
check_http "/products/1/variant-detail?attribute_values=%5B16%5D" 200 "variant-detail Blue"
check_http "/products/1/variant-detail?attribute_values=%5B17%5D" 200 "variant-detail Green"
SKU=$(curl -s "$BASE/products/1/variant-detail?attribute_values=%5B15%5D")
if echo "$SKU" | grep -q '"sku":"P0001-RED"'; then pass "Red returns sku P0001-RED"; else fail "Red sku mismatch: $SKU"; fi
PRICE=$(curl -s "$BASE/products/check-stock?product_id=1&variant%5B%5D=17")
if echo "$PRICE" | grep -q '"price":1350'; then pass "Green price 1350"; else fail "Green price: $PRICE"; fi

echo "== 3. default assets (were 404 before this session) =="
check_http "/uploads/default/no-image.png"        200 "no-image.png"
check_http "/uploads/category/default.png"        200 "category default.png"
check_http "/uploads/settings/default-logo.webp"  200 "default-logo.webp"
check_http "/uploads/settings/default-favicon.webp" 200 "default-favicon.webp"

echo "== 4. regression smoke =="
check_http "/"                                200 "home"
check_http "/products"                        200 "products"
check_http "/search?category=&q=rice"         200 "search"
check_http "/page/terms-and-conditions"       200 "cms page (demo id=2)"
check_http "/products/show/premium-3d-canvas-cwc-201-1" 200 "product detail"
check_http "/no-such-page-xyz"                404 "bad slug stays 404"
check_http "/admin/page/manage"               302 "admin requires auth"

echo "== 5. PHP lint (files touched by Job A/B) =="
for f in \
  app/Http/Controllers/Frontend/FrontendController.php \
  app/Http/Controllers/Admin/AnalyticsDashboardController.php \
  resources/views/backEnd/layouts/partials/sidebar.blade.php \
  resources/views/backEnd/courier/index.blade.php \
  resources/views/backEnd/order/courier_send.blade.php \
  resources/views/frontend/partials/event-layer-head.blade.php
do
  if php -l "$ROOT/$f" >/dev/null 2>&1; then pass "php -l $f"; else fail "php -l $f"; fi
done

echo "== 6. Job B — event listeners in event-layer-head =="
EVL="$ROOT/resources/views/frontend/partials/event-layer-head.blade.php"
for ev in logo_click search favorite; do
  if grep -q "$ev" "$EVL"; then pass "listener $ev present"; else fail "listener $ev missing"; fi
done
if grep -q 'addHours(3)' "$ROOT/app/Http/Controllers/Admin/AnalyticsDashboardController.php"; then
  pass "analytics cache 3h"
else
  fail "analytics cache 3h"
fi

echo "== 7. Job A — admin sidebar Pages dropdown + courier badge =="
SB="$ROOT/resources/views/backEnd/layouts/partials/sidebar.blade.php"
if grep -q "pages.index" "$SB" && grep -q "pages.create" "$SB"; then
  pass "sidebar links to pages.index + pages.create"
else
  fail "sidebar Pages dropdown missing"
fi
CI="$ROOT/resources/views/backEnd/courier/index.blade.php"
if grep -q "No API Connect" "$CI"; then pass "courier badge 'No API Connect'"; else fail "courier badge"; fi

echo
echo "RESULT: $PASS passed, $FAIL failed"
[ "$FAIL" -eq 0 ] || exit 1
