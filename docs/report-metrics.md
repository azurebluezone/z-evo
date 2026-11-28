# Report Metric Dictionary

<!-- METRIC_DICTIONARY_LOCKED -->

**Project:** `z-evo-commerce` (Laravel 12.74, PHP 8.4)
**Locked:** 2026-11-27
**Owner:** Manu (Chief Systems Architect)
**Scope:** admin dashboard, `/admin/reports/*`, `/admin/order-report`, `/admin/stock-report`, daily report email.

**Status vocabulary**

| Status | Meaning |
|---|---|
| `IMPLEMENTED` | Code computes this definition today and a report surface shows it. |
| `PARTIAL` | A surface shows the number, but the grain or the formula does not match this definition yet. |
| `PROPOSED` | The definition is locked, but no surface computes it yet. |
| `BLOCKED` | The definition is locked, but a required column or table does not exist. |

## 0. Lock rule

This file is the single source of truth for report metric definitions.

- A metric name means exactly one formula, one grain and one date basis.
- Add or change a metric here before changing code.
- If code and this file disagree, code is wrong.
- A page must not invent a local definition. If a page needs a different number, it gets a different name.
- `PROPOSED` is not a promise that the number is on screen today.

## 1. Global rules

### 1.1 Grain

| Table | One row means |
|---|---|
| `orders` | one order |
| `order_details` | one order line |
| `payments` | one payment attempt |
| `courier_shipments` | one shipment |
| `courier_events` | one tracking event |
| `order_returns` | one return request |
| `order_return_items` | one returned line |
| `coupon_usages` | one coupon use |
| `inventory_movements` | one stock movement |
| `variant_stocks` | one variant balance |
| `customers` | one customer |
| `event_logs` | one visitor event |

**Rule:** one query, one grain. Aggregate each domain separately, then merge on `order_id`. Never `SUM(orders.amount)` after joining `order_details`, `payments`, `order_returns`, or `courier_events`.

Example: one order with 3 items and 2 payments produces 6 joined rows. An order-level sum over that join counts the order 6 times.

### 1.2 Date basis

| Report | Date column |
|---|---|
| Orders placed | `orders.created_at` |
| Delivered sales | `orders.delivered_at` |
| Payments | `payments.created_at` |
| Returns | `order_returns.created_at` |
| Courier | `courier_shipments.created_at` |
| Inventory | `inventory_movements.created_at` |
| Coupons | `coupon_usages.created_at` |
| Visitors | `event_logs.event_time` |

### 1.3 Timezone and range

The database stores UTC; the application runs `Asia/Dhaka`. A range is `>= start 00:00` and `< next day 00:00`. Do not use `BETWEEN end 23:59:59`.

### 1.4 Money formula

```text
orders.amount = (subtotal + shipping_charge) - discount
```

Evidence:

- `app/Services/OrderPlacementService.php:70` — `$amount = max(0.0, ($subtotal + $shippingFee) - $discount);`
- `app/Http/Controllers/Admin/OrderController.php:1789` — POS create
- `app/Http/Controllers/Admin/OrderController.php:2189` — POS edit

`discount` is already inside `orders.amount`:

- Web checkout writes `discount => 0` (`ShoppingController.php:619`).
- POS writes product + manual + coupon discount (`OrderController.php:49-53`).
- Never subtract `order_details.product_discount` a second time.

### 1.5 Status handling

`orders.order_status` can hold a slug or a numeric id. Map through `order_statuses`; do not hard-code a new status name.

- Delivered: `['delivered', '4']`
- Cancelled: `['cancelled', '5']`
- Live data today is numeric only.

### 1.6 Duplicate rule

One query, one grain. If an order-level amount must be combined with a detail table, aggregate the detail table first and merge the two results on `order_id`. `COUNT(DISTINCT order_id)` is acceptable for a count; it is not a substitute for a correct sum.

### 1.7 Previous-period change

Every headline KPI on a report page is shown with its change against the window of the **same length immediately before** the selected range.

```text
previous_to   = range_from - 1 day, end of day
previous_from = previous_to - (days_in_range - 1), start of day
delta         = (now - before) / before * 100        rounded to 1 decimal
delta         = null                                 when before = 0
```

- A null delta is rendered as `no previous period`, never as `0%`, `infinity` or a blank.
- The previous window uses the same formula, grain and date basis as the current one: both go through `ReportMetricsService::kpiTotals()`.
- Delta is a percentage of the previous value, not of the range, and it is not annualised.

### 1.8 Export

A report download is the page's query without pagination: the same filters, the same query source (`BuildsReportQueries`), and therefore the same numbers. `page` is never a filter.

- Excel is a real `.xlsx` workbook (Office Open XML, built with `ZipArchive`), not a CSV renamed. Every text cell is an inline string, so a value beginning with `=` is text, never a formula.
- CSV is the same header and the same rows, UTF-8 with a BOM, for tools that want a plain file.
- PDF is the browser's print path: `Print / PDF` calls `window.print()` and the print stylesheet in `admin-report.css`; the browser's "Save as PDF" produces the file. There is no server-side PDF renderer in this project, so no endpoint pretends to be one.
- The Sales Report export follows the grain on screen (`view=line` or `view=order`); the Product Report export is the product list with its lifetime delivered sales columns.
- The last row is `TOTAL` and covers the whole filter, matching the footer strip, never the visible page.

