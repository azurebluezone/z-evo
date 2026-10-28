# Arafat 20 — E-Commerce Platform

A complete e-commerce stack on **Laravel 10**: storefront, admin panel and REST API in one codebase.
Catalog with variants, order lifecycle, courier integration, bKash/ShurjoPay checkout, marketing
pixels and built-in analytics.

> Measured from the codebase: **551 route definitions**, **83 controllers**, **57 models**,
> **478 Blade views**, **122 migrations**.

---

## Table of contents

1. [Tech stack](#tech-stack)
2. [Feature overview](#feature-overview)
3. [Storefront](#storefront)
4. [Admin panel](#admin-panel)
5. [Payments](#payments)
6. [SMS & notifications](#sms--notifications)
7. [Courier & shipping](#courier--shipping)
8. [Marketing, analytics & tracking](#marketing-analytics--tracking)
9. [REST API](#rest-api)
10. [Requirements](#requirements)
11. [Installation](#installation)
12. [Demo accounts](#demo-accounts)
13. [Project structure](#project-structure)
14. [Testing](#testing)
15. [Security notes](#security-notes)

---

## Tech stack

| Layer | Technology |
| --- | --- |
| Framework | Laravel 10.50.3 |
| Runtime | PHP **>= 8.2** (enforced by `vendor/composer/platform_check.php`) |
| Database | MySQL / MariaDB |
| Views | Blade, custom front-end theme `fm` (123 views) |
| Permissions | `spatie/laravel-permission` (roles + permissions) |
| Media | `spatie/laravel-medialibrary`, `intervention/image` |
| Cart | `hardevine/shoppingcart` |
| API auth | `laravel/sanctum` |
| Payments | `shurjopayv2/laravel8`, bKash (custom controller) |
| Geo | `stevebauman/location` |
| UI helpers | `laravel/ui`, `spatie/laravel-html`, `brian2694/laravel-toastr` |
| Browser tests | Playwright (`@playwright/test`) |
| Unit tests | PHPUnit, Faker, Mockery |

---

## Feature overview

- **3 panels** — storefront, admin dashboard, token-authenticated API
- **3-level catalogue** — category → subcategory → child category
- **Variant system** — attributes, attribute values, colors, sizes, per-variant stock
- **Order lifecycle** — order board, statuses, courier assignment, returns & refunds
- **Homepage builder** — every home section can be toggled and titled from settings
- **Role-based access** — Admin / Manager / Support

---

## Storefront

- **Composable home page** — hero, top categories, brands, hot deal, flash sale, featured,
  collections, promo, service, best selling, discount products, recommended and footer sections,
  each with its own enable flag and title in `general_settings`
- **Catalogue** — category, subcategory and child-category pages, brand pages, attribute filters
- **Product page** — image gallery, variant/color/size selection, reviews, recommendations,
  flash-sale pricing, campaign association
- **Cart & checkout** — session cart (`hardevine/shoppingcart`), shipping charge by district,
  coupon validation
- **Customer area** — account, order history, notifications, addresses
- **Landing pages** — database-driven landing pages with their own controller and views
- **Engagement** — contact form, service request form, newsletter subscription, visitor tracking
- **Theming** — switchable front-end theme (`FrontendThemeController`), `fm` theme ships
  sections, partials and components as separate Blade files

## Admin panel

**Dashboard & reporting**
`DashboardController`, `AnalyticsDashboardController`, `ReportsController`, `SocialEventDashboardController`

**Catalogue**
`ProductController`, `VariantController`, `AttributeController`, `CategoryController`,
`SubcategoryController`, `ChildcategoryController`, `BrandController`, `ColorController`,
`SizeController`, `InventoryController`, `ProductRecommendationController`

**Sales**
`OrderController`, `OrderBoardController`, `OrderStatusController`, `ReturnsRefundsController`,
`CouponController`, `CampaignController`, `FlashSaleController`

**Customers & access**
`CustomerManageController`, `UserController`, `RoleController`, `PermissionController`,
`VisitorManageController`, `IpBlock` (model)

**Shipping & courier**
`CourierController`, `CourierSettingsController`, `CourierFraudCheckerController`,
`OrderCourierController`, `ShippingChargeController`, `ShippingVerificationController`

**Marketing & content**
`BannerController`, `BannerCategoryController`, `PromoBannerController`, `HomepageSettingsController`,
`HomepageCollectionController`, `WebsiteSetupController`, `FrontendThemeController`,
`CreatePageController`, `LandingPageController`, `NotificationTemplateController`,
`SocialMediaController`, `SocialEventController`, `SocialEventPlatformController`,
`FacebookTrackingController`, `PixelsController`, `TagManagerController`, `TikTokTestController`

**Settings & ops**
`GeneralSettingController` (64 settings columns incl. branding, footer, colours, section toggles),
`ApiIntegrationController`, `EditorUploadController`, `ServiceRequestsController`,
`UserSubmissionsController`, `UiDemoController`, `ContactController`

## Payments

- **bKash** — `Frontend/BkashController` (create/execute/callback flow)
- **ShurjoPay** — `Frontend/ShurjopayControllers` on top of `shurjopayv2/laravel8`
- Payment gateways are stored in `payment_gateways` and filtered by `status` + `type`

## SMS & notifications

- `SmsGateway` model with per-gateway credentials and notification flags
- `NotificationTemplate` for transactional messages, editable from the admin panel
- Customer notification feed (`CustomerNotificationController`)

## Courier & shipping

- Courier API credentials (`Courierapi`), shipment records (`CourierShipment`)
- Fraud checker (`CourierFraudCheck`) before dispatch
- Per-district shipping charges, manual shipping verification
- Webhook receiver: `Api/CourierWebhookController`

## Marketing, analytics & tracking

- **Facebook Conversions API** — pixel id, conversion token, API version, server-side and
  enhanced e-commerce flags, custom events
- **TikTok Events API** — pixel id, access token, server-side/browser flags, test mode
- **Google Tag Manager** — server container id/url and server-side flag
- **SEO** — `SeoMeta` model for per-entity metadata
- **First-party analytics** — `VisitorMaster`, `EventLog`, event ingestion endpoint,
  IP blocking, analytics dashboard and reports

## REST API

- `laravel/sanctum` token authentication
- `Api/FrontendController` — storefront data
- `Api/EventLogController` — behavioural event ingestion
- `Api/CourierWebhookController` — courier status callbacks

---

## Requirements

- PHP **8.2** or newer with `pdo_mysql`, `mbstring`, `xml`, `curl`, `zip`, `gd`, `bcmath`, `intl`
- Composer 2
- MySQL 5.7+ / MariaDB 10.5+
- Nginx or Apache (document root → `public/`)
- No Node build step required at runtime

## Installation

```bash
# 1. dependencies
composer install --no-dev --optimize-autoloader

# 2. environment
cp .env.example .env
php artisan key:generate

# 3. database (edit DB_* in .env first)
php artisan migrate --force

# 4. demo content (idempotent seeders)
php artisan db:seed --force

# 5. storage permissions
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
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

Recommended PHP limits for catalogue uploads: `upload_max_filesize=100M`, `post_max_size=100M`,
`memory_limit=512M`, `max_execution_time=300`, `max_input_vars=5000`.

## Demo accounts

Created by `CreateAdminUserSeeder` **only for local development**:

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@gmail.com` | `105585` (changed from the seeded `123456`; this is the working local password) |
| Manager | `manager@gmail.com` | `123456` |
| Support | `support@gmail.com` | `123456` |

> Change or delete these accounts before any public deployment.

---

## Project structure

```
app/
  Http/Controllers/
    Admin/      60 back-office controllers
    Api/        3 JSON/token endpoints
    Auth/       login, register, password reset, verification
    Frontend/   storefront, cart, checkout, payments, landing pages
  Models/       57 Eloquent models
database/
  migrations/   122 schema migrations
  seeders/      settings, roles, demo storefront, Shinjuku catalogue
resources/views/
  backEnd/      admin screens
  frontend/
    fm/         current theme (sections, partials, components)
    pages/      static and landing pages
routes/web.php  551 route definitions
public/         assets and uploads (product images under uploads/product/)
```

## Testing

```bash
php artisan test              # PHPUnit
vendor/bin/phpunit            # direct runner
npx playwright test           # browser suite (if configured)
```

## Security notes

- `.env` is git-ignored — never commit credentials or API tokens
- Seeded demo passwords must be rotated before production
- Payment and courier keys live in the database; restrict admin access accordingly
- Keep `APP_DEBUG=false` and `APP_ENV=production` on live servers
