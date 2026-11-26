# Laravel 10 to 12 upgrade assessment

Measured 2026-11-26 against the live tree at `~/Desktop/new evo`, HEAD
`199f5b0`. Scope: assessment only. No dependency and no application code is
changed by this document.

The plan below is deliberately a two-stage move (Laravel 10 -> 11 structure is
kept, framework jumps to 12.74 in one Composer run). Laravel 11 and 12 both
support the Laravel 10 application structure, so there is no forced rewrite of
the kernels.

Evidence commands and their raw output:

- `composer why-not laravel/framework 12.0` -> exit 1, saved at
  `tools/verify/.artifacts/why-not-laravel12.log`
- `composer check-platform-reqs` -> every requirement success
- `composer outdated --direct` (installed vs available)
- `composer audit` -> five advisories on `laravel/framework`; every listed fix
  is 12.71+ / 13.40+, so only a 12.x or 13.x framework can close them

## 1. Where the upgrade actually stops

The resolver refuses `laravel/framework 12.0` for one root reason and three
direct package constraints. Everything else it prints is a transitive pin that
moves on its own once the direct packages move.

| Package | Installed | Declared in `composer.json` | Why 12.0 is refused | Target |
| --- | --- | --- | --- | --- |
| `zevo/commerce` (root) | `dev-main` | `laravel/framework: ^10.0` at line 14 | root constraint | `^12.74` |
| `laravel/sanctum` | `v3.3.3` | `^3.2` at line 15 | requires `illuminate/* ^9.21\|^10.0` | `^4.0` (4.5.0 requires illuminate `^12.0\|^13.0`, PHP `^8.2`) |
| `spatie/laravel-permission` | `5.11.1` | `^5.10` at line 20 | requires `illuminate/* ^7.0...^10.0` | `^6.0` (6.25.0 supports illuminate `^8.12...^13.0`) |
| `nunomaduro/collision` | `v7.12.0` | `^7.0` at line 29 | `conflicts laravel/framework >=11.0.0` | `^8.1` (8.9.6 requires PHP `^8.2`, termwind `^2.4.1`) |

Transitive pins that move with the three targets above (all appear in the
resolver output):

| Package | Installed | Moves to | Because |
| --- | --- | --- | --- |
| `laravel/prompts` | `v0.1.25` | `^0.3` | collision 8 / framework 12 |
| `nesbot/carbon` | `2.73.0` | `^3.8.4` | framework 12 |
| `nunomaduro/termwind` | `v1.17.0` | `^2.0` | collision 8 |
| `symfony/console`, `error-handler`, `finder`, `http-foundation`, `http-kernel`, `mailer`, `mime`, `process`, `routing`, `uid`, `var-dumper` | `6.4.x` | `^7.2` | framework 12 |

`composer.json:73-74` sets `minimum-stability: dev` with
`prefer-stable: true`, and the lock pins `laravel/framework` to the
`10.x-dev` branch (`f4488e9568bcf779a8d290711f84013c1a6c6bed`). The upgrade
must pin a tagged release instead: `^12.74` is the newest 12.x line and the
first range that closes all five advisories. Drop the dev pin if no other
package needs it.

## 2. Not blockers (measured, do not spend time on them here)

These were suspected earlier and are not constraints:

| Package | Installed | Laravel 12 support |
| --- | --- | --- |
| `hardevine/shoppingcart` | 3.4 | requires illuminate `^12` (lock) |
| `laravel/ui` | v4.6.3 | requires illuminate `^12` (lock); `Auth::routes()` keeps working |
| `spatie/laravel-medialibrary` | 11.23.9 | requires illuminate `^12` (lock) |
| `spatie/laravel-html` | 3.13.0 | requires illuminate `^12` (lock) |
| `laravel/tinker` | v2.11.1 | requires illuminate `^12` (lock) |
| `stevebauman/location` | v7.8.2 | requires illuminate `^12` (lock) |
| `barryvdh/laravel-debugbar` | v3.16.5 | requires illuminate `^12` (lock) |
| `spatie/laravel-ignition` | 2.9.1 | requires illuminate `^12` (lock) |
| `brian2694/laravel-toastr` | 5.61 | no upper bound; needs a runtime smoke test, not a constraint change |
| `doctrine/dbal` | 3.10.7 | not needed from Laravel 11 on; remove after the upgrade, not before |
| `intervention/image` | 2.7.2 | no Laravel coupling; 4.4.1 is available, separate cleanup |
| `shurjopayv2/laravel8` | not installed | zero matches in `composer.lock`; it is not part of this tree |

## 3. Code-level hits that need an edit or a test

### 3.1 Application structure (measured, keep it)

```
app/Http/Kernel.php: present (3549 bytes)
app/Console/Kernel.php: present (1319 bytes)
bootstrap/app.php: legacy bootstrap (Illuminate\Foundation\Application + Kernel/Handler singletons)
```

