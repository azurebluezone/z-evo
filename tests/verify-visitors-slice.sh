#!/usr/bin/env bash
# Recorded acceptance check for the visitor-analytics slice.
#
# It exercises the same paths the admin uses: HTTP with a real admin session,
# the DataTables JSON the orders grid consumes, and a real frontend write that
# carries a blocked phone number. Every check prints PASS/FAIL, and the final
# line is the contract's expected output.
#
# The dev server must be running. Credentials come from the environment so the
# script is not another place a password is hard-coded:
#   BASE_URL, ADMIN_EMAIL, ADMIN_PASSWORD

set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT" || exit 1

BASE_URL="${BASE_URL:-http://127.0.0.1:8000}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@gmail.com}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-105585}"

TMP="$(mktemp -d)"
ARTIFACT_DIR="$ROOT/tests/artifacts"
ARTIFACT="$ARTIFACT_DIR/visitors-slice.log"
TEST_PHONE="01999999992"

mkdir -p "$ARTIFACT_DIR"
: > "$ARTIFACT"
exec > >(tee -a "$ARTIFACT") 2>&1

FAILED=0
fail() {
    FAILED=1
    echo "FAIL $*"
}
pass() {
    echo "PASS $*"
}

cleanup() {
    php artisan tinker --execute="\App\Models\PhoneBlock::where('phone', '${TEST_PHONE}')->delete();" >/dev/null 2>&1
    rm -rf "$TMP"
}
trap cleanup EXIT

echo "== Visitor analytics slice =="
echo "base: $BASE_URL"

# 1. Syntax ---------------------------------------------------------------
LINT_FAILED=0
for file in \
    app/Models/IpGeoCache.php \
    app/Models/PhoneBlock.php \
    app/Models/VisitorMaster.php \
    app/Services/Analytics/GeoIpResolver.php \
    app/Services/Analytics/VisitorFilter.php \
    app/Http/Controllers/Admin/VisitorManageController.php \
    app/Http/Controllers/Admin/PhoneBlockController.php \
    app/Http/Controllers/Admin/OrderController.php \
    app/Http/Middleware/BlockedPhoneGuard.php \
    app/Http/Middleware/VisitorIdentify.php \
    config/services.php
do
    php -l "$file" >/dev/null 2>&1 || { fail "lint $file"; LINT_FAILED=1; }
done
[ "$LINT_FAILED" -eq 0 ] && pass lint

# 2. Feature tests --------------------------------------------------------
if php artisan test --filter=VisitorAnalyticsPhoneBlockTest --no-ansi >"$TMP/tests.log" 2>&1; then
    pass feature-tests
else
    cat "$TMP/tests.log"
    fail feature-tests
fi

# 3. Admin login ----------------------------------------------------------
LOGIN_CODE=$(curl -sS -c "$TMP/cookies" -o "$TMP/login.html" -w '%{http_code}' "$BASE_URL/admin/login" || echo 000)
TOKEN=$(grep -oP 'name="_token" value="\K[^"]+' "$TMP/login.html" 2>/dev/null | head -1)

if [ "$LOGIN_CODE" = "200" ] && [ -n "${TOKEN:-}" ]; then
    LOGIN_POST=$(curl -sS -b "$TMP/cookies" -c "$TMP/cookies" -o /dev/null -w '%{http_code}' \
        -X POST "$BASE_URL/admin/login" \
        --data-urlencode "_token=$TOKEN" \
        --data-urlencode "email=$ADMIN_EMAIL" \
        --data-urlencode "password=$ADMIN_PASSWORD" || echo 000)

    if [ "$LOGIN_POST" = "302" ]; then
        pass admin-login
    else
        fail "admin-login http=$LOGIN_POST"
    fi
else
    fail "admin-login page http=$LOGIN_CODE"
fi

# 4. Analytics page: chart + map containers and payloads ------------------
ANALYTICS_CODE=$(curl -sS -b "$TMP/cookies" -o "$TMP/analytics.html" -w '%{http_code}' "$BASE_URL/admin/visitors/manage" || echo 000)
if [ "$ANALYTICS_CODE" = "200" ]; then
    pass analytics-http
else
    fail "analytics-http $ANALYTICS_CODE"
fi

for needle in 'id="trafficChart"' 'visitorAnalyticsChartData' 'admin-page-visitors-analytics.js' 'id="visitorMap"' 'leaflet.js' 'leaflet.css' 'visitorAnalyticsMapData'; do
    if grep -qF "$needle" "$TMP/analytics.html"; then
        pass "analytics-marker $needle"
    else
        fail "analytics-marker missing $needle"
    fi
done

if python3 - "$TMP/analytics.html" <<'PY'
import json
import re
import sys

html = open(sys.argv[1], encoding='utf-8').read()

def payload(element_id):
    match = re.search(
        r'<script type="application/json" id="%s">(.*?)</script>' % element_id,
        html,
        re.S,
    )
    if not match:
        raise SystemExit('missing %s' % element_id)
    return json.loads(match.group(1))

chart = payload('visitorAnalyticsChartData')
map_data = payload('visitorAnalyticsMapData')

for bucket in ('daily', 'weekly', 'monthly'):
    if bucket not in chart or not isinstance(chart[bucket].get('labels'), list):
        raise SystemExit('chart bucket %s is missing' % bucket)

if 'points' not in map_data or 'source' not in map_data:
    raise SystemExit('map payload is missing points/source')

if map_data['source'] not in ('coordinates', 'divisions'):
    raise SystemExit('map source is not a real source: %s' % map_data['source'])