## 2. Revenue vocabulary

Never label a number simply "revenue". Use one of these three names.

| Name | Formula | Grain | Date basis | Surface |
|---|---|---|---|---|
| Order Value (placed) | `SUM(orders.amount)` | order | `orders.created_at` | Dashboard "Orders placed", Sales & Profit "Ordered revenue" |
| Delivered Revenue | `SUM(orders.amount)` where delivered | order | `orders.delivered_at` | Dashboard "Delivered revenue", Sales & Profit "Delivered revenue" |
| Collected Payment | `SUM(payments.amount)` where `payment_status = 'paid'` | payment | `payments.created_at` | Payment Methods report |

## 3. Core metric dictionary

### 3.1 Orders and sales

| Metric | Formula | Grain | Source | Date basis | Status | Surfaces |
|---|---|---|---|---|---|---|
| Total Orders | `COUNT(orders.id)` | order | `orders` | `created_at` | `IMPLEMENTED` | Dashboard, Sales & Profit, Returns |
| Delivered Orders | `COUNT(orders.id)` where delivered | order | `orders` | `delivered_at` | `IMPLEMENTED` | Dashboard, Sales & Profit |
| Order Value (placed) | `SUM(orders.amount)` | order | `orders` | `created_at` | `IMPLEMENTED` | Dashboard, Sales & Profit |
| Delivered Revenue | `SUM(orders.amount)` where delivered | order | `orders` | `delivered_at` | `IMPLEMENTED` | Dashboard, Sales & Profit |
| Gross Product Sales | `SUM(order_details.sale_price * order_details.qty)` | order line | `order_details` | parent order `created_at` | `IMPLEMENTED` | Dashboard KPI, Sales Report line table |
| Net Sales | `SUM(orders.amount) - SUM(orders.shipping_charge)` | order | `orders` | `delivered_at` | `IMPLEMENTED` | Dashboard, Sales & Profit "Product revenue net" |
| AOV | `Net Sales / Delivered Orders` | order | `orders` | `delivered_at` | `IMPLEMENTED` | Dashboard; Coupon Impact has Coupon AOV |
| Top Products | top 5 of `SUM(order_details.sale_price * order_details.qty)` grouped by `order_details.product_id`, units `SUM(order_details.qty)`; ties: sales desc, units desc, `product_id` asc | order line | `order_details` | parent order `created_at` | `IMPLEMENTED` | Dashboard table (same basis as Gross Product Sales) |
| Filtered Delivered Orders | `COUNT(DISTINCT order_details.order_id)` of the lines the Sales Report filter keeps | order | `order_details` | page filter (`order_details.updated_at`) | `IMPLEMENTED` | Sales Report summary cards |
| Order Discount (Sales Report) | `SUM(orders.discount)` over the distinct filtered delivered orders | order | `orders` | page filter (`order_details.updated_at`) | `IMPLEMENTED` | Sales Report summary cards |
| Shipping Charged (Sales Report) | `SUM(orders.shipping_charge)` over the distinct filtered delivered orders | order | `orders` | page filter (`order_details.updated_at`) | `IMPLEMENTED` | Sales Report summary cards |
| Order Total (Sales Report) | `SUM(orders.amount)` over the distinct filtered delivered orders | order | `orders` | page filter (`order_details.updated_at`) | `IMPLEMENTED` | Sales Report summary cards |
| Product Units Sold | `SUM(order_details.qty)` for delivered orders, grouped by `order_details.product_id` | order line | `order_details` | lifetime (no window) | `IMPLEMENTED` | Product Report table + footer |
| Product Revenue (delivered) | `SUM(order_details.sale_price * order_details.qty)` for delivered orders, grouped by `order_details.product_id` | order line | `order_details` | lifetime (no window) | `IMPLEMENTED` | Product Report table + footer |
| Product COGS | `SUM(order_details.purchase_price * order_details.qty)` for delivered orders, grouped by `order_details.product_id` | order line | `order_details` | lifetime (no window) | `IMPLEMENTED` | Product Report table + footer |

**Product Report sales basis.** The Product Report lists products by its own filters (keyword, category, `products.updated_at` range); the three sales columns are LIFETIME delivered figures per product: status `['delivered','4']`, grouped on `order_details.product_id` (the historical `product_name` is display text, never a key), no date window, and no order-level discount is allocated. They are deliberately not the same number as Gross Product Sales (all statuses, parent `created_at`) or the dashboard COGS (delivered, windowed): the Product Report answers "how much has this product ever sold", the dashboard answers "how much did we sell in this window". Product Revenue minus Product COGS is a product-level margin, not the dictionary's Gross Profit (Net Sales - COGS at order grain).

**Sales Report summary rule.** The four summary numbers are order-grain: take the `order_id`s the filtered line query keeps, then aggregate `orders` over that distinct set (duplicate rule 1.6). Summing `orders.discount` across line rows would multiply it by the line count - the live data has delivered orders with more than one line. The summary date basis is the page's own filter (`order_details.updated_at` from Start Date 00:00:00 to End Date 23:59:59; with no dates the filter is unbounded), so the cards always describe exactly the table above them, not a second hidden date rule.

