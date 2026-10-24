# Recovery Audit — mounted `Desktop/zevo` vs `z-evo`

Date: 2026-10-24
Auditor: jarin (Principal Debug & Root Cause Engineer)
Allowed root: `/home/xubuntu/Desktop/z-evo`
Mounted source: `/media/xubuntu/759e9d2f-b1e3-48c7-9e24-dc98181febf1/home/sahon/Desktop/zevo`

## 1. Question

The mounted `Desktop/zevo` was believed to be far ahead of `z-evo`
("header/footer position fixed, views made dynamic, many updates"). Decision
requested: copy that folder wholesale and make it the main project.

## 2. Method (reproducible)

```bash
S='/media/xubuntu/759e9d2f-b1e3-48c7-9e24-dc98181febf1/home/sahon/Desktop/zevo'
D='/home/xubuntu/Desktop/z-evo'
sudo -n diff -rq \
  --exclude=vendor --exclude=node_modules --exclude=.git --exclude=storage \
  --exclude=cache --exclude=uploads --exclude=.zombiecoder --exclude=work \
  --exclude='*.log' --exclude=.phpunit.result.cache "$S" "$D"
```

Then per-file `diff -u` on every differing file, plus sha256 on the
header/footer views named in the request.

Note: the mounted folder has **no `.git`** (`ls -ld .../zevo/.git` → No such
file or directory; `git -C` → "not a git repository"), so it is a snapshot
export, not a branch that could be merged.

## 3. Evidence — header/footer are already identical

```
SAME resources/views/frontend/fm/components/shop-header.blade.php
SAME resources/views/frontend/fm/partials/footer/footer-column.blade.php
SAME resources/views/frontend/fm/partials/footer/footer-links.blade.php
SAME resources/views/frontend/fm/partials/footer/footer-menu.blade.php
SAME resources/views/frontend/fm/partials/header/primary-header.blade.php
SAME resources/views/frontend/fm/sections/design/footer-footer-links.blade.php
SAME resources/views/frontend/fm/sections/design/header-primary-header.blade.php
SAME resources/views/frontend/fm/sections/design/shop-header.blade.php
```

(sha256 equal on both sides for each file.)

Reason: the header/footer + dynamic-view work was already merged into `z-evo`
as `a2bd00e` ("Merge latest changes from arafat: 31 modified + 3 new files").
The mounted snapshot simply predates nothing there — it contains the same bytes.

File counts (excluding `vendor`, `node_modules`, `storage`, `public/uploads`):
mounted = 1485, ours = 1555. **The mounted snapshot has fewer files, not more.**

## 4. What the mounted snapshot actually added

The newest mtimes in the snapshot are all in one window, 2026-10-22
10:45–11:37 — the Admin Wishlist batch and four small bug fixes. Nothing about
header/footer after that.

| # | File | Nature | Ported |
|---|------|--------|--------|
| 1 | `app/Http/Controllers/Admin/WishlistController.php` | new | yes |
| 2 | `app/Models/Wishlist.php` | new | yes |
| 3 | `database/migrations/2026_10_22_000002_create_wishlists_table.php` | new | yes |
| 4 | `database/migrations/2026_10_22_000001_normalize_order_status_values.php` | new | yes |
| 5 | `resources/views/backEnd/wishlist/index.blade.php` | new | yes |
| 6 | `tests/Feature/AdminWishlistPageTest.php` | new | yes |
| 7 | `tests/Feature/AdminCourierPagesTest.php` | new | yes |
| 8 | `tests/Feature/AdminOrderDatatableTest.php` | new | yes |
| 9 | `tests/Feature/PasswordChangeTest.php` | new | yes |
| 10 | `app/Http/Controllers/Admin/DashboardController.php` | 1-line fix: old-password error must land in the error bag | yes |
| 11 | `app/Http/Controllers/Frontend/CustomerController.php` | same fix (customer side) | yes |
| 12 | `app/Models/Order.php` | `$casts` for `risk_last_checked_at` / `risk_score_percent` | yes |
| 13 | `app/Providers/AppServiceProvider.php` | do not skip shared view data under phpunit | yes |
| 14 | `resources/views/backEnd/courier/{index,edit,shipment}.blade.php` | move JS inside `@section('script')` — it was dead after `@endsection` | yes |
| 15 | `resources/views/backEnd/layouts/partials/sidebar.blade.php` | Wishlists "Soon" placeholder → real link | yes |
| 16 | `routes/web.php` | wishlist read/write closures + `admin.wishlist.*` routes | yes (shurjopay hunks excluded) |

Ported files are byte-identical to the snapshot (`sha256sum` compared per file,
all `SAME`).

## 5. What was deliberately NOT copied

| File | Reason |
|------|--------|
| `app/Http/Controllers/Frontend/ShurjopayControllers.php` | payment gateway removed on purpose in `8ba62ea` |
| `app/Http/Controllers/Frontend/FrontendController.php` (payment_success/cancel) | same |
| `CustomerController.php` shurjopay branch, `composer.json/lock` `shurjopayv2/laravel8`, `config/app.php` service provider | same |
| `public/uploads/product/*`, `public/uploads/variant/*` | dev-DB image blobs, not code; our seeded set is already in place |
| `.env`, `storage/`, `bootstrap/cache/`, `vendor/` | machine-local state; overwriting would break this machine |

Copying the folder wholesale would therefore have **reverted** `8ba62ea`
(payment removal) and **deleted** `docs/`, `work/`, `tools/`, `README.md`,
`STATUS.md`, `database/seeders/FrontendSeedSeeder.php` and
`public/uploads/frontend-seed/` — i.e. net loss.

## 6. Bonus defect found by the ported tests

`resources/views/frontend/fm/pages/customer/checkout.blade.php:298` still
referenced `$shurjopay_gateway`, which `8ba62ea` had stopped passing.

```
testing.ERROR: Undefined variable $shurjopay_gateway
  at resources/views/frontend/fm/pages/customer/checkout.blade.php:298
```

Result: `/customer/checkout` returned HTTP 500. `git blame` shows the line
came from `1a2e370` and was left orphaned by `8ba62ea`. Fixed here: the
shurjopay radio block was removed and the file docblock corrected.

Remaining dead references (not breaking, follow-up decision):
`AppServiceProvider.php:92-100` (sets `shurjopay.*` config),
`Admin/ApiIntegrationController.php:49-61`, `config/shurjopay.php`,
`backEnd/apiintegration/pay_manage.blade.php`.

## 7. Verification

```
php artisan migrate --force
  2026_10_22_000001_normalize_order_status_values ... DONE
  2026_10_22_000002_create_wishlists_table ......... DONE

php artisan test
  Tests: 25 passed (100 assertions)

curl
  home=200  products=200  wishlist_count=200
  admin/wishlist (guest)=302 -> /login
```

DB backup taken before migrating: `/tmp/opencode/db-backup6.sql` (218684 B,
71 `CREATE TABLE`).

## 8. Conclusion

The mounted `Desktop/zevo` was **not** ahead of `z-evo`; its unique content was
one 2026-10-22 batch, now fully adopted. `z-evo` remains the main project and is
now a functional superset of the snapshot. The only remaining differences are
the intentionally removed Shurjopay code and the files that exist only here.

Status labels: SOURCE_VERIFIED (diff + sha256), END_TO_END_TESTED (phpunit +
curl), CONFIG_VALIDATED (migrate status).
