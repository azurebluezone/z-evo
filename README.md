# Z Evo-Commerce

Z Evo-Commerce is a Laravel 10 storefront, admin back office and JSON API in one
codebase. It covers a three-level catalogue with per-variant stock, a full order
lifecycle with courier hand-off, online payments, campaign landing pages and
first-party visitor analytics.

<!-- measured: routes=558 controllers=88 models=63 views=495 migrations=148 -->

| Snapshot | Measured value |
| --- | --- |
| Framework | Laravel 10.50.5 |
| PHP at build time | 8.4.27 (Composer dependency floor: 8.4.1) |
| Route definitions | 558 (539 in `routes/web.php`, 19 in `routes/api.php`) |
| Controllers | 88 classes under `app/Http/Controllers` |
| Eloquent models | 63 classes under `app/Models` |
| Blade views | 495 files under `resources/views` |
| Migrations | 148 files under `database/migrations` |
| Default local database | MySQL, database name `z_evo` |

The numbers above are counted from the tree, not copied from a design document.
`tools/verify/rebrand_check.sh` re-measures them and fails when the README and
the code disagree, so this table cannot drift silently.

---

## Table of contents

1. [Feature overview](#feature-overview)
2. [Tech stack](#tech-stack)
3. [Storefront](#storefront)
4. [Admin panel](#admin-panel)
5. [Payments](#payments)
6. [Courier and shipping](#courier-and-shipping)
7. [Marketing, analytics and tracking](#marketing-analytics-and-tracking)
8. [REST API](#rest-api)
9. [Requirements](#requirements)
10. [Installation](#installation)
11. [Demo accounts](#demo-accounts)
12. [Project structure](#project-structure)
13. [Testing](#testing)
14. [Repository hygiene](#repository-hygiene)
15. [Security notes](#security-notes)

---

## Feature overview

- **Three surfaces** — storefront, admin dashboard and token-authenticated API
- **Three-level catalogue** — category, subcategory, child category
- **Variants** — attributes, attribute values, colours, sizes, per-variant stock
- **Order lifecycle** — order board, statuses, courier assignment, returns, refunds
- **Homepage builder** — every home section has its own enable flag and title
- **Campaign landing pages** — nine registry designs, one live campaign per design
- **Role-based access** — Admin, Manager and Support roles
- **First-party analytics** — visitor sessions, event log, charts and a Leaflet map

---

## Tech stack

| Layer | Technology |
| --- | --- |
| Framework | Laravel 10.50.5 |
| Runtime | PHP 8.4.1 or newer (enforced by `vendor/composer/platform_check.php`) |
| Database | MySQL 5.7+ / MariaDB 10.5+ |
| Views | Blade; the `fm` storefront theme ships as 126 separate views |
| Permissions | `spatie/laravel-permission` |
| Media | `spatie/laravel-medialibrary`, `intervention/image` |
| Cart | `hardevine/shoppingcart` |
| API auth | `laravel/sanctum` |
| Payments | `shurjopayv2/laravel8`, custom bKash flow |
| Geo | `stevebauman/location` |
| UI helpers | `laravel/ui`, `spatie/laravel-html`, `brian2694/laravel-toastr` |
| Tests | PHPUnit, Faker, Mockery; Playwright for browser journeys |

---

## Storefront

- **Composable home page** — hero, top categories, brands, hot deal, flash sale,
  featured, collections, promo, service, best selling, discount products,
  recommended and footer sections, each with its own toggle and title
- **Catalogue** — category, subcategory and child-category pages, brand pages,
  attribute filters
- **Product page** — gallery, variant/colour/size selection, reviews,
  recommendations, flash-sale pricing, campaign association
- **Cart and checkout** — session cart, district shipping charge, coupon rules
- **Customer area** — account, order history, notifications, addresses
- **Landing pages** — database-driven campaigns with their own controller, order
  form and tracking hooks
- **Engagement** — contact form, service request, newsletter, visitor tracking
- **Theming** — switchable front-end theme via `FrontendThemeController`

## Admin panel

**Dashboard and reporting**
`DashboardController`, `AnalyticsDashboardController`, `ReportsController`,
`SocialEventDashboardController`

**Catalogue**
`ProductController`, `VariantController`, `AttributeController`,
`CategoryController`, `SubcategoryController`, `ChildcategoryController`,
`BrandController`, `ColorController`, `SizeController`, `InventoryController`,
`ProductRecommendationController`, `ReviewController`

**Sales**
`OrderController`, `OrderBoardController`, `OrderStatusController`,
`ReturnsRefundsController`, `CouponController`, `CampaignController`,
`FlashSaleController`

**Customers and access**
`CustomerManageController`, `UserController`, `RoleController`,
`PermissionController`, `VisitorManageController`, `PhoneBlockController`

**Shipping and courier**
`CourierController`, `CourierSettingsController`,
`CourierFraudCheckerController`, `OrderCourierController`,
`ShippingChargeController`, `ShippingVerificationController`

**Marketing and content**
`BannerController`, `BannerCategoryController`, `PromoBannerController`,
`HomepageSettingsController`, `HomepageCollectionController`,
`WebsiteSetupController`, `FrontendThemeController`, `CreatePageController`,
`LandingPageController`, `NotificationTemplateController`,
`SocialMediaController`, `SocialEventController`, `SocialEventPlatformController`,
`FacebookTrackingController`, `PixelsController`, `TagManagerController`,
`TikTokTestController`

**Settings and operations**
`GeneralSettingController`, `ApiIntegrationController`, `EditorUploadController`,
`ServiceRequestsController`, `UserSubmissionsController`, `UiDemoController`,
`ContactController`

## Payments

- **bKash** — `Frontend/BkashController` with create, execute and callback steps
- **ShurjoPay** — `Frontend/ShurjopayControllers` on `shurjopayv2/laravel8`
- Gateways live in `payment_gateways` and are filtered by `status` and `type`

## Courier and shipping

- Courier credentials in `Courierapi`, shipments in `CourierShipment`
- Fraud check in `CourierFraudCheck` before dispatch
- Per-district shipping charges and manual shipping verification
- Signed webhook receiver: `Api/CourierWebhookController`
- Admin settings mask stored secrets and label each one **Saved** or **Not set**;
  a token is only fetched when an admin presses **Copy token**

## Marketing, analytics and tracking

- **Facebook Conversions API** — pixel id, conversion token, API version,
  server-side and enhanced e-commerce flags, custom events
- **TikTok Events API** — pixel id, access token, server/browser flags, test mode
- **Google Tag Manager** — server container id and URL
- **SEO** — `SeoMeta` per-entity metadata
- **First-party analytics** — `VisitorMaster`, `EventLog`, ingestion endpoint,
  phone blocking, dashboard charts and a Leaflet visitor map

## REST API

- `laravel/sanctum` token authentication
- `Api/FrontendController` — storefront data
- `Api/EventLogController` — behavioural event ingestion
- `Api/CourierWebhookController` — courier status callbacks

---

## Requirements

- PHP **8.4.1** or newer with `pdo_mysql`, `mbstring`, `xml`, `curl`, `zip`,
  `gd`, `bcmath` and `intl`
- Composer 2
- MySQL 5.7+ / MariaDB 10.5+
- Nginx or Apache with the document root pointed at `public/`
- No Node build step is required at runtime

## Installation

```bash
# 1. dependencies
composer install --no-dev --optimize-autoloader

# 2. environment
cp .env.example .env
php artisan key:generate

# 3. database (fill DB_* in .env first)
php artisan migrate --force

# 4. demo content (idempotent seeders, including the CMS pages)
php artisan db:seed --force

# 5. writable storage
chmod -R ug+rwX storage bootstrap/cache
```

**Nginx** (minimal vhost):

```nginx
server {
    listen 80;
    root /path/to/project/public;
    index index.php;
    client_max_body_size 100m;

    location / { try_files $uri $uri/ /index.php?$query_string; }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

Recommended PHP limits for catalogue uploads: `upload_max_filesize=100M`,
`post_max_size=100M`, `memory_limit=512M`, `max_execution_time=300`,
`max_input_vars=5000`.

## Demo accounts

Created by `database/seeders/CreateAdminUserSeeder.php` **for local development
only**:

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@gmail.com` | `105585` |
| Manager | `manager@gmail.com` | `123456` |
| Support | `support@gmail.com` | `123456` |

> Rotate or delete these accounts before any public deployment.

---

## Project structure

```
app/
  Http/Controllers/
    Admin/       64 back-office controllers
    Api/          4 JSON endpoints
    Auth/         login, register, password reset, verification
    Frontend/     storefront, cart, checkout, payments, landing pages
  Models/        63 Eloquent models
database/
  migrations/   148 schema migrations
  seeders/      settings, roles, demo catalogue, campaigns, CMS pages
resources/views/
  backEnd/      admin screens
  frontend/
    fm/         126 views: current theme (sections, partials, components)
    pages/      static and landing pages
routes/
  web.php       539 route definitions
  api.php        19 route definitions
public/         assets and uploads (product images under uploads/product/)
tools/verify/   release gates and environment checks
```

## Testing

```bash
php artisan test              # PHPUnit, full suite
vendor/bin/phpunit            # direct runner
npx playwright test           # browser suite, when configured
```

Tests run against the configured development database, so features that need
rows create and clean up their own fixtures instead of depending on seeded demo
data.

## Repository hygiene

`.gitignore` keeps generated and machine-local material out of history:

- `.env` and every `.env.*` variant, while `.env.example` stays tracked
- `/vendor`, `/node_modules` and build caches
- user uploads under `public/uploads/`
- `.zombiecoder/` at any depth, so nested agent workspaces cannot slip in
- `__pycache__/` and `*.pyc` from the asset tooling
- `tools/verify/.artifacts/` (suite logs, screenshots, staging lists)
- `public/reports/` (rendered briefs and OpenCode session transcripts)

Never `git add -f` around these rules; a committed session dump or upload is
hard to remove from public history.

## Security notes

- `.env` is git-ignored: never commit credentials or API tokens
- Seeded demo passwords must be rotated before production
- Payment and courier keys live in the database, so restrict admin access
- Keep `APP_DEBUG=false` and `APP_ENV=production` on live servers
- Courier webhook tokens are stored masked and printed only on demand