**Order-level view (Sales Report).** The `Line view` / `Order view` toggle (`?view=order`) keeps the same filter and the same four cards and prints one row per distinct filtered delivered order: Invoice and Date from `orders`, Customer from `shippings`, Lines / Qty / Sales summed from the lines the filter kept, Discount / Shipping / Order Total straight from `orders` (`orders.amount` already is `(subtotal + shipping) - discount`), Status from `order_statuses` by id-or-slug, Payment from the latest `payments` row, Courier from the latest `courier_shipments` row. Money never comes from re-summing lines: a two-line order occupies one row and its discount is counted once.

### 3.2 Profit and cost

| Metric | Formula | Grain | Source | Date basis | Status | Surfaces |
|---|---|---|---|---|---|---|
| COGS | `SUM(order_details.purchase_price * order_details.qty)` | order line | `order_details` | parent order `delivered_at` | `IMPLEMENTED` | Dashboard, Sales & Profit |
| Gross Profit | `Net Sales - COGS` | order | derived | `delivered_at` | `IMPLEMENTED` | Dashboard KPI `gross_profit`, Sales & Profit arithmetic |
| Shipping Revenue | `SUM(orders.shipping_charge)` where delivered | order | `orders` | `delivered_at` | `IMPLEMENTED` | Sales & Profit |
| Courier Cost | `SUM(courier_shipments.shipping_cost)` | shipment | `courier_shipments` | `created_at` | `IMPLEMENTED` | Dashboard, Sales & Profit, Courier |
| Shipping Margin | `Shipping Revenue - Courier Cost` | order / shipment | derived | `delivered_at` | `IMPLEMENTED` | Sales & Profit |
| Net Profit A (delivery-inclusive) | `Delivered Revenue - COGS - Courier Cost - Refund Paid - Business Expenses` | order | derived | `delivered_at` / expense `spent_at` | `IMPLEMENTED` | Dashboard "Estimated Net Profit", Sales & Profit "Net profit A" |
| Net Profit B (product-only) | `Net Sales - COGS - Refund Paid` | order | derived | `delivered_at` | `IMPLEMENTED` | Sales & Profit "Net profit B" |
| Expense (dashboard) | `COGS + Courier Cost + Refund Paid + Business Expenses` | order | derived | `delivered_at` / expense `spent_at` | `IMPLEMENTED` | Dashboard |
| Business Expenses | `SUM(business_expenses.amount)` | expense row | `business_expenses` | `spent_at` | `IMPLEMENTED` | Dashboard expense, Sales & Profit View A |
| Refund Required | `SUM(order_returns.refund_amount) WHERE refund_required = 1` | return | `order_returns` | `created_at` | `IMPLEMENTED` | Dashboard, Returns, Sales & Profit |
| Refund Paid | `SUM(order_returns.refund_amount) WHERE refunded = 1` | return | `order_returns` | `created_at` | `IMPLEMENTED` | Dashboard, Returns, Sales & Profit |
| Refund Pending | `Refund Required - Refund Paid` | return | derived | `created_at` | `IMPLEMENTED` | Dashboard, Returns, Sales & Profit |

### 3.3 Payments

| Metric | Formula | Grain | Source | Date basis | Status | Surfaces |
|---|---|---|---|---|---|---|
| Collected Payment | `SUM(payments.amount) WHERE payment_status = 'paid'` | payment | `payments` | `created_at` | `IMPLEMENTED` | Payment Methods, Dashboard |
| Pending Payment | `SUM(payments.amount) WHERE payment_status = 'pending'` | payment | `payments` | `created_at` | `IMPLEMENTED` | Payment Methods, Dashboard |
| Payment Success Rate | `paid payment count / all payment count * 100` | payment | `payments` | `created_at` | `IMPLEMENTED` | Payment Methods |
| Average Paid Payment | `Collected Payment / paid payment count` | payment | `payments` | `created_at` | `IMPLEMENTED` | Payment Methods |
| Payment Settlement Gap | `SUM(payments.amount - payments.gateway_fee - payments.settled_amount) WHERE payment_status = 'paid'` | payment | `payments`, `payment_settlements` | `payments.created_at`; settlement date is `settled_at` | `IMPLEMENTED` | Payment Methods card and per-method Fee/Settled/Gap columns; a settlement entry writes one `payment_settlements` row and recomputes the payment's `settled_amount`/`gateway_fee` from its rows; `AdminPaymentSettlementTest` proves the page gap equals an independent SQL sum |

### 3.4 Courier

| Metric | Formula | Grain | Source | Date basis | Status | Surfaces |
|---|---|---|---|---|---|---|
| Shipments | `COUNT(courier_shipments.id)` | shipment | `courier_shipments` | `created_at` | `IMPLEMENTED` | Courier |
| Delivered Shipments | `COUNT(*) WHERE status_normalized = 'delivered'` | shipment | `courier_shipments` | `created_at` | `IMPLEMENTED` | Courier, Dashboard |
| Returned Shipments | `COUNT(*) WHERE status_normalized IN ('returned','cancelled')` | shipment | `courier_shipments` | `created_at` | `IMPLEMENTED` | Courier |
| In-Transit Shipments | `COUNT(*) WHERE status_normalized = 'in_transit'` | shipment | `courier_shipments` | `created_at` | `IMPLEMENTED` | Courier; unknown statuses are not counted as in transit |
| Delivery Success Rate | `Delivered Shipments / Shipments * 100` | shipment | `courier_shipments` | `created_at` | `IMPLEMENTED` | Courier, Dashboard |
| Courier Average Cost | `Courier Cost / Shipments` | shipment | `courier_shipments` | `created_at` | `IMPLEMENTED` | Courier |
| COD Outstanding | `SUM(courier_shipments.cod_amount - courier_shipments.remitted_amount)` | shipment | `courier_shipments` | settlement date, `created_at` until a settlement date exists | `IMPLEMENTED` | Courier card and per-provider column; `AdminCodOutstandingTest` proves the page total equals an independent SQL sum; a `settlement_status` outside pending/remitted/settled is counted as unknown, never folded into a guess |
| Courier Settlement Shortfall | `SUM(courier_shipments.cod_amount - courier_shipments.remitted_amount - courier_shipments.return_charge)` | shipment | `courier_shipments`, `courier_settlements` | `courier_shipments.created_at`; remittance date is `courier_settlements.remitted_at` | `IMPLEMENTED` | Courier reconciliation table (expected COD, remitted, return charge, shortfall) and card; a remittance entry writes one `courier_settlements` row and recomputes the shipment's `remitted_amount` from its rows; `AdminCourierSettlementTest` proves the page shortfall equals an independent SQL sum |

