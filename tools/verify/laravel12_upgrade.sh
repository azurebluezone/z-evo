#!/usr/bin/env bash
#
# Release gate for the Laravel 10 -> 12.74 upgrade (contract gc-mwxcpvsa).
#
# Every stage proves one real property of the upgraded tree. The gate prints
# ZC-LARAVEL12-UPGRADE-OK only when all stages hold; raw output is appended to
# tools/verify/.artifacts/laravel12-upgrade.log.
#
# This gate replaces tools/verify/laravel12_blockers.sh, which measured the
# blockers before the upgrade and now fails by design (it asserts ^10.0).

set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT" || exit 1

ARTIFACTS="tools/verify/.artifacts"
mkdir -p "$ARTIFACTS"
LOG="$ARTIFACTS/laravel12-upgrade.log"
: > "$LOG"

fail=0
ok()  { printf 'PASS: %s\n' "$*" | tee -a "$LOG"; }
bad() { printf 'FAIL: %s\n' "$*" | tee -a "$LOG"; fail=1; }

echo "=== 1. Runtime framework version ===" | tee -a "$LOG"
VERSION="$(php artisan --version 2>&1 | tail -1)"
if printf '%s' "$VERSION" | grep -q 'Laravel Framework 12\.74\.'; then
    ok "$VERSION"
else
    bad "expected Laravel Framework 12.74.x, got: ${VERSION:-<no output>}"
fi

echo "=== 2. composer.json constraints ===" | tee -a "$LOG"
if php -r '
$c = json_decode(file_get_contents("composer.json"), true);
$r = $c["require"] ?? [];
$d = $c["require-dev"] ?? [];
$want = [
    ["require", "laravel/framework", "^12.74"],
    ["require", "laravel/sanctum", "^4.5"],
    ["require", "spatie/laravel-permission", "^6.25"],
    ["require-dev", "nunomaduro/collision", "^8.9"],
    ["require-dev", "phpunit/phpunit", "^11.1"],
];
$bad = [];
foreach ($want as [$section, $package, $expected]) {
    $have = $section === "require" ? ($r[$package] ?? "") : ($d[$package] ?? "");
    if ($have !== $expected) {
        $bad[] = "$package expected $expected, got " . ($have === "" ? "<missing>" : $have);
    }
}
if (isset($r["doctrine/dbal"])) {
    $bad[] = "doctrine/dbal is still required";
}
if (array_key_exists("minimum-stability", $c)) {
    $bad[] = "minimum-stability is still set to " . var_export($c["minimum-stability"], true);
}
if ($bad) {
    fwrite(STDERR, implode("; ", $bad) . "\n");
    exit(1);
}
exit(0);
' >>"$LOG" 2>&1; then
    ok "framework ^12.74, sanctum ^4.5, permission ^6.25, collision ^8.9, phpunit ^11.1; dbal and dev stability gone"
else
    bad "composer.json constraints do not match the upgrade"
fi

echo "=== 3. Installed lock versions ===" | tee -a "$LOG"
if php -r '
$l = json_decode(file_get_contents("composer.lock"), true);
$all = array_merge($l["packages"] ?? [], $l["packages-dev"] ?? []);
$got = [];
foreach ($all as $p) {
    $got[$p["name"]] = $p["version"];
}
$bad = [];
$ranges = [
    "laravel/framework" => ["v12.74.", "12.74.x"],
    "laravel/sanctum" => ["v4.", "4.x"],
    "spatie/laravel-permission" => ["6.", "6.x"],
    "nunomaduro/collision" => ["v8.", "8.x"],
];
foreach ($ranges as $package => [$prefix, $label]) {
    $have = $got[$package] ?? "";
    if (! str_starts_with($have, $prefix)) {
        $bad[] = "$package expected $label, got " . ($have === "" ? "<missing>" : $have);
    }
}
if (! str_starts_with($got["nesbot/carbon"] ?? "", "3.")) {
    $bad[] = "nesbot/carbon expected 3.x, got " . ($got["nesbot/carbon"] ?? "<missing>");
}
if (isset($got["doctrine/dbal"])) {
    $bad[] = "doctrine/dbal is still installed";
}
if ($bad) {
    fwrite(STDERR, implode("; ", $bad) . "\n");
    exit(1);
}
exit(0);
' >>"$LOG" 2>&1; then
    ok "lock installs framework 12.74.x, sanctum 4.x, permission 6.x, collision 8.x, carbon 3.x; no dbal"
else
    bad "composer.lock does not match the upgrade"
fi

echo "=== 4. Upgraded classes resolve ===" | tee -a "$LOG"
if php -r '
require "vendor/autoload.php";
$classes = [
    "Spatie\\Permission\\Middleware\\RoleMiddleware",
    "Spatie\\Permission\\Middleware\\PermissionMiddleware",
    "Spatie\\Permission\\Middleware\\RoleOrPermissionMiddleware",
    "Laravel\\Sanctum\\Http\\Middleware\\AuthenticateSession",
    "Illuminate\\Foundation\\Http\\Middleware\\ValidateCsrfToken",
];
$missing = array_values(array_filter($classes, fn ($class) => ! class_exists($class)));
if ($missing) {
    fwrite(STDERR, "missing: " . implode(", ", $missing) . "\n");
    exit(1);
}
exit(0);
' >>"$LOG" 2>&1; then
    ok "Spatie singular middleware, Sanctum session middleware and ValidateCsrfToken all exist"