The official Laravel 11 upgrade guide states that it does not recommend moving a
Laravel 10 application to the new skeleton because "Laravel 11 has been carefully
tuned to also support the Laravel 10 application structure". The route alias
table at `app/Http/Kernel.php:44-74` and the schedule at
`app/Console/Kernel.php:18-32` therefore stay where they are. Moving them to
`bootstrap/app.php` / `routes/console.php` is an optional later cleanup, not an
upgrade requirement.

### 3.2 Spatie permission middleware namespace (hard failure)

`app/Http/Kernel.php:67-69` registers:

```
Spatie\Permission\Middlewares\RoleMiddleware
Spatie\Permission\Middlewares\PermissionMiddleware
Spatie\Permission\Middlewares\RoleOrPermissionMiddleware
```

Version 6 renamed the namespace to the singular `Spatie\Permission\Middleware`.
Without this edit every `role`, `permission` and `role_or_permission` route
middleware fails with class-not-found after the Composer update.

Behavioural note from the v6 upgrade guide: `RoleOrPermissionMiddleware` now
uses `canAny()`, so it may grant abilities registered outside this package that
it previously ignored. Audit the routes that use `role_or_permission`.

### 3.3 Sanctum 4 configuration (hard failure on stateful requests)

`config/sanctum.php:62-65` still uses the Sanctum 3 keys:

```
'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
```

Sanctum 4 expects `authenticate_session`, `encrypt_cookies` and
`validate_csrf_token` mapped to the framework middleware classes
(`Laravel\Sanctum\Http\Middleware\AuthenticateSession`,
`Illuminate\Cookie\Middleware\EncryptCookies`,
`Illuminate\Foundation\Http\Middleware\ValidateCsrfToken`).

The `personal_access_tokens` migration already exists at
`database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php`,
so the "publish Sanctum migrations" step is already satisfied.

### 3.4 Doctrine dead code in the test bootstrap

`tests/TestCase.php:23` calls `getDoctrineSchemaManager()` and
`registerDoctrineTypeMapping()`. Laravel 11 removed
`Connection::getDoctrineSchemaManager()`. The call is wrapped in
`catch (\Throwable)`, and an undefined-method `Error` is also a `Throwable`, so
the suite will not fail; the mapping simply becomes dead code. Delete the
`setUp()` body so nobody believes the ENUM mapping still exists.

`Schema::getColumnType()` also stops returning the Doctrine-normalised type and
returns the real column type. `tests/Feature/CampaignPricingSchemaTest.php:45-50`
already normalises `tinyint`/`bool` to `boolean` and `int` to `integer`, so it
should hold, but this is `NOT_TESTED` until the suite runs on 12.

### 3.5 Carbon 3 distance semantics

Seven call sites use `diffIn*` (`diffInDays`, `diffInWeeks`, `diffInMonths`,
`diffInHours`, `diffInSeconds`):

- `app/Http/Controllers/Admin/VisitorManageController.php:245,385,419,453`
- `app/Http/Controllers/Admin/AnalyticsDashboardController.php:131,363,803`

Carbon 3 returns a signed float instead of a signed-free integer. Reviewed:
`app/Services/Analytics/VisitorFilter.php:95-97` swaps a reversed range, and
`AnalyticsDashboardController.php:126` swaps as well, so no call site receives
`from > to` today. The remaining risk is type change: `$spanSeconds` is a float
passed to `subSeconds()`, and month/week distances may be fractional. Keep the
visitor-analytics and dashboard tests in the release gate.

### 3.6 Migration `change()` and float definitions

`->change()` appears at
`database/migrations/2026_10_18_000001_make_customers_email_nullable.php:51`
and `database/migrations/2026_11_02_000002_change_money_columns_to_decimal.php:21-45`.
Laravel 11 drops every modifier that is not repeated on the `change()` call.
Reviewed originals: the money columns are plain `integer` with no default or
unsigned flag, and `customers.email` only carries length 55, so no attribute is
lost. Still verify on a fresh database, or squash with
`php artisan schema:dump` before shipping.

`database/migrations/2023_02_20_022411_create_customers_table.php:22` uses
`$table->float('balance')`. Laravel 11 changed that signature from
total/places to a precision argument, so a fresh install creates a different
`balance` column than the live `FLOAT(8,2)`. Decide the intended type and make
it explicit before a fresh install is trusted.

### 3.7 Checked and unaffected

- Local disk is explicitly defined at `config/filesystems.php:33-37`
  (`root => storage_path('app')`), so the Laravel 12 default change to
  `storage/app/private` does not apply; no `Storage::disk('local')` call exists.
- No model uses `HasUuids`; `Str::uuid()` in `VisitorIdentify` keeps UUID v4.
- No `image` validation rule is used, so the SVG exclusion in Laravel 12 is moot.
- No `mergeIfMissing` call.
- No duplicate route names were found in `routes/`.
- No Doctrine usage in application code.
- `spatie/once` is not installed.
- PHP 8.4.27 is installed (Laravel 12 needs 8.2+), curl 7.81.0 (needs 7.34+),
  and `composer check-platform-reqs` reports success for every extension.

## 4. Support window (priority trade-off)