**Status normalization rule.** `courier_shipments.status_normalized` is the only status a report reads. `CourierShipment` writes it on every save through `CourierStatusNormalizer::normalize()`, so the Pathao sender, the SteadFast sender, the webhook and the admin panel cannot drift. Unknown provider words keep their own lowercased slug rather than being forced into a bucket; that is why In-Transit is an explicit count, not `Shipments - Delivered - Returned`.

### 3.5 Returns and cancellations

| Metric | Formula | Grain | Source | Date basis | Status | Surfaces |
|---|---|---|---|---|---|---|
| Return Rate (order) | `COUNT(DISTINCT order_returns.order_id) / COUNT(orders.id) * 100` | order | `order_returns` + `orders` | return `created_at` vs order `created_at` | `IMPLEMENTED` | Returns; one order with two returns counts once |
| Return Rate (item) | `SUM(order_return_items.qty) / SUM(order_details.qty) * 100` | order line | `order_return_items` + `order_details` | return `created_at` vs parent order `created_at` | `IMPLEMENTED` | Returns |
| Cancelled Orders | `COUNT(orders.id) WHERE order_status IN ('cancelled','5')` | order | `orders` | `created_at` | `IMPLEMENTED` | Returns |
| Cancel Rate | `Cancelled Orders / Total Orders * 100` | order | `orders` | `created_at` | `IMPLEMENTED` | Returns |

**Item Return Rate rule.** The numerator is every returned unit whose return was created in the range; the denominator is every unit placed on an order created in the range. The rate is therefore a ratio of two events in the same window, not a lifetime figure, and it uses `qty`, not return rows: three returned units on one line count as three.

### 3.6 Coupons

| Metric | Formula | Grain | Source | Date basis | Status | Surfaces |
|---|---|---|---|---|---|---|
| Coupon Usage Count | `COUNT(coupon_usages.id)` | usage | `coupon_usages` | `created_at` | `IMPLEMENTED` | Coupon Impact, Dashboard |
| Coupon Discount | `SUM(coupon_usages.discount_amount)` | usage | `coupon_usages` | `created_at` | `IMPLEMENTED` | Coupon Impact, Dashboard |
| Coupon Revenue | `SUM(orders.amount)` over `DISTINCT coupon_usages.order_id` | order | `coupon_usages` + `orders` | `coupon_usages.created_at` | `IMPLEMENTED` | Coupon Impact, Dashboard, daily email |
| Coupon AOV | `Coupon Revenue / linked orders` | order | derived | `coupon_usages.created_at` | `IMPLEMENTED` | Coupon Impact |
| Coupon Counter Drift | `coupons.used_count != COUNT(coupon_usages.id)` | coupon | `coupons` + `coupon_usages` | all time | `IMPLEMENTED` | Report Health |

### 3.7 Stock

| Metric | Formula | Grain | Source | Date basis | Status | Surfaces |
|---|---|---|---|---|---|---|
| On Hand | `SUM(variant_stocks.on_hand)` | variant | `variant_stocks` | current | `IMPLEMENTED` | Stock Alerts |
| Reserved | `SUM(variant_stocks.reserved)` | variant | `variant_stocks` | current | `IMPLEMENTED` | Stock Alerts |
| Available | `SUM(variant_stocks.available)` | variant | `variant_stocks` | current | `IMPLEMENTED` | Stock Alerts |
| Damaged | `SUM(variant_stocks.damaged)` | variant | `variant_stocks` | current | `IMPLEMENTED` | Stock Alerts |
| Stock Status Buckets | out: `available <= 0`; low: `0 < available <= effective low line`; remaining: `effective low line < available <= max(stock_remaining_threshold, effective low line)`; healthy: above; orphan: `sku IS NULL`. Effective low line = `low_stock_threshold` when it is greater than zero, otherwise `stock_warning_threshold` | variant | `variant_stocks` + `report_settings` | current | `IMPLEMENTED` | Stock Alerts, navbar signal, dashboard |
| Inventory Movement Count | `COUNT(inventory_movements.id)` | movement | `inventory_movements` | `created_at` | `IMPLEMENTED` | Stock Alerts |
| Last Sold (variant) | `MAX(orders.delivered_at)` over delivered lines with that `order_details.variant_id` | variant | `order_details` + `orders` | lifetime | `IMPLEMENTED` | Stock Alerts |
| Units Sold (30d, variant) | `SUM(order_details.qty)` where delivered and `orders.delivered_at >= now() - 30 days` | variant | `order_details` + `orders` | rolling 30 days | `IMPLEMENTED` | Stock Alerts (feeds Avg Daily Sold) |
| Avg Daily Sold (variant) | `Units Sold (30d, variant) / 30` | variant | derived | rolling 30 days | `IMPLEMENTED` | Stock Alerts |
| Days Remaining (variant) | `available / Avg Daily Sold`, floored to whole days and clamped at 0; null when Avg Daily Sold = 0 | variant | derived (`variant_stocks.available`) | current balance + rolling 30 days | `IMPLEMENTED` | Stock Alerts |

