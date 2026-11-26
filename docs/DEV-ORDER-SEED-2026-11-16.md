# Dev order seed — 30 variant-aware orders — `SEED-FIRST-V3`

**তৈরি:** ২০২৬-১১-১৬
**Seeders:** `DevShippingChargeSeeder`, `DevOrderSeeder`, `DevReportFixtureSeeder`
**Services:** `OrderPlacementService` (extended), `InventoryLedger`, `OrderLifecycle`
**Verifier:** `tools/verify_dev_seed.php` → wrapper `tools/verify_dev_seed_bundle.sh`
**Marker:** `DEV_SEED_BUNDLE_OK`

## কী করে

`DevOrderSeeder` অর্ডারগুলো direct INSERT করে না। প্রতিটি অর্ডার আসল checkout
path দিয়ে যায়:

```text
OrderPlacementService::place()
  → orders / shippings / payments / order_details (variant_id সহ)
  → InventoryLedger::reserveOrder()  → RESERVE movement + inventory_reservations
  → OrderLifecycle::transition()     → processing → shipped → delivered / cancelled / returned
                                       SALE / RELEASE / delivered_at / cancelled_at / audit
```

তাই seed pass হলে বোঝা যায় আসল অর্ডার path-ও কাজ করছে — variant মিসিং বা
ledger অমিল হলে seed নিজেই exception ছোড়ে।

## Deterministic spec

| বিষয় | নিয়ম |
|---|---|
| Invoice | `AD-ORD-0001` … `AD-ORD-0030` |
| Product index | অর্ডার index = catalog index |
| Qty | `[1,2,3,1,2][i % 5]` |
| Zone | `ঢাকার ভিতরে` 70 / `ঢাকার বাহিরে` 130 / `Standard delivery` 120, cycling |
| Free shipping | index `4,11,19,25` → `shipping_charge=0`, `shipping_is_free=1`, `shipping_list_price` snapshot |
| Payment method | COD / bkash / nagad / card, cycling |
| Multi-line | `i % 7 == 0` → ৭ ঘর পরের product একটি extra line |
| Coupon | `AD-ORD-0003` ADORA10, `AD-ORD-0015` FLAT100, `AD-ORD-0023` WELCOME50 |
| Date anchor | `2026-11-15 10:00:00` − `(29 − i)` দিন |
| Status | delivered 12, pending 10, shipped 4, cancelled 2, returned 2 |
| Customers | ১০ জন, phone `01710000001`–`01710000010` |

Status transition-এর সময়গুলো placed day-এর ভেতরেই থাকে
(`+30m processing`, `+2h shipped`, `+5h delivered`, `+8h returned`, `+45m cancelled`),
তাই কোনো fixture ভবিষ্যতের তারিখে পড়ে না।

## Inventory — first principles

লেজারই SSOT; `variant_stocks` শুধু projection।

| Movement | কখন | `on_hand` | hold |
|---|---|---|---|
| `OPENING_BALANCE +25` | catalog seed | বাড়ে | — |
| `RESERVE −qty` | checkout/place | বদলায় না | `inventory_reservations` row |
| `SALE −qty` | shipped / delivered / returned | কমে | hold delete |
| `RELEASE +qty` | cancelled | বদলায় না | hold delete |
| `RETURN +qty` | sellable return | বাড়ে | — |
| `RETURN_DAMAGED +qty` | damaged return | বাড়ে, `available` বদলায় না | — |

- `variant_stocks.reserved` সবসময় `0`; live hold `inventory_reservations`-এ।
- Pending ১০টি অর্ডারের hold ১২০ দিনের TTL নিয়ে active থাকে, যাতে dev-এ
  "expired reservation" দেখিয়ে স্টক ভুল না হয়।
- `products.stock` কেবল legacy bridge — প্রতি on-hand delta-তে mirror হয়, যাতে
  `DROP-LEGACY-STOCK`-এর আগে পুরোনো stock screen মিথ্যা না বলে।

