#!/usr/bin/env bash
#
# Prove the e-commerce notification system end to end on a throwaway database.
#
# Why a scratch database: this runner places a real order, sends real
# notifications and runs migrate:fresh --seed, which DESTROYS the database it
# runs against. The project .env points at the live dev database (z_evo), so
# the runner copies the credentials, overrides DB_DATABASE and drops the
# scratch database on exit.
#
# What it proves:
#   1. migrate:fresh --seed completes with the new migrations and data;
#   2. tools/verify_notifications.php passes every check (schema, 18 templates,
#      renderer, legacy adoption, order/payment/refund/welcome notifications,
#      mail opt-in, database queue round trip, scheduler, admin form);
#   3. NotificationTemplateSeeder is idempotent: a second run leaves the same
#      catalog and the verifier stays green.
#
# Usage:
#   bash tools/verify_notification_bundle.sh
#
# Prints NOTIFICATION_BUNDLE_OK only on success.

set -Eeuo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

if [ ! -f .env ]; then
    echo "Missing .env in $ROOT" >&2
    exit 10
fi

# Read DB_* from .env into the shell, shell-quoted.
eval "$(php -r '
$path = getcwd() . "/.env";
$values = [];
foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
    $line = trim($line);
    if ($line === "" || str_starts_with($line, "#") || ! str_contains($line, "=")) { continue; }
    [$key, $value] = explode("=", $line, 2);
    $value = trim($value);
    if (strlen($value) >= 2) {
        $first = $value[0]; $last = $value[strlen($value) - 1];
        if (($first === "\"" && $last === "\"") || ($first === "\x27" && $last === "\x27")) {
            $value = substr($value, 1, -1);
        }
    }
    $values[trim($key)] = $value;
}
foreach (["DB_HOST", "DB_PORT", "DB_DATABASE", "DB_USERNAME", "DB_PASSWORD"] as $key) {
    echo $key . "=" . escapeshellarg($values[$key] ?? "") . "\n";
}
')"

REAL_DB="${DB_DATABASE:-}"

if [ -z "$REAL_DB" ]; then
    echo "DB_DATABASE is empty in .env" >&2
    exit 10
fi

SCRATCH_DB="${Z_NOTIFY_DB:-z_evo_notify_check}"

if [ "$SCRATCH_DB" = "$REAL_DB" ]; then
    echo "Refusing to run migrate:fresh against the real database (${REAL_DB})." >&2
    exit 11
fi

export MYSQL_PWD="${DB_PASSWORD:-}"

MYSQL_BIN="${MYSQL_BIN:-mysql}"

drop_scratch() {
    "$MYSQL_BIN" -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" \
        -e "DROP DATABASE IF EXISTS \`${SCRATCH_DB}\`" >/dev/null 2>&1 || true
}

trap drop_scratch EXIT

echo "Scratch database: ${SCRATCH_DB} (real: ${REAL_DB} — untouched)"

drop_scratch
"$MYSQL_BIN" -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" \
    -e "CREATE DATABASE \`${SCRATCH_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

export DB_CONNECTION=mysql
export DB_DATABASE="$SCRATCH_DB"

# LAST SAFETY GATE: prove Laravel itself resolved the scratch database before a
# destructive command runs. If the environment override did not take, abort
# instead of wiping the real database.
ACTIVE_DB="$(php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo (string) config("database.connections.mysql.database");')"

if [ "$ACTIVE_DB" != "$SCRATCH_DB" ]; then
    echo "Laravel resolved DB '${ACTIVE_DB}', expected '${SCRATCH_DB}'. Aborting before migrate:fresh." >&2
    exit 12
fi

echo "— migrate:fresh --seed"
php artisan migrate:fresh --seed --force --no-interaction

echo "— verify (first pass, fresh seed)"
NOTIFY_CHECK_FRESH=1 php tools/verify_notifications.php

echo "— reseed notification templates (idempotency)"
php artisan db:seed --class=NotificationTemplateSeeder --force --no-interaction

echo "— verify (second pass, must stay green)"
php tools/verify_notifications.php

echo "NOTIFICATION_BUNDLE_OK"