**Stock Alerts velocity rule.** The three velocity columns are variant-grain and read delivered sales only (`['delivered','4']`) on `orders.delivered_at`, never on the order's creation date: a line sold today but delivered last week belongs to the week it was delivered. Lines whose `order_details.variant_id` is null cannot be attributed to a variant and are excluded from these columns. The window is the 30 days before the request (`now() - 30 days`) and the denominator is always 30, so a variant with 2 delivered units shows 0.07/day rather than 2/day. Last Sold is lifetime, not windowed. Days Remaining is an estimate: `floor(available / avg)`, 0 when `available <= 0`, and the page prints an em dash when there were no delivered sales in the window (a zero average has no days to divide by); it never prints infinity.

### 3.8 Marketing efficiency

| Metric | Formula | Grain | Source | Date basis | Status | Surfaces |
|---|---|---|---|---|---|---|
| Marketing Spend | `SUM(marketing_spends.spent)` | spend row | `marketing_spends` | `spent_at` | `IMPLEMENTED` | Marketing report, dashboard |
| Attributed Delivered Revenue | `SUM(orders.amount)` where delivered and the order has an `order_attributions` row | order | `orders` + `order_attributions` | `orders.delivered_at` | `IMPLEMENTED` | Marketing report, dashboard |
| Attributed Delivered Orders | `COUNT(orders.id)` over the same set | order | same | `orders.delivered_at` | `IMPLEMENTED` | Marketing report |
| New Customers (attributed) | customers whose first-ever delivered order (`MIN(orders.delivered_at)` per customer) falls in the range, grouped by that order's `order_attributions.source` | customer | `orders` + `order_attributions` | `orders.delivered_at` | `IMPLEMENTED` | Marketing report |
| ROAS | `Attributed Delivered Revenue / Marketing Spend`; null when spend is 0 | range | derived | spend `spent_at`, revenue `delivered_at` | `IMPLEMENTED` | Marketing report, dashboard |
| CPA | `Marketing Spend / Attributed Delivered Orders`; null when there are no orders | range | derived | same | `IMPLEMENTED` | Marketing report, dashboard |
| CAC | `Marketing Spend / New Customers`; null when there are no new customers | range | derived | same | `IMPLEMENTED` | Marketing report, dashboard |
| Unattributed Delivered Revenue | `SUM(orders.amount)` for delivered orders in range with no `order_attributions` row | order | `orders` | `orders.delivered_at` | `IMPLEMENTED` | Marketing report footer |

**Marketing efficiency rule.** Spend and revenue are counted on two different clocks by nature: `marketing_spends.spent_at` is the day the money left, `orders.delivered_at` is the day the revenue became real, and the report states both. An order has exactly one primary attribution (`order_attributions.order_id` is unique, migration `2026_11_28_000002`), so the revenue join cannot multiply an order. Delivered revenue without an attribution row is never divided by spend: it is reported as Unattributed Delivered Revenue, because attributing it to a channel would invent a return. A channel with spend but no delivered order prints "-" for ROAS and CAC rather than 0 or infinity. Both the dashboard and the Marketing report read `MarketingMetricsService`, so they cannot show different numbers for the same range.

### 3.9 Cohort retention

| Metric | Formula | Grain | Source | Date basis | Status | Surfaces |
|---|---|---|---|---|---|---|
| Cohort Month | `DATE_FORMAT(MIN(orders.delivered_at), '%Y-%m')` per customer | customer | `orders` | `delivered_at` | `IMPLEMENTED` | Cohort report |
| Cohort Size | `COUNT(customer_id)` whose first delivered month equals the cohort month | cohort | `orders` | `delivered_at` | `IMPLEMENTED` | Cohort report |
| Repeat(k) | `COUNT(DISTINCT customer_id)` of the cohort with a delivered order at offset k whole months from the cohort month | cohort + offset | `orders` | `delivered_at` | `IMPLEMENTED` | Cohort report |
| Retention(k) | `Repeat(k) / Cohort Size`; `Repeat(0)` is the acquisition month itself | cohort + offset | derived | `delivered_at` | `IMPLEMENTED` | Cohort report |

**Cohort retention rule.** A customer's cohort is fixed by history — the month of their first delivered order — and never moves. Only delivered orders (`order_status IN ('delivered','4')`) count, on `orders.delivered_at`: a placed order that never shipped is not a customer relationship. Every cell is a distinct-customer count, so two delivered orders in one month count the customer once. The date filter selects which cohort months are listed; a listed cohort still shows its later activity even when that activity falls outside the filter, because retention is a property of the cohort, not of the window. The page shows M0 to M6 (`CohortRetentionService::MAX_OFFSET`), and the totals row sums per-cohort cells, which is valid because a customer belongs to exactly one cohort.

