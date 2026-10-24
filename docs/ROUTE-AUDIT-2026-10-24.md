# Route audit — 2026-10-24

Scope: `routes/web.php` only. Nothing was removed unless the route had **zero
name references and zero URI references** outside its own definition, *and* its
handler was a pure redirect to a route that still exists, a duplicate of an
existing route, or a development-only inline closure.

Baseline measured with `php artisan route:list --json` (exit 0):

| | routes |
| --- | ---: |
| before | 575 |
| after | 558 |
| removed | 17 |

Duplicate `method + uri` pairs at baseline: 0. Duplicate route names: 0.
Routes whose controller class or method was missing: 0
(`/tmp/opencode/route_audit.php`, exit 0).

## How references were counted

Corpus: every `.php`, `.blade.php`, `.js` and `.json` file under `app/`,
`resources/views/`, `public/`, `config/` and `routes/`, excluding
`routes/web.php` itself (its own definition would always match). For each route
the audit counted files containing the route **name** and files containing the
**URI literal**. Both had to be 0.

`public/` matters: it caught a false positive below that a `grep app resources`
pass would have missed.

## Removed (17)

### Duplicate / preview entry points (5)

| Name | URI | Handler | Why it went |
| --- | --- | --- | --- |
| `legacy.home` | `GET /legacy-home` | `FrontendController@index` | second public URL for the legacy homepage markup; the live homepage is `home` → `frontendHome` |
| `frontend.home.preview` | `GET /frontend-home` | `FrontendController@frontendHome` | same handler as `home` (`/`), i.e. a second URL for the same page |
| `newsletter.subscribe.compat` | `GET\|POST /subscribe` | `FormController@newsletterSubscribe` | same handler as `newsletter.subscribe` (`POST newsletter/subscribe`), which stays |
| `test.zombie.cards` | `GET /test-zombie-cards` | inline closure | dev-only preview rendering `frontend.layouts.pages.details_new` with 8 products; no caller |
| `cart.shortlist` | `GET /cart-shortlist` | inline closure | legacy popup view; the redesigned mini-cart uses `cart.sidebar.content` |

### `{id}/show` redirect stubs (12)

Each of these was a one-line `redirect()->route('<x>.edit', ['id' => $id])`.
The `.edit` target exists for all twelve (checked with
`php artisan route:list --name=<x>.edit`), and the `/show` URL itself had no
caller anywhere.

| Name | URI | Redirects to |
| --- | --- | --- |
| `permissions.show` | `GET admin/permissions/{id}/show` | `permissions.edit` |
| `categories.show` | `GET admin/categories/{id}/show` | `categories.edit` |
| `subcategories.show` | `GET admin/subcategories/{id}/show` | `subcategories.edit` |
| `childcategories.show` | `GET admin/childcategories/{id}/show` | `childcategories.edit` |
| `orderstatus.show` | `GET admin/orderstatus/{id}/show` | `orderstatus.edit` |
| `pixels.show` | `GET admin/pixels/{id}/show` | `pixels.edit` |
| `tagmanagers.show` | `GET admin/tag-manager/{id}/show` | `tagmanagers.edit` |
| `brands.show` | `GET admin/brands/{id}/show` | `brands.edit` |
| `colors.show` | `GET admin/color/{id}/show` | `colors.edit` |
| `sizes.show` | `GET admin/size/{id}/show` | `sizes.edit` |
| `admin.products.show` | `GET admin/products/{id}/show` | `products.edit` |
| `campaign.show` | `GET admin/campaign/{id}/show` | `campaign.edit` |

Reference counts after removal (`name_hits` / `uri_hits` across the corpus):

```
legacy.home 0/0            frontend.home.preview 0/0     newsletter.subscribe.compat 0/0
test.zombie.cards 0/0      cart.shortlist 0/0
permissions.show 0/0       categories.show 0/0           subcategories.show 0/0
childcategories.show 0/0   orderstatus.show 0/0          pixels.show 0/0
tagmanagers.show 0/0       brands.show 0/0               colors.show 0/0
sizes.show 0/0             admin.products.show 0/0       campaign.show 0/0
```

The one raw `/subscribe` hit is not a form: it is
`resources/views/backEnd/prompt/enhanced_prompt_generator.html:594`
(`assets/images/frontend/subscribe…`) and a JS fallback string
`'newsletter/subscribe'` in `resources/views/frontend/layouts/app.blade.php:2033`.

## False positive that was caught and reverted

`products.search` (`GET products/search`) was removed on the first pass, then
restored. The current header forms post to `route('search')`, but the legacy
template's live search calls the literal URL:

```
public/assets/templates/basic/js/main.js:243:        url: '/products/search',
```

After restoring it: `GET /products/search?q=rice` → **200**. Lesson: a route
name with no references is not a dead route when a public JS file hard-codes
the URI. `public/` must be in the corpus.

