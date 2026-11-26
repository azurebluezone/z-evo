#!/usr/bin/env bash
#
# Release gate for contract gc-mwx9uvgx.
#
# Proves the session transcripts are out of the public web root, the composer
# and npm metadata is no longer scaffold, the settings seeder applies its new
# default to an existing install, no plaintext .env backup is left in the root,
# the full suite is green, and the release is committed and pushed.
#
# Prints ZC-CONSISTENCY-PUSH-OK only when every stage holds.

set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT" || exit 1

ARTIFACTS="tools/verify/.artifacts"
mkdir -p "$ARTIFACTS"

fail=0
ok()  { printf 'PASS: %s\n' "$*"; }
bad() { printf 'FAIL: %s\n' "$*"; fail=1; }

echo "=== 1. Session transcripts are out of the public web root ==="
if [ -d public/reports ]; then
    bad "public/reports still exists in the web root"
else
    ok "public/reports is gone from the web root"
fi

count=$(find storage/app/reports -maxdepth 1 -type f 2>/dev/null | wc -l | tr -d ' ')
if [ "$count" -ge 4 ]; then
    ok "storage/app/reports holds $count files"
else
    bad "storage/app/reports holds only $count files"
fi

code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 \
    "http://127.0.0.1:8000/reports/ses_df8206761ffe2pPizR0d2QvK96.json" || echo 000)
if [ "$code" = "404" ] || [ "$code" = "403" ]; then
    ok "the transcript URL now answers HTTP $code"
else
    bad "the transcript URL still answers HTTP $code"
fi

echo "=== 2. Composer metadata and lock ==="
if grep -q '"name": "laravel/laravel"' composer.json; then
    bad "composer.json still carries the laravel/laravel scaffold name"
else
    ok "composer.json name is $(php -r '$j=json_decode(file_get_contents("composer.json"),true); echo $j["name"];')"
fi

if grep -q '"description": "The Laravel Framework."' composer.json; then
    bad "composer.json still carries the scaffold description"
else
    ok "composer.json description is the project description"
fi

if composer validate --no-check-publish --no-interaction > "$ARTIFACTS/composer-validate.log" 2>&1; then
    ok "composer validate --no-check-publish passed"
else
    tail -5 "$ARTIFACTS/composer-validate.log"
    bad "composer validate failed"
fi

php_floor=$(php -r '$j=json_decode(file_get_contents("composer.json"),true); echo $j["require"]["php"] ?? "?";')
if [ "$php_floor" = "^8.4" ]; then
    ok "composer require.php = $php_floor (matches the 8.4.1 dependency floor)"
else
    bad "composer require.php = $php_floor, expected ^8.4"
fi

echo "=== 3. npm metadata is real ==="
if grep -q 'no test specified' package.json; then
    bad "package.json still has the scaffold test script"
else
    ok "package.json has no failing scaffold test script"
fi

if php -r '$j=json_decode(file_get_contents("package.json"),true); exit(($j["private"]??false)===true?0:1);'; then
    ok "package.json is private"
else
    bad "package.json is not private"
fi

if grep -q 'azurebluezone/z-evo' package.json; then
    ok "package.json repository points at the real remote"
else
    bad "package.json repository is not the real remote"
fi

echo "=== 4. Settings seeder reaches an existing row ==="
if grep -q 'GeneralSetting::updateOrCreate' database/seeders/GeneralSettingSeeder.php; then
    ok "GeneralSettingSeeder uses updateOrCreate"
else
    bad "GeneralSettingSeeder still uses firstOrCreate"
fi

live_name=$(php artisan tinker --execute="echo DB::table('general_settings')->value('name');" 2>&1 | grep -v DEPRECATED | tail -1)
if [ "$live_name" = "Z Evo-Commerce" ]; then
    ok "general_settings.name = $live_name"
else
    bad "general_settings.name = ${live_name:-<empty>}"
fi

echo "=== 5. No plaintext .env backup in the root ==="
if compgen -G ".env.bak.*" > /dev/null; then
    bad "a .env.bak file is still present"
else
    ok "no .env.bak file in the project root"
fi

echo "=== 6. Full PHPUnit suite ==="
if php artisan test > "$ARTIFACTS/consistency-suite.log" 2>&1; then
    if grep -q 'FAILED' "$ARTIFACTS/consistency-suite.log"; then
        bad "suite exited 0 but printed a FAILED line"
    else
        grep -E '^  Tests: ' "$ARTIFACTS/consistency-suite.log" | tail -1
        ok "php artisan test exited 0"
    fi
else
    grep -E '^   FAILED|^  Tests: ' "$ARTIFACTS/consistency-suite.log" | tail -10
    bad "php artisan test exited non-zero"
fi

echo "=== 7. Dependency audit (recorded, framework upgrade is a separate contract) ==="
audit_json=$(composer audit --format=json 2>/dev/null || true)
advisories=$(printf '%s' "$audit_json" | php -r '$j=json_decode(stream_get_contents(STDIN),true); $n=0; foreach(($j["advisories"]??[]) as $list){ $n += count($list);} echo $n;')
echo "WARN: composer audit reports $advisories advisories against laravel/framework; see README Known framework risk"
if grep -q 'Known framework risk' README.md; then
    ok "the framework risk is disclosed in README"
else
    bad "README does not disclose the framework risk"
fi

echo "=== 8. No generated junk is tracked ==="
junk=$(git ls-files | grep -E '(__pycache__|\.pyc$|/\.artifacts/|\.zombiecoder/|^public/reports/|^storage/app/reports/)' || true)
if [ -z "$junk" ]; then
    ok "no pycache, session dump, verification artifact or agent state is committed"
else
    printf '%s\n' "$junk" | head -10
    bad "generated junk is tracked"
fi

echo "=== 9. In-scope tree is clean ==="
dirty=$(git status --porcelain --untracked-files=all -- \
        .gitignore app composer.json composer.lock database docs package.json public resources storage tools README.md STATUS.md \
    | grep -vE '__pycache__|\.pyc$|tools/verify/\.artifacts/|\.zombiecoder/|public/reports/|storage/app/reports/' || true)
if [ -z "$dirty" ]; then
    ok "every in-scope change is committed"
else
    printf '%s\n' "$dirty" | head -15
    bad "uncommitted in-scope paths remain"
fi

echo "=== 10. Remote equality ==="
if git fetch -q origin main; then
    head=$(git rev-parse HEAD)
    remote=$(git rev-parse origin/main)
    if [ "$head" = "$remote" ]; then
        ok "HEAD == origin/main ($head)"
    else
        bad "HEAD $head != origin/main $remote"
    fi
else
    bad "git fetch origin main failed"
fi

if [ "$fail" -ne 0 ]; then
    echo "CONSISTENCY GATE FAILED"
    exit 1
fi

echo ZC-CONSISTENCY-PUSH-OK