### 3.10 Cart lifecycle (abandoned carts)

| Metric | Formula | Grain | Source | Date basis | Status | Surfaces |
|---|---|---|---|---|---|---|
| Cart Snapshot | `item_count`, `subtotal` of the session cart, refreshed on every cart mutation | cart lifecycle | `abandoned_carts` | `last_activity_at` | `IMPLEMENTED` | Abandoned Carts |
| Last Activity | the moment of the latest cart mutation for that cart key | cart lifecycle | `abandoned_carts` | `last_activity_at` | `IMPLEMENTED` | Abandoned Carts |
| Abandoned Cart | a cart whose stored status is `abandoned`: it was `open` and idle longer than the configured window | cart lifecycle | `abandoned_carts` | `abandoned_at` | `IMPLEMENTED` | Abandoned Carts |
| Abandoned Value | `SUM(abandoned_carts.subtotal)` over rows with status `abandoned` | cart lifecycle | `abandoned_carts` | stored subtotal | `IMPLEMENTED` | Abandoned Carts |
| Recovered Cart | a cart an admin marked recovered; `recovered_at` is written at that moment | cart lifecycle | `abandoned_carts` | `recovered_at` | `IMPLEMENTED` | Abandoned Carts |
| Converted Cart | a cart that produced an order; `order_id` and `converted_at` are written by `OrderPlacementService::place()` | cart lifecycle | `abandoned_carts` | `converted_at` | `IMPLEMENTED` | Abandoned Carts |
| Abandoned Window | `config('reports.abandoned_cart_window_hours')`, default 24, override `ABANDONED_CART_WINDOW_HOURS` | configuration | `config/reports.php` | not a date | `IMPLEMENTED` | Abandoned Carts, `carts:mark-abandoned` |

**Cart lifecycle rule.** The shopping cart itself is session-backed (`hardevine/shoppingcart`), so it leaves no durable evidence on its own. `abandoned_carts` is that evidence: one row per cart key (the session id), written by `CartActivityRecorder` on every cart mutation and read by the report. Statuses are stored, never inferred: `open` means items are in the cart, `abandoned` is written by `carts:mark-abandoned` for `open` rows idle past the window, `converted` is written by `OrderPlacementService::place()` together with the `order_id`, and `recovered` is written by the admin action together with `recovered_at`. An empty cart creates no row; a converted cart that sees new items in the same session reopens as a new lifecycle, because the previous conversion already lives on its order.

## 4. Surface map

| Surface | Route | Metrics shown | Date basis |
|---|---|---|---|
| Reports Dashboard | `/admin/reports/dashboard` | Orders placed, Delivered Revenue, Gross Product Sales, Net Sales, AOV, Gross Profit, Top Products table, Expense (incl. Business Expenses), Estimated Net Profit A, previous-period %, Payment mix, Delivery Success Rate, Coupon metrics, Stock signal | mixed; each card states its basis |
| Sales Report | `/admin/order-report` | Gross Product Sales, COGS, order totals; delivered orders only; line view and order view toggle; summary cards (Filtered Delivered Orders, Order Discount, Shipping Charged, Order Total) | `orders.delivered_at`; summary cards use the page filter (`order_details.updated_at`) |
| Product Report | `/admin/stock-report` | stock units, retail value, cost value, Product Units Sold, Product Revenue (delivered), Product COGS | `products.updated_at` for the product list; sales columns are lifetime delivered |
| Report export | `/admin/report-export/{stock\|order}` | the source page's table plus a `TOTAL` row; `format=xlsx` (real workbook) or `format=csv`; Sales Report follows `view=line\|order` | same as the source page |
| Sales & Profit | `/admin/reports/overview` | Order Value (placed), Delivered Revenue, COGS, Gross Profit, Courier Cost, Shipping Margin, Refund Required/Paid/Pending, Business Expenses, Net Profit A/B | `orders.created_at`, `orders.delivered_at` and `business_expenses.spent_at` |
| Payment Methods | `/admin/reports/payments` | Collected Payment, Pending Payment, Payment Success Rate, Average Paid Payment | `payments.created_at` |
| Courier Performance | `/admin/reports/couriers` | Shipments, Delivered/Returned/In-Transit, Courier Cost, Delivery Success Rate, Courier Average Cost | `courier_shipments.created_at`; status from `status_normalized` |
| Returns & Cancellations | `/admin/reports/returns` | Return Rate (order, distinct orders), Return Rate (item), Refund Required/Paid/Pending, Cancelled Orders, Cancel Rate | `order_returns.created_at` and `orders.created_at` |
| Coupon Impact | `/admin/reports/coupons` | Coupon Usage Count, Coupon Discount, Coupon Revenue, Coupon AOV | `coupon_usages.created_at` |
| Stock Alerts | `/admin/reports/stock-alerts` | On Hand, Reserved, Available, Damaged, Stock Status Buckets, Inventory Movement Count, Last Sold, Avg Daily Sold (30d), Days Remaining | current and `inventory_movements.created_at`; velocity on `orders.delivered_at` (rolling 30 days) |
| Report Health | `/admin/reports/health` | data-quality and access checks, not business metrics; the old `/admin/reports/summary` URL 301-redirects here | current |
| Report Settings | `/admin/reports/settings` | stock thresholds, report email schedule | current |
| Daily email | `SendDailyReport` | dashboard summary metrics | dashboard range |