## Report fixtures

`DevReportFixtureSeeder` (অর্ডারের পরে চলে):

| Fixture | কী |
|---|---|
| Returns | `AD-ORD-0029` sellable + refunded=1; `AD-ORD-0030` damaged + refunded=0 |
| Return items | ৩টি line (`order_return_items`), refund amounts header-এর সাথে মেলে |
| Restock | `InventoryLedger::restock()` → `RETURN` (২) + `RETURN_DAMAGED` (১) |
| Coupon usages | ৩টি attribution row; `coupons.used_count` row-count থেকে normalize |
| Courier | Pathao ২টি (delivered, `shipping_cost=60`), Steadfast ৪টি (shipped, `shipping_cost=70`) |
| Courier events | ১৪টি timeline row, প্রতিটি shipment-এর অন্তত একটি event |

## Shipping read-only

`shipping_charges`-এ ঠিক তিনটি row:

| Name | Amount |
|---|---:|
| `Standard delivery` | 120 |
| `ঢাকার ভিতরে` | 70 |
| `ঢাকার বাহিরে` | 130 |

`ShippingCharge::isReadOnlyName()` canonical + legacy spelling
(`ঢাকার বাইরে`, `ঢাকার বাইরে।`) lock করে। `ShippingChargeController`-এর
store/update/active/inactive/destroy refuse করে, আর index view-তে lock badge
দেখায়। Free shipping কোনো charge row নয় — per-order flag
(`shipping_is_free` + `shipping_list_price`)।

## Migrations (plan §3.5)

| Migration | কী |
|---|---|
| `2026_11_16_000001_add_shipping_cost_to_courier_shipments` | `shipping_cost decimal(12,2)` |
| `2026_11_16_000002_add_shipping_fields_to_orders` | `shipping_is_free`, `shipping_list_price` |
| `2026_11_16_000003_add_delivered_at_to_orders` | `delivered_at`, `cancelled_at` |
| `2026_11_16_000004_create_order_return_items` | line-level returns + `order_returns.order_id` UNIQUE drop |
| `2026_11_16_000005_add_note_to_audit_logs` | `audit_logs.note` |
| `2026_11_16_000006_add_stock_threshold_to_variant_stocks` | `low_stock_threshold` |

## চালানো ও যাচাই

Scratch database-এ (আসল `z_evo` ছোঁয়া হয় না):

```bash
bash tools/verify_dev_seed_bundle.sh
```

স্ক্রিপ্ট নিজেই scratch DB বানায়, `migrate:fresh --seed` চালায়, verifier
দুইবার চালায় (দ্বিতীয়বার seeders re-run করে idempotency প্রমাণ), admin status
controller smoke করে, শেষে scratch DB drop করে।

## প্রমাণ (২০২৬-১১-১৬, scratch `z_evo_seed_check`)

```text
Dev orders: 30 placed, 0 already present (30 expected).
Report fixtures: 2 returns / 3 items · 3 coupon usages · 6 shipments / 14 events.

ORDERS 30
STATUS_MIX delivered=12 pending=10 shipped=4 cancelled=2 returned=2
LINES 35 variant_aware=1
RETURNS 2 ITEMS 3
COUPON_USAGES 3
SHIPMENTS 6 EVENTS 14
RESERVATIONS 12
LEDGER_OPENING 30
DEV_SEED_BUNDLE_OK

Dev orders: 0 placed, 30 already present (30 expected).
DEV_SEED_BUNDLE_OK
```

`RESERVATIONS 12` = pending ১০টি অর্ডারের ১২টি line (index 14 ও 21 multi-line)।

## পরের ধাপ

```bash
php artisan migrate:fresh --seed
```

আসল dev database-এ একবার। এরপর plan-এর `DROP-LEGACY-STOCK` ধাপ (আলাদা কাজ)।
