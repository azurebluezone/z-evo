#!/usr/bin/env bash
# Recorded check for contract gc-mw5c1wba: product cards expose Add to Cart
# and Buy Now, and Buy Now lands on a non-empty checkout.
#
# The dev server must be running:
#   php artisan serve --host=127.0.0.1 --port=8000

set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT" || exit 1

BASE_URL="${BASE_URL:-http://127.0.0.1:8000}"
ARTIFACT_DIR="$ROOT/work/audit-shop-admin"
ARTIFACT="$ARTIFACT_DIR/buy-now.log"
mkdir -p "$ARTIFACT_DIR"
: > "$ARTIFACT"
exec > >(tee -a "$ARTIFACT") 2>&1

if ! curl -fsS -o /dev/null --max-time 10 "$BASE_URL/"; then
    echo "BUY-NOW-FAIL server unreachable at $BASE_URL"
    exit 1
fi

NODE_PATH="$ROOT/node_modules" \
BASE_URL="$BASE_URL" \
OUT_DIR="$ARTIFACT_DIR" \
node "$ROOT/tests/verify-buy-now.cjs"
status=$?

if [ "$status" -ne 0 ]; then
    echo "BUY-NOW-FAIL browser checks failed"
    exit 1
fi