## 5. Known mismatches and open items

| # | Item | Evidence | Impact | Next step |
|---|---|---|---|---|
| 1 | ~~Return Rate (order) used return rows, not distinct orders~~ resolved 2026-11-28 | `returnsReport` now runs `distinct()->count('order_id')`; `AdminReportReturnRateTest` gives one order two returns and asserts the rate counts it once | the rate now describes orders, not return paperwork | fixed |
| 2 | ~~Gross Profit was not computed as `Net Sales - COGS`~~ resolved 2026-11-28 | `ReportMetricsService::kpiTotals` returns `gross_profit = net_sales - cogs`; the dashboard prints it as its own KPI and Sales & Profit prints it in the arithmetic table | the name now has exactly one formula; the Net Profit numbers keep their own names | fixed |
| 3 | Global AOV is missing | ~~only Coupon Impact has `avg_order_value`~~ resolved 2026-11-28 | resolved | AOV is on the dashboard (`data-kpi="aov"`), `gc-mwzcuyey` |
| 4 | Gross Product Sales has no KPI | ~~only the Sales Report line table sums `sale_price * qty`~~ resolved 2026-11-28 | resolved | Dashboard KPI `data-kpi="product_sales"`, order-line grain on parent `created_at`, `gc-mwzcuyey` |
| 5 | ~~Return Rate (item) was missing~~ resolved 2026-11-28 | `returnsReport` sums `order_return_items.qty` over the return range and divides by placed `order_details.qty`; the Returns page shows `item-return-rate` | item-level return rate is now a real number | fixed |
| 6 | ~~COD Outstanding is not aggregated~~ resolved 2026-11-28 | `courierPerformance` sums `cod_amount - remitted_amount` per provider and in the range total; `AdminCodOutstandingTest` compares the page with an independent SQL sum and counts a `mystery_state` settlement row as unknown | COD collection can now be reconciled on the Courier page | fixed, `gc-mwzxs9g5` |
| 7 | ~~Payment settlement metrics are not aggregated~~ resolved 2026-11-28 | `paymentMethods` sums paid `gateway_fee`, `settled_amount` and `amount - gateway_fee - settled_amount`; `storePaymentSettlement` writes `payment_settlements` and recomputes the payment from its rows; `AdminPaymentSettlementTest` compares the page gap with SQL | collected, settled and the gap are visible per method | fixed, `gc-mwzxy9w7` |
| 8 | ~~Courier settlement metrics are not aggregated~~ resolved 2026-11-28 | `courierPerformance` shows expected COD, remitted, return charge and shortfall; `storeCourierSettlement` writes `courier_settlements` and recomputes `remitted_amount` from its rows; `AdminCourierSettlementTest` compares the page shortfall with SQL | remittance and return charges are now reportable | fixed, `gc-mwzy4hc3` |
| 9 | District and division reports are not built | `shippings.district_id` and `shippings.division` exist (`2026_11_27_000003`); `districts` exists but has 0 rows | no district-level sales, returns or courier report | seed `districts`, make district mandatory at checkout, then add the report |
| 10 | ~~In-Transit Shipments was derived~~ resolved 2026-11-28 | `CourierShipment` writes `status_normalized` on save via `CourierStatusNormalizer`; `courierPerformance` counts `status_normalized = 'in_transit'` and never subtracts | an unknown provider word is no longer counted as in transit | fixed |
| 11 | ~~Stock status ignored the per-variant threshold~~ resolved 2026-11-28 | `ReportStockAlertController` and `StockAlertSignal` use `low_stock_threshold` when it is greater than zero, otherwise the global value; the remaining band is `max(global remaining, effective low line)` | a variant-specific threshold now changes the bucket | fixed |
| 12 | ~~Dashboard Net Profit excluded other business expenses~~ resolved 2026-11-28 | `ReportMetricsService::kpiTotals` adds `SUM(business_expenses.amount)` by `spent_at`; the card is labelled "Estimated Net Profit" and the badge lists operating expenses | the number now covers the cost of running the business | fixed |
| 13 | ~~Report Summary was a code audit page~~ resolved 2026-11-28 | `ReportHealthController` runs report registry and data-quality checks; old URL redirects | the name suggested a business summary | renamed to Report Health with a 301 from the old URL; the printable business report stays separate |

## 6. Change process

1. Add or change the metric in this file first: formula, grain, source, date basis, status.
2. Add a regression test that recomputes the number from the database.
3. Update the page label and the surface map.
4. If a new column is required, add it as a separate C-ID migration; do not hide schema work inside a report query.
5. Run `php artisan test --filter=AdminReport`.
6. Record the decision and the test result in the syllabus.

## 7. Evidence and verification

**Code evidence**

- `app/Services/OrderPlacementService.php:70` — order amount formula.
- `app/Http/Controllers/Admin/OrderController.php:1789,2189` — POS amount formula.
- `app/Http/Controllers/Admin/OrderController.php:49-53` — POS total discount.
- `app/Services/Admin/ReportMetricsService.php` — dashboard, final report and email metrics; coupon revenue uses distinct orders.
- `app/Http/Controllers/Admin/ReportOverviewController.php` — ordered revenue, delivered revenue, COGS, courier cost, shipping margin, Net Profit A/B.
- `app/Http/Controllers/Admin/ReportAnalyticsController.php` — payment, courier, returns and coupon queries; delivered and cancelled status constants.
- `app/Http/Controllers/Admin/ReportStockAlertController.php` — stock balances and status buckets.
- `app/Http/Controllers/Admin/ReportHealthController.php` — data-quality checks.
- `routes/web.php:1504-1558` — report routes and permission middleware.