else
    bad "an upgraded middleware class does not exist"
fi

echo "=== 5. Kernel, config and test bootstrap edits ===" | tee -a "$LOG"
if grep -qF 'Spatie\Permission\Middleware\RoleMiddleware' app/Http/Kernel.php \
    && ! grep -qF 'Spatie\Permission\Middlewares' app/Http/Kernel.php; then
    ok "app/Http/Kernel.php uses the singular Spatie middleware namespace"
else
    bad "app/Http/Kernel.php still points at the old Spatie namespace"
fi

if grep -qF "'authenticate_session'" config/sanctum.php \
    && grep -qF "'validate_csrf_token'" config/sanctum.php \
    && ! grep -qF "'verify_csrf_token'" config/sanctum.php; then
    ok "config/sanctum.php carries the Sanctum 4 middleware keys"
else
    bad "config/sanctum.php still carries the Sanctum 3 middleware keys"
fi

if grep -qF "'events_enabled'" config/permission.php \
    && grep -qF "'team_resolver'" config/permission.php \
    && grep -qF "'register_octane_reset_listener'" config/permission.php; then
    ok "config/permission.php carries the v6 keys"
else
    bad "config/permission.php is missing v6 keys"
fi

if ! grep -qF 'getDoctrineSchemaManager' tests/TestCase.php; then
    ok "tests/TestCase.php no longer calls the removed Doctrine schema manager"
else
    bad "tests/TestCase.php still calls getDoctrineSchemaManager"
fi

if grep -qF "double('balance', 8, 2)" database/migrations/2023_02_20_022411_create_customers_table.php; then
    ok "the customers migration defines balance explicitly as double(8, 2)"
else
    bad "the customers migration does not define balance as double(8, 2)"
fi

echo "=== 6. Live customers.balance column type ===" | tee -a "$LOG"
BALANCE_TYPE="$(php artisan tinker --execute="echo Illuminate\Support\Facades\Schema::getColumnType('customers','balance');" 2>&1 | tail -1 | tr -d '[:space:]')"
if [ "$BALANCE_TYPE" = "double" ]; then
    ok "customers.balance resolves to double, matching the migration"
else
    bad "customers.balance resolves to '${BALANCE_TYPE:-<none>}', expected double"
fi

echo "=== 7. Security audit ===" | tee -a "$LOG"
AUDIT="$(composer audit --no-interaction 2>&1)"
printf '%s\n' "$AUDIT" >>"$LOG"
if printf '%s' "$AUDIT" | grep -qF 'No security vulnerability advisories found'; then
    ok "composer audit reports no advisories"
else
    bad "composer audit still reports advisories"
fi

echo "=== 8. Full test suite ===" | tee -a "$LOG"
SUITE_LOG="$ARTIFACTS/laravel12-suite.log"
if php artisan test >"$SUITE_LOG" 2>&1; then
    SUMMARY="$(grep -E '^ +Tests: ' "$SUITE_LOG" | tail -1 | sed 's/^ *//')"
    if printf '%s' "$SUMMARY" | grep -qE 'Tests: +[0-9]+ passed'; then
        ok "suite green: ${SUMMARY:-<no summary>}"
    else
        bad "suite exited 0 without a pass summary: ${SUMMARY:-<no summary>}"
    fi
else
    bad "php artisan test failed; see $SUITE_LOG"
fi

echo "=== 9. Upgrade is committed ===" | tee -a "$LOG"
SUBJECT="$(git log -1 --format=%s)"
if printf '%s' "$SUBJECT" | grep -qiE 'laravel 12'; then
    ok "HEAD subject names the upgrade: $SUBJECT"
else
    bad "HEAD subject does not name the upgrade: ${SUBJECT:-<none>}"
fi

if git show HEAD:composer.json 2>/dev/null | grep -qF '"laravel/framework": "^12.74"'; then
    ok "HEAD already contains the ^12.74 constraint"
else
    bad "HEAD does not contain the ^12.74 constraint"
fi

DIRTY="$(git status --porcelain -- README.md composer.json composer.lock app config tests database docs STATUS.md tools/verify/laravel12_upgrade.sh)"
if [ -z "$DIRTY" ]; then
    ok "every upgrade path is committed"
else
    printf '%s\n' "$DIRTY" >>"$LOG"
    bad "uncommitted upgrade paths remain: $(printf '%s' "$DIRTY" | wc -l | tr -d ' ')"
fi

echo "=== 10. README claims ===" | tee -a "$LOG"
if grep -qF 'Laravel 12.74' README.md && ! grep -qF 'Laravel 10' README.md; then
    ok "README names Laravel 12.74 and carries no Laravel 10 claim"
else
    bad "README still claims an older framework version"
fi

echo "=== 11. Remote sync ===" | tee -a "$LOG"
BRANCH_LINE="$(git status -sb | head -1)"
if printf '%s' "$BRANCH_LINE" | grep -q 'origin/main' && ! printf '%s' "$BRANCH_LINE" | grep -qE '\[(ahead|behind)'; then
    ok "branch is in sync with origin/main"
else
    bad "branch is not in sync with origin/main: ${BRANCH_LINE:-<no branch line>}"
fi

echo "=== Result ===" | tee -a "$LOG"
if [ "$fail" -ne 0 ]; then
    echo "LARAVEL12 UPGRADE GATE FAILED" | tee -a "$LOG"
    exit 1
fi

echo "ZC-LARAVEL12-UPGRADE-OK" | tee -a "$LOG"
