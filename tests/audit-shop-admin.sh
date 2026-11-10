#!/usr/bin/env bash
# End-to-end shop + admin audit.
#
# The contract's observable output is the final AUDIT-PASS line. The browser
# work lives in audit-shop-admin.cjs because status, layout width, console
# errors and row counts all have to be read from a real rendered DOM.
#
# The dev server must be running:
#   php artisan serve --host=127.0.0.1 --port=8000
#
# Credentials come from the environment, not from this file:
#   BASE_URL, ADMIN_EMAIL, ADMIN_PASSWORD

set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT" || exit 1

BASE_URL="${BASE_URL:-http://127.0.0.1:8000}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@gmail.com}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-105585}"

ARTIFACT_DIR="$ROOT/work/audit-shop-admin"
ARTIFACT="$ARTIFACT_DIR/audit.log"
mkdir -p "$ARTIFACT_DIR/shots"
: > "$ARTIFACT"
exec > >(tee -a "$ARTIFACT") 2>&1

if ! curl -fsS -o /dev/null --max-time 10 "$BASE_URL/"; then
    echo "AUDIT-FAIL server unreachable at $BASE_URL"
    exit 1
fi

NODE_PATH="$ROOT/node_modules" \
BASE_URL="$BASE_URL" \
ADMIN_EMAIL="$ADMIN_EMAIL" \
ADMIN_PASSWORD="$ADMIN_PASSWORD" \
OUT_DIR="$ARTIFACT_DIR" \
node "$ROOT/tests/audit-shop-admin.cjs"
status=$?

if [ "$status" -ne 0 ]; then
    echo "AUDIT-FAIL browser checks failed"
    exit 1
fi
