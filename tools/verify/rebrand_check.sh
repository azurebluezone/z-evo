#!/usr/bin/env bash
#
# Rebrand and repository-hygiene gate for contract gc-mwx94w33.
#
# Proves three things against the real tree:
#   1. .gitignore actually ignores agent state (any depth), verification
#      artifacts, session transcripts and python bytecode, while .env.example
#      stays trackable;
#   2. the old "Arafat" brand string is gone from the files this change owns;
#   3. README.md carries the Z Evo-Commerce title and every count it prints
#      equals the count measured from the tree.
#
# Prints ZC-REBRAND-OK only when every stage holds.

set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT" || exit 1

fail=0
ok()  { printf 'PASS: %s\n' "$*"; }
bad() { printf 'FAIL: %s\n' "$*"; fail=1; }

echo "=== 1. .gitignore ignores generated and agent paths ==="
while IFS='|' read -r path why; do
    [ -n "$path" ] || continue
    if git check-ignore -q "$path"; then
        ok "ignored: $path ($why)"
    else
        bad "not ignored: $path ($why)"
    fi
done <<'LIST'
.zombiecoder/SSOT.md|root agent state
public/img/.zombiecoder/SSOT.md|nested agent state under public
public/assets/images/frontend/banner/.zombiecoder/SSOT.md|deeply nested agent state
tools/verify/.artifacts/gate2.log|verification output
public/reports/ses_df8206761ffe2pPizR0d2QvK96.json|opencode session transcript
tools/__pycache__/probe.cpython-312.pyc|python bytecode
LIST

if git check-ignore -q .env.example; then
    bad ".env.example must stay trackable"
else
    ok ".env.example is not ignored"
fi

echo "=== 2. old brand string is gone ==="
# Only the proper-noun brand counts. Historical lowercase filesystem paths
# (/home/sahon/uds/hi/arafat_bak, a changelog row that records an
# `arafat`->`new evo` path fix) are evidence of where real files live and must
# not be rewritten; they are listed as notes below.
if hits=$(grep -rnE '\bArafat\b|আরাফাত' \
        README.md \
        STATUS.md \
        tools/probe_commons.php \
        resources/views/frontend/pages/service.blade.php); then
    printf '%s\n' "$hits"
    bad "old brand string still present"
else
    ok "no Arafat brand string in README.md, STATUS.md, probe_commons.php or the service page"
fi

if notes=$(grep -rniE 'arafat' \
        README.md \
        STATUS.md \
        tools/probe_commons.php \
        resources/views/frontend/pages/service.blade.php); then
    echo "NOTE: historical lowercase path records kept on purpose:"
    printf '%s\n' "$notes" | head -6
fi

echo "=== 3. README carries the new brand ==="
if grep -qE '^# Z E(vo)?-?Commerce' README.md; then
    ok "README title: $(head -1 README.md)"
else
    bad "README title is not Z Evo-Commerce"
fi

echo "=== 4. README counts equal the tree ==="
routes=$(grep -hE '^[[:space:]]*Route::' routes/*.php | wc -l | tr -d ' ')
controllers=$(find app/Http/Controllers -name '*.php' | wc -l | tr -d ' ')
models=$(find app/Models -maxdepth 1 -name '*.php' | wc -l | tr -d ' ')
views=$(find resources/views -name '*.blade.php' | wc -l | tr -d ' ')
migrations=$(find database/migrations -name '*.php' | wc -l | tr -d ' ')

measured="routes=$routes controllers=$controllers models=$models views=$views migrations=$migrations"
stated=$(grep -oE 'measured: routes=[0-9]+ controllers=[0-9]+ models=[0-9]+ views=[0-9]+ migrations=[0-9]+' README.md \
    | head -1 | sed 's/^measured: //')

echo "measured: $measured"
echo "README:   ${stated:-<missing>}"

if [ -n "$stated" ] && [ "$measured" = "$stated" ]; then
    ok "README counts equal the tree"
else
    bad "README counts differ from the tree"
fi

if [ "$fail" -ne 0 ]; then
    echo "REBRAND GATE FAILED"
    exit 1
fi

echo ZC-REBRAND-OK