## Two GET deletes converted to POST + CSRF

Neither route was removed — both are wired to admin UI — but a GET that deletes
is a CSRF hole: any page can drop
`<img src="http://shop/admin/products/image/destroy?id=7">` into an admin's
browser.

| Route | Before | After |
| --- | --- | --- |
| `products.image.destroy` | `GET admin/products/image/destroy` | `POST`, CSRF token, `findOrFail` |
| `campaign.image.destroy` | `GET admin/campaign/image/destroy` | `POST`, CSRF token, `findOrFail` |

Both handlers also had two bugs beyond the method:

1. `find()` + `->image` on a missing row was a fatal error → `findOrFail`.
2. `File::delete($row->image)` was given a path relative to the project root,
   while the file lives under `public/`. The row vanished and the file stayed.
   Deletion is now `public_path()` and is confined to `uploads/`, so a
   corrupted row cannot aim `File::delete` outside the upload directory.

The product edit page's Remove button had **no handler at all**
(`resources/views/backEnd/product/edit.blade.php:196`, class `remove-image`,
`type="button"`, no JS bound) — a dead control next to a live-looking gallery.
It now submits a dedicated per-photo POST form placed outside the product form
(a nested form is invalid HTML). The campaign edit page used a bare
`<a href>` GET link (`campaign/edit.blade.php:162`); it is now a submit button
pointing at the same kind of hidden form.

`public/admin/assets/js/admin-confirm.js` learned the HTML5 `form="id"`
attribute in `formOf()`, so the existing SweetAlert confirm still guards a
button that lives outside its form.

## Kept although the name has no reference

| Route | Evidence it is still used |
| --- | --- |
| `admin.analytics.visitor` | `resources/views/backEnd/analytics/index.blade.php:728` → `url('admin/analytics/visitor')` |
| `cart.sidebar.content` | `resources/views/frontend/layouts/app.blade.php:852,1902` → `/cart/sidebar-content` |
| `products.check-stock` | `resources/views/frontend/pages/product-details.blade.php:386` → `url('products/check-stock')` |
| `products.search` | `public/assets/templates/basic/js/main.js:243` |
| `campaign.image.destroy` | `resources/views/backEnd/campaign/edit.blade.php:162` (now a POST form) |
| `products.image.destroy` | `resources/views/backEnd/product/edit.blade.php:196` (now wired) |

Kept without a reference found, because removal needs a per-feature decision
rather than a redirect/duplicate test:

| Route | Note |
| --- | --- |
| `admin.login`, `admin.login.submit` | authentication entry point; `GET /admin/login` → 200. Do not remove without an end-to-end admin sign-in test |
| `admin.tiktok-test.index` | the page itself has no inbound link; its own JS uses `admin.tiktok-test.connection` / `.send-event`. Dead page candidate |
| `customers.manage` | `GET /admin/customer/manage` → 200, no inbound link; overlaps `customers.index` |
| `admin.order.board.cards` | order board data endpoint; no literal caller found |
| `admin.quill.upload` | rich-text image upload; the editor JS builds the URL elsewhere |
| `settings.destroy` | destructive POST handler with no caller; needs a decision, not a silent delete |
| `brands.page`, `cart.remove.compat`, `cart.update.compat`, `admin.courier.check.phone`, `admin.frontend-theme.preview` | live feature handlers, no reference found in the corpus |

## Verification

```text
php artisan route:list --json      exit 0, 558 routes (was 575)
php artisan test                   35 passed (154 assertions), exit 0
php artisan view:cache             exit 0 (all Blade templates compile)
node --check public/admin/assets/js/admin-confirm.js   exit 0
```

HTTP smoke against `php artisan serve` (`127.0.0.1:8000`):

| URL | Status |
| --- | --- |
| `/` | 200 |
| `/products` | 200 |
| `/product/60` | 200 |
| `/admin/login` | 200 |
| `/products/search?q=rice` | 200 |
| `/legacy-home`, `/frontend-home`, `/test-zombie-cards`, `/cart-shortlist` | 404 |
| `/admin/brands/1/show`, `/admin/products/1/show`, `/admin/categories/1/show` | 404 |
| `GET /admin/products/image/destroy?id=1` | 405 |
| `GET /admin/campaign/image/destroy?id=1` | 405 |

New regression tests:

- `tests/Feature/AdminImageDeleteTest.php` (5 tests): GET is 405 for both image
  routes, POST deletes the row **and** the file, unknown id is 404, and the
  product edit page renders one POST form per photo.
- `tests/Feature/AdminReviewTest.php`: the review list now asserts the product
  name links to `products.edit` — correcting the earlier claim that no such
  route existed (`GET admin/products/{id}/edit` is registered).