**Test evidence**

- `php artisan test --filter=AdminReport` -> 31 passed, 233 assertions (2026-11-27).
- `tests/Feature/AdminReportCouponRevenueTest.php` -> 1 passed, 7 assertions; inserts a second usage on the same order and proves coupon revenue counts the order once.

**Live data evidence (2026-11-27)**

- `orders` 31, `order_details` 36, `payments` 31, `courier_shipments` 6, `order_returns` 2, `coupon_usages` 3, `inventory_movements` 152, `variant_stocks` 90, `customers` 11, `districts` 0, `event_logs` 71.
- Orders without a shipment: 25.
- Coupon revenue: distinct-order sum 2,480; naive join sum 2,480 today; the regression fixture makes the naive sum 3,410 and the correct sum 2,480.

## 8. Change log

| Date | Change | Reason | Contract |
|---|---|---|---|
| 2026-11-27 | Initial lock of the metric dictionary | One definition per metric; stop three different "revenue" numbers | `gc-mwz8l4as` |
| 2026-11-27 | Coupon Revenue fixed to distinct orders | Two usages on one order multiplied the order amount | `gc-mwz7mvsi` |
| 2026-11-28 | Gross Product Sales, Net Sales and AOV added to the dashboard; every headline KPI gained a previous-period percentage | The dictionary proposed AOV and had no global surface for product revenue; a single number with no comparison is not a trend | `gc-mwzcuyey` |
| 2026-11-28 | Top Products table added to the dashboard at the order-line grain on parent `created_at`, same basis as Gross Product Sales | report-management-final section 8 item 5: a ranking table was the missing drill-down under the product revenue card | `gc-mwzhfnde` |
| 2026-11-28 | Sales Report gained order-grain summary cards (Filtered Delivered Orders, Order Discount, Shipping Charged, Order Total) and a Line/Order view toggle; the date filter now uses whole-day bounds and applies each bound on its own | report-management-final section 8 item 6: the page only summed lines, so an order-level discount or total did not exist and End Date excluded its own day | `gc-mwzi18yq` |
| 2026-11-28 | Product Report gained lifetime delivered Product Units Sold, Product Revenue and Product COGS per product and in the footer; both report date filters now use `< next day 00:00` instead of `<= 23:59:59` (rule 1.3) | report-management-final section 8 item 7: the report could only see stock, so a product's sales history was invisible | `gc-mwzu7jd8` |
| 2026-11-28 | Stock Alerts gained variant Last Sold, Units Sold (30d), Avg Daily Sold and Days Remaining; one aggregate query over delivered lines on `orders.delivered_at` feeds all three columns | report-management-final section 8 item 8: a balance with no velocity cannot tell a dead variant from a fast mover | `gc-mwzuhrpj` |
| 2026-11-28 | Product Report and Sales Report exports are real XLSX/CSV downloads over the whole filter (same query source as the page, `TOTAL` row, no pagination); the browser print dialog is the PDF path | report-management-final section 8 item 9: the Excel button downloaded a page-one CSV | `gc-mwzuqp6j` |
| 2026-11-28 | Report Summary renamed to Report Health (`ReportHealthController`, `reports/health`, sidebar label); `/admin/reports/summary` keeps working as a 301 behind the same `report-sales-view` permission | report-management-final section 8 item 10: the page is a data-quality audit, and the name promised a business summary | `gc-mwzvfpfm` |
| 2026-11-28 | Six metric-truth fixes: distinct-order Return Rate, Gross Profit = Net Sales - COGS, item Return Rate, `status_normalized` written on every shipment save and read by the Courier page, per-variant `low_stock_threshold`, and business expenses inside Estimated Net Profit. Section 5 #6-#9 corrected: the columns now exist, only the aggregates/UI are missing | the dictionary and the pages disagreed on six numbers; three "columns do not exist" sentences were stale after the Wave 1 migrations | `gc-mwzw7e3l` |
| 2026-11-28 | Marketing efficiency locked in section 3.8: Marketing Spend, Attributed Delivered Revenue, ROAS, CPA, CAC and Unattributed Delivered Revenue, all from `MarketingMetricsService`; dashboard blocks and the Marketing report share it | Phase 3 item 3 needed one written definition of ROAS before two screens could show it | `gc-mx015bem` |
| 2026-11-28 | Cohort retention locked in section 3.9: cohort = month of first delivered order, size, Repeat(k) and Retention(k) for M0-M6, from `CohortRetentionService`; the cohort report is the only surface | Phase 3 item 4 needed the cohort definition written before a second screen could invent a different retention number | `gc-mx01cvgj` |
| 2026-11-28 | Cart lifecycle locked in section 3.10: `abandoned_carts` stores one row per session cart with item count, subtotal, last activity and a status of open/abandoned/recovered/converted; `carts:mark-abandoned` applies the configurable window and `OrderPlacementService` stores the conversion | Phase 3 item 5 requires the recovery state to be stored, not inferred from logs | `gc-mx02ch15` |