if map_data['points'] and not all('lat' in p and 'lng' in p for p in map_data['points']):
    raise SystemExit('a map point has no coordinates')

print('chart-daily-total=%s map-points=%s map-source=%s' % (
    chart['daily']['total'], len(map_data['points']), map_data['source']))
PY
then
    pass chart-map-payloads
else
    fail chart-map-payloads
fi

# 5. Single date means that day ------------------------------------------
DATE_CODE=$(curl -sS -b "$TMP/cookies" -o "$TMP/list-date.html" -w '%{http_code}' \
    "$BASE_URL/admin/visitors/list?from=2026-11-02" || echo 000)

if [ "$DATE_CODE" = "200" ] && grep -qF 'Total: 348' "$TMP/list-date.html"; then
    pass single-date-348
else
    fail "single-date expected Total: 348, http=$DATE_CODE"
fi

DATE_ANALYTICS=$(curl -sS -b "$TMP/cookies" -o "$TMP/analytics-date.html" -w '%{http_code}' \
    "$BASE_URL/admin/visitors/manage?from=2026-11-02" || echo 000)

if [ "$DATE_ANALYTICS" = "200" ] && python3 - "$TMP/analytics-date.html" <<'PY'
import json
import re
import sys

html = open(sys.argv[1], encoding='utf-8').read()
match = re.search(
    r'<script type="application/json" id="visitorAnalyticsChartData">(.*?)</script>',
    html,
    re.S,
)
if not match:
    raise SystemExit('missing chart payload')
chart = json.loads(match.group(1))
if chart['daily']['total'] != 348 or chart['daily']['data'] != [348]:
    raise SystemExit('single-date daily series is %r' % chart['daily'])
PY
then
    pass single-date-chart-348
else
    fail single-date-chart-348
fi

# 6. Orders grid phones ---------------------------------------------------
PHONE_ROW=$(php artisan tinker --execute="
\$o = \App\Models\Order::with(['shipping', 'customer'])
    ->whereHas('customer', fn (\$q) => \$q->whereNotNull('phone')->where('phone', '<>', ''))
    ->whereHas('shipping', fn (\$q) => \$q->whereNotNull('phone')->where('phone', '<>', ''))
    ->first();
echo \$o ? \$o->invoice_id . '|' . \$o->shipping->phone . '|' . \$o->customer->phone : '';
" 2>/dev/null | tail -1)

IFS='|' read -r INVOICE ORDER_PHONE CUSTOMER_PHONE <<< "$PHONE_ROW"

if [ -n "${INVOICE:-}" ] && [ -n "${ORDER_PHONE:-}" ] && [ -n "${CUSTOMER_PHONE:-}" ]; then
    DT_CODE=$(curl -sS -b "$TMP/cookies" -o "$TMP/datatable.json" -w '%{http_code}' \
        "$BASE_URL/admin/order/all/datatable?draw=1&start=0&length=20&search%5Bvalue%5D=$INVOICE" || echo 000)

    if [ "$DT_CODE" = "200" ] && python3 - "$TMP/datatable.json" "$INVOICE" "$ORDER_PHONE" "$CUSTOMER_PHONE" <<'PY'
import json
import sys

payload = json.load(open(sys.argv[1], encoding='utf-8'))
invoice, order_phone, customer_phone = sys.argv[2:5]
row = next((r for r in payload.get('data', []) if invoice in r[1]), None)
if row is None:
    raise SystemExit('order %s not found in datatable' % invoice)
if len(row) != 9:
    raise SystemExit('row has %d cells, expected 9' % len(row))
if order_phone not in row[4]:
    raise SystemExit('order phone missing from cell 4')
if customer_phone not in row[5]:
    raise SystemExit('customer phone missing from cell 5')
PY
    then
        pass order-phones
    else
        fail order-phones
    fi
else
    fail "order-phones fixture missing: $PHONE_ROW"
fi

# 7. Global phone block ---------------------------------------------------
if php artisan tinker --execute="
\App\Models\PhoneBlock::updateOrCreate(
    ['phone' => '${TEST_PHONE}'],
    ['original_phone' => '${TEST_PHONE}', 'reason' => 'verify script', 'is_active' => true]
);
" >/dev/null 2>&1; then
    pass phone-block-fixture
else
    fail phone-block-fixture
fi

CSRF=$(grep -oP 'name="_token" value="\K[^"]+' "$TMP/analytics.html" 2>/dev/null | head -1)

if [ -n "${CSRF:-}" ]; then
    BLOCK_CODE=$(curl -sS -b "$TMP/cookies" -H 'Accept: application/json' -o "$TMP/block.json" -w '%{http_code}' \
        -X POST "$BASE_URL/customer/signin" \
        --data-urlencode "_token=$CSRF" \
        --data-urlencode "phone=$TEST_PHONE" \
        --data-urlencode 'password=irrelevant' || echo 000)

    if [ "$BLOCK_CODE" = "403" ] && grep -qF '"blocked"' "$TMP/block.json"; then
        pass phone-block-403
    else
        fail "phone-block expected 403, got $BLOCK_CODE: $(cat "$TMP/block.json" 2>/dev/null | head -c 200)"
    fi
else
    fail phone-block-csrf-missing
fi

# Result ------------------------------------------------------------------
echo
if [ "$FAILED" -eq 0 ]; then
    echo "VERIFY-PASS chart=yes map=yes single-date=348 phones=yes phone-block=403"
    exit 0
fi

echo "VERIFY-FAIL one or more checks failed"
exit 1