From the official support policy (laravel.com/docs/12.x/releases):

| Version | Released | Bug fixes until | Security fixes until |
| --- | --- | --- | --- |
| 10 | 2023-02-14 | 2024-08-06 | 2025-02-04 |
| 11 | 2024-03-12 | 2025-09-03 | 2026-03-12 |
| 12 | 2025-02-24 | 2026-08-13 | 2027-02-24 |
| 13 | Q1 2026 | Q3 2027 | Q1 2028 |

Today is 2026-11-26, so Laravel 12 is already in security-only and Laravel 13 is
available. Decision: land 12.74 now, because the user asked for 12, the three
package targets are proven on 12, and every advisory fix exists in 12.71+.
Opening 13 in the same window would add an unmeasured major on top of an
unmeasured major. Record 13 as the next release gate, not as part of this one.

## 5. Ordered migration plan

Each step ends with a check; do not start the next step on a red check.

0. Backup: database dump, `git tag pre-laravel12`, keep the current
   `composer.lock`. This is the rollback point.
1. Baseline: `php artisan test` -> 232 passed (3582 assertions); save the log.
2. Dev tooling: `nunomaduro/collision ^8.1`, `phpunit/phpunit ^11.0`.
3. Sanctum: `laravel/sanctum ^4.0`, update `config/sanctum.php:62-65`, confirm
   the existing `personal_access_tokens` migration.
4. Permission: `spatie/laravel-permission ^6.0`, rename the three middleware
   classes in `app/Http/Kernel.php:67-69`, compare `config/permission.php` and
   the permission migrations against the v6 stubs, audit `role_or_permission`
   routes for the `canAny()` change.
5. Framework: `laravel/framework ^12.74`, remove the `10.x-dev` pin and the dev
   stability setting, `composer update -W`. Expect prompts, carbon, termwind and
   symfony to move.
6. Code cleanup: delete the Doctrine block in `tests/TestCase.php`, make the
   `balance` float explicit, verify the `change()` migrations on a fresh DB or
   squash them.
7. Verify: `php artisan config:clear route:clear view:clear`,
   `php artisan test`, `composer audit`, then smoke: home page, admin login,
   product/cart/checkout, API `auth:sanctum`, courier webhook, and
   `php artisan schedule:run -v` (the schedule still lives in the Console
   Kernel).
8. Ship: commit, push, deploy behind maintenance mode, restore the queue worker.

## 6. Stop rule and rollback

Stop and report instead of forcing the upgrade when any of these is true:

- `composer update` cannot resolve with the four direct constraints above.
- The suite fails in a way that is not fixed within two focused attempts.
- A payment, checkout or courier webhook smoke test fails.
- A fresh-database schema diff drops a column attribute or changes `balance`
  unintentionally.
- `composer audit` still reports the five framework advisories after pinning
  12.74.

Rollback: `git reset --hard pre-laravel12`, `composer install` from the tagged
lock, restore the database dump, then clear caches. Do not deploy a partial
step: the Composer update and the code edits are one release unit.

## 7. Machine-readable blocker list

The release gate parses this block and matches every package against the live
`composer why-not laravel/framework 12.0` output.

<!-- ZC-BLOCKERS:BEGIN
zevo/commerce|root|raise laravel/framework to ^12.74
laravel/sanctum|direct|^3.2 to ^4.0, update config/sanctum.php middleware keys
spatie/laravel-permission|direct|^5.10 to ^6.0, rename middleware namespace
nunomaduro/collision|direct|^7.0 to ^8.1
laravel/prompts|transitive|moves to ^0.3 with collision 8
nesbot/carbon|transitive|moves to ^3.8.4 with framework 12
nunomaduro/termwind|transitive|moves to ^2.0 with collision 8
symfony/console|transitive|moves to ^7.2 with framework 12
symfony/error-handler|transitive|moves to ^7.2 with framework 12
symfony/finder|transitive|moves to ^7.2 with framework 12
symfony/http-foundation|transitive|moves to ^7.2 with framework 12
symfony/http-kernel|transitive|moves to ^7.2 with framework 12
symfony/mailer|transitive|moves to ^7.2 with framework 12
symfony/mime|transitive|moves to ^7.2 with framework 12
symfony/process|transitive|moves to ^7.2 with framework 12
symfony/routing|transitive|moves to ^7.2 with framework 12
symfony/uid|transitive|moves to ^7.2 with framework 12
symfony/var-dumper|transitive|moves to ^7.2 with framework 12
ZC-BLOCKERS:END -->

## 8. Status of every claim

- Resolver output, package constraints, installed versions, platform
  requirements, support dates, kernel state: `SOURCE_VERIFIED` / measured on
  2026-11-26.
- The upgrade itself, Carbon 3 behaviour and the fresh-database schema are
  `NOT_TESTED` / `UNVERIFIED` until the migration actually runs.
- This document becomes stale when the upgrade lands. At that point fold the
  durable decisions into `STATUS.md` and delete it; it is an assessment, not a
  permanent state record.
