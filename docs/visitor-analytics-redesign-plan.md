# Visitor & Analytics Admin Redesign — Section Plan

**Status:** proposal for design approval. No UI code has been written.
**Project:** `today` v1.0.0 (Laravel), admin panel.
**Template source:** Materialize v3.0.0 by Pixinvent, `vertical-menu-template`.
**Template root:** `/home/sahon/Desktop/admin html/html/vertical-menu-template/`
**Project assets:** `/home/sahon/Desktop/new evo/public/admin/assets/`
**Prepared:** 2026-11-02

---

## 1. Purpose and scope

This document defines the section order, the exact template component to reuse, the real data source, the responsive behaviour, the plain-English labels, and the empty/low-data state for two admin pages:

1. **Visitors** — who is visiting, where they come from, what they look at.
2. **Analytics** — how much traffic there is, where it leaks, and what to focus on.

The client will supply the visual design direction after reading this plan. This document deliberately stops before markup, CSS, or JavaScript changes.

---

## 2. Design principles

| Principle | What it means in the UI |
| --- | --- |
| One question per section | Each card answers one admin question in its title. No mixed-purpose cards. |
| Plain English first | Labels say `Visitors`, `Pages viewed`, `Where we lose them`; never `CAPI`, `deduplication`, `attribution`, `bounce`. |
| Truth before decoration | If the data is missing, show a clear empty state. Never fill a chart with estimates. |
| People and bots are separate | The default view is real people. A visible toggle shows hidden bot traffic. |
| Mobile is a first-class layout | Every table becomes stacked cards or a child-row DataTable below `768px`. |
| One filter bar per page | The same date range and filters apply to every section on that page. |
| No duplicate tables | A dataset appears once per page; the other page links to it. |
| English only | No Bengali text in the interface. Bengali is used only in the audio explanation. |

---

## 3. Page map

| Page | Route | Job | Primary user question |
| --- | --- | --- | --- |
| Visitors | `admin/visitors/manage` | Audience and behaviour | Who is visiting, from where, and what did they look at? |
| Analytics | `admin/analytics` | Traffic and conversion | How much traffic, where does it leak, and what should I fix first? |
| Live | `admin/analytics/live` | Real-time feed | Who is on the site right now? |

The existing Live page stays as a separate drill-down. It is not rebuilt in this scope; the Analytics page links to it.

---

## 4. Shared components

### 4.1 Shared filter bar

**Template source:** `index.html` card header dropdown pattern (lines 2150–2162) plus the existing filter form in `resources/views/backEnd/analytics/index.blade.php`.

**Contents:**

- Date range preset: `Today`, `Yesterday`, `Last 7 days`, `Last 30 days`, `Last 90 days`, `Custom`.
- Custom date inputs (Flatpickr range, project asset already present).
- `People only` / `Include bots` toggle. Default: `People only`.
- `Apply` button and `Reset` link.
- A small line: `Showing 2 Oct – 2 Nov 2026 · 108 people · 200 bot visits hidden`.

**Behaviour:** submitting reloads the page with query parameters. No client-side filtering of aggregates, so the numbers always match the server.

**Responsive:** four columns on desktop, two on tablet, one stacked column on mobile. The `Apply` button becomes full width below `576px`.

### 4.2 Visitor detail drawer

**Template source:** `ui-offcanvas.html` `#offcanvasEnd` (line 1820) and the existing `#visitorDrawer` in `resources/views/backEnd/analytics/index.blade.php`.

**Contents:**

- Header: visitor ID, first seen, last seen, status badge.
- Tabs using `app-ecommerce-customer-details-overview.html` `.nav-pills` pattern (line 1895): `Overview`, `Pages`, `Products`.
- Overview: location, device, browser, source, total page views, total events.
- Pages: ordered list of page paths with time.
- Products: product cards with view/add-to-cart/order markers.
- Journey timeline using `index.html` `.timeline.card-timeline` (line 2524).
- Footer actions: `Block visitor`, `Copy visitor ID`.

**Responsive:** right drawer at `min(560px, 92vw)` on desktop; full-width bottom sheet below `576px`.

### 4.3 Empty and low-data state

**Template source:** Bootstrap utility classes already used in the project (`text-center py-10`, `avatar-initial`, `bg-label-secondary`).

**Rules:**

- No data at all: icon + one plain sentence + one action link.
- Some data but below the sample threshold: show the count and a progress hint, not a percentage.
- Data error: `We could not load this section` + `Retry` button + timestamp of last successful load.
- Never show `0%` when the denominator is zero.

**Example copy:**

> No location data yet
> We have not received a usable district for these visitors. Check the location setup, or come back after the next visit.

### 4.4 Section header pattern

Every card uses the same header structure:

- Left: section title (one question) and a one-line subtitle with the current range.
- Right: a single action: `View all`, `Export`, or a `More` dropdown from `index.html` (line 2152).
- No more than one primary action per card.

### 4.5 Insight card

**Template source:** `cards-statistics.html` stat-card shell with `avatar-initial bg-label-*` (lines 1798–1900) and the `index.html` KPI grid.

**Contents:**

- A short finding in plain English.
- The number that supports it.
- A `Why this matters` line.
- A `See the numbers` link that scrolls to the relevant section.
- A confidence note: `Based on 12 people` or `Collecting data (12/30)`.

**Rule:** an insight is only shown when the sample is large enough. Otherwise the card says what is still being collected. No invented recommendations.

---

## 5. Page A — Visitors

**Route:** `admin/visitors/manage`
**Job:** answer who is visiting and what they do, person by person and district by district.

### A1. Page header

**Question:** What am I looking at?

**Contents:**

- Title: `Visitors`
- Subtitle: `People who visited your shop in the selected period.`
- Right side: `Export CSV` button and a `Last updated` timestamp.
- The shared filter bar directly below.

**Responsive:** title and actions stack on mobile; export button becomes icon + label.

### A2. Audience at a glance

**Question:** How many people came, and are they new or returning?

**Template source:** `cards-statistics.html` KPI cards; `index.html` KPI grid (lines 1798–1900).

**Cards (five):**

| Card | Plain label | Real calculation |
| --- | --- | --- |
| 1 | `People` | Distinct `visitor_id` in `visitors_master` for the range, excluding bots by default. |
| 2 | `New` | Visitors whose `created_at` falls in the range. |
| 3 | `Returning` | Visitors with more than one session or a `last_seen_at` after `created_at`; show the definition in a tooltip. |
| 4 | `Pages per person` | `SUM(pageview_count) / COUNT(DISTINCT visitor_id)`, one decimal. |
| 5 | `Orders from visitors` | Distinct orders in `orders` linked to a visitor in the range. |

**Delta:** a seven-day comparison with an arrow and `vs previous 7 days`. If the previous period has no data, show `No comparison yet` instead of a fake percentage.

**Responsive:** five cards in one row at `≥1400px`; three + two at `≥992px`; two columns at `≥576px`; one column below.

### A3. Where visitors come from

**Question:** Which district or division are my visitors coming from?

**Template source:** `maps-leaflet.html` `#basicMap` and `.leaflet-map`; project Leaflet asset at `public/admin/assets/vendor/libs/leaflet/leaflet.js` (147,552 bytes) and `leaflet.css` (14,806 bytes).

**Layout:** two columns on desktop, stacked on mobile.

- Left: Bangladesh map with one circle per division. Circle size = visitor count; colour = low/medium/high. Clicking a circle filters the page to that division.
- Right: `Top districts` table with columns `District`, `Visitors`, `Share`, `Orders`. The table is the accessible fallback for the map.
- A small `Map data` note: `Based on 108 people with a resolved location.`

**Data:** `visitors_master.division`, `visitors_master.city`, and a new nullable `visitors_master.district` column. `ip_geo_cache` already stores `district` but `visitors_master` does not, so the column and the write path must be added before this section can show real districts.

**Empty state:** `No location data yet` + `We have not received a usable district for these visitors.` + a `Location setup` link for the admin. The map is not drawn with placeholder countries.

**Responsive:** map height `360px` desktop, `280px` tablet, `240px` mobile. The district table becomes a two-line list on mobile: district name on top, `12 visitors · 4 orders` below.

### A4. Which pages they visited

**Question:** Which pages do people open most, and where do they leave immediately?

**Template source:** `tables-datatables-advanced.html` `.dt-responsive` (line 1933) and the `cards-analytics.html` `#weeklySalesChart` horizontal bar shell.

**Contents:**

- A horizontal bar for the top five pages.
- A DataTable below with columns: `Page`, `Views`, `People`, `Left immediately`, `Last seen`.
- `Page` shows a human name (`Home`, `Product: Premium 3D Canvas`) with the raw path in a tooltip.
- `Left immediately` is the share of visitors whose only page view was this page. Plain label, no `bounce` word.

**Data:** `event_logs.page_path`, `event_logs.event_name = 'PageView'`, `event_logs.visitor_id`. Exclude non-page endpoints listed in section 9.6.

**Empty state:** `No page views in this period` + `Tracking may not be receiving browser events.` + `Open live feed`.

**Responsive:** the bar chart hides below `768px`; the table uses DataTables responsive child rows. Columns `Views` and `People` stay visible; the rest collapse.

### A5. Which products they looked at

**Question:** Which products attract attention, and which ones turn attention into orders?

**Template source:** `app-ecommerce-customer-details-overview.html` product card pattern and `tables-datatables-advanced.html` `.dt-responsive-child` (line 1973).

**Contents:**

- Product table with thumbnail, product name, `Views`, `Added to cart`, `Orders`, `View → order` rate.
- The thumbnail uses the existing product image path; a missing image falls back to the template's `avatar-initial`.
- `View → order` is hidden until the product has at least 30 views; below that show `Collecting data (12/30)`.
- Sort default: `Views` descending.

**Data:** `event_logs.page_path` matched against product routes `/product/{slug}` and `/products/show/{slug}`; join `products.slug`. `AddToCart` events matched by visitor and page path. Orders from `order_details` joined to `products.id`.

**Verified mapping:** `premium-3d-canvas-cwc-201-1` → product 1, `artisan-raw-honey` → product 14, `organic-spinach-bundle` → product 17. These are real product rows, not fixtures.

**Empty state:** `No product views yet` + `Product pages will appear here after the first visit.`

**Responsive:** table becomes product cards on mobile: image, name, three small numbers in a row. No horizontal scroll.

### A6. All visitors

**Question:** Who exactly visited, and what should I do with this person?

**Template source:** `tables-datatables-advanced.html` `.dt-responsive-child`; the existing `visitors.blade.php` table and bulk-action bar.

**Columns:**

| Column | Content | Mobile |
| --- | --- | --- |
| Visitor | Short ID + status dot | Visible |
| Location | `City, Division` or `Unknown` | Visible as second line |
| Device | `Mobile`, `Desktop`, `Tablet`, `Bot` | Child row |
| Source | `Direct`, `Facebook`, `Google`, `Unknown` | Child row |
| Pages | Number of page views | Visible |
| Last seen | Relative time (`3 min ago`) | Visible |
| Action | `View` button | Visible |

**Behaviour:**

- Search by visitor ID, IP, city, or ISP.
- Filter by status (`Active`, `Blocked`), device, division, and date range.
- Bulk select with `Block` and `Unblock`, reusing the existing routes.
- Row click opens the visitor detail drawer (section 4.2).
- Pagination: 50 per page, server-side.

**Empty state:** `No visitors match these filters` + `Clear filters`.

**Responsive:** below `768px`, the DataTable child row shows device, source, browser, and a `View profile` button. The bulk action bar becomes a sticky bottom bar with a count.

### A7. Visitor profile drawer

**Question:** What did this one person do?

**Template source:** `ui-offcanvas.html` `#offcanvasEnd`, `app-ecommerce-customer-details-overview.html` tabs, `index.html` timeline.

**Contents:**

- Identity: visitor ID, first seen, last seen, status.
- Location: city, division, district (when available), ISP, country.
- Device: device type, browser, OS, user agent (truncated with a copy button).
- Source: channel, referrer host, landing page, UTM values.
- Activity: total page views, total events, last page.
- Timeline: `Landed` → `Viewed product` → `Added to cart` → `Checkout` → `Order`, with timestamps and gaps.
- Actions: block/unblock, copy visitor ID.

**Data:** `visitors_master` row + `event_logs` for the visitor, ordered by `event_time`, plus `orders` for the visitor.

**Empty state:** `No activity recorded for this visitor yet.`

---

## 6. Page B — Analytics

**Route:** `admin/analytics`
**Job:** answer how much traffic there is, where it leaks, and what to fix first.

### B1. Page header and filter bar

**Question:** What period and segment am I looking at?

**Contents:**

- Title: `Analytics`
- Subtitle: `Traffic, pages, and orders for the selected period.`
- Shared filter bar (section 4.1) with the addition of `Compare to previous period` toggle.
- A visible `Data quality` chip when bot traffic is hidden or location data is missing.

**Responsive:** same stacking rules as A1.

### B2. Total view

**Question:** What is the total picture in one glance?

**Template source:** `index.html` KPI grid (lines 1798–1900); `cards-statistics.html` stat cards.

**Cards (five):**

| Card | Plain label | Real calculation |
| --- | --- | --- |
| 1 | `Page views` | Count of `event_logs` with `event_name = 'PageView'` in range. |
| 2 | `People` | Distinct `visitor_id` in `event_logs` in range, bots excluded by default. |
| 3 | `Orders` | Count of `orders` created in range. |
| 4 | `Revenue` | `SUM(orders.amount)` in range, formatted in BDT. |
| 5 | `Conversion` | `Orders / People`, shown only when `People ≥ 30`; otherwise `Collecting data`. |

**Why these five:** they answer `how many`, `how many people`, `how many buyers`, `how much money`, and `how well we convert` without jargon.

**Delta:** comparison to the previous equal-length period. If the previous period has no data, show `No comparison yet`.

**Responsive:** five across at `≥1400px`, three + two at `≥992px`, two columns at `≥576px`, one below.

### B3. Traffic over time

**Question:** Are visits going up or down?

**Template source:** `index.html` `#totalTransactionChart` area/line card (line 1800) and `cards-analytics.html` `#performanceOverviewChart` (line 1881).

**Contents:**

- Area chart with three series: `Page views`, `People`, `Orders`.
- Daily granularity for ranges up to 90 days; weekly for longer ranges.
- Hover tooltip shows the date and all three numbers.
- A segmented control: `Views` / `People` / `Orders` to isolate one series on mobile.
- A one-line summary above the chart: `Page views are up 18% compared with the previous 7 days.` If the change is not meaningful, say `About the same as the previous period.`

**Data:** `event_logs.event_time` for views and people; `orders.created_at` for orders. Use `event_time`, not `created_at`, so late-arriving events land on the correct day.

**Empty state:** `No traffic in this period` + `Try a longer date range.`

**Responsive:** chart height `320px` desktop, `260px` tablet, `220px` mobile. The segmented control moves below the title on mobile.

### B4. Where we lose them

**Question:** Where do most customers drop off?

**Template source:** `index.html` funnel-style card shell and `cards-analytics.html` `#performanceOverviewChart`; the existing funnel markup in `analytics/index.blade.php` (lines 444–530).

**Funnel steps (five):**

1. `Landed` — distinct visitors with a `PageView`.
2. `Viewed a product` — distinct visitors with a `page_path` matching a product route. Do **not** rely on `ViewContent`; the current event log has zero `ViewContent` rows.
3. `Added to cart` — distinct visitors with `AddToCart`.
4. `Reached checkout` — distinct visitors with `BeginCheckout` or `InitiateCheckout` (both spellings exist in the data).
5. `Ordered` — distinct visitors with an order in `orders`.

**Display:**

- Horizontal bars with the count and the share of the first step.
- Between steps: `−42 people` and `−38%` in a red label.
- The biggest drop gets a callout: `Most people leave after viewing a product.` + `See the product pages`.
- Percentages are hidden until the step has at least 30 people; below that show `Collecting data (12/30)`.

**Why orders come from `orders`, not `Purchase` events:** the current data has 29 `Purchase` events but only 3 orders. Event counts are not sales. The funnel's last step must use the order table.

**Empty state:** `Not enough journey data yet` + `We need at least 30 people at each step to show drop-off.`

**Responsive:** vertical stepper on mobile; horizontal bars on desktop.

### B5. Traffic sources

**Question:** Where is the traffic coming from?

**Template source:** `cards-analytics.html` `#shipmentStatisticsChart` card shell for a donut; `index.html` referral tabs pattern.

**Contents:**

- Donut: `Direct`, `Facebook`, `Google`, `Other`, `Unknown`.
- Table: `Source`, `People`, `Orders`, `Conversion`.
- A note when one source is above 90%: `Most traffic is direct or unknown. Add UTM tags to your ad links to see campaign performance.`

**Data:** `visitors_master.channel`, `visitors_master.utm_source`, `visitors_master.referrer_host`. The current data is 382 direct / 10 null, so the note is expected to show.

**Empty state:** `No source data yet` + `UTM tags will make this section useful.`

**Responsive:** donut above the table on mobile; side by side on desktop.

### B6. Visits calendar

**Question:** Which days were busy, and which days were quiet?

**Template source:** `app-calendar.html` `#calendar` and `.app-calendar-wrapper` (line 1795); project FullCalendar asset at `public/admin/assets/vendor/libs/fullcalendar/fullcalendar.js` (265,721 bytes).

**Contents:**

- Month view. Each day cell shows a small badge with the number of people.
- Colour scale: light for low, primary for medium, dark for high. No red/green judgement.
- Clicking a day opens a side panel with: people, page views, orders, top page, top product.
- A `Today` button and prev/next month controls.
- A one-line summary: `Busiest day: 28 October, 34 people.`

**Data:** `visitors_master.created_at` for new people; `event_logs.event_time` for activity; `orders.created_at` for orders.

**Empty state:** `No visits recorded this month.`

**Responsive:** month grid becomes a list of weeks below `576px`; each day is a row with date and count. The side panel becomes a bottom sheet.

### B7. Devices and peak hours

**Question:** How do people browse, and when are they most active?

**Template source:** `cards-analytics.html` `#visitsByDayChart` bar card (line 1911) and `index.html` `#overviewChart` radial card (line 1800).

**Contents:**

- Device donut: `Mobile`, `Desktop`, `Tablet`, `Bot`. Bot is shown only when `Include bots` is on.
- Browser bars: top five browsers.
- Peak hours: 24 bars, one per hour, with the busiest hour highlighted.
- Plain labels: `Most people browse on mobile` and `Busiest hour: 8 PM – 9 PM`.

**Data:** `visitors_master.device_type`, `browser`, `os`; `event_logs.event_time` for peak hours.

**Empty state:** `No device data yet.`

**Responsive:** donut and browser bars stack; peak-hours chart scrolls horizontally on very small screens, with the busiest hour pinned.

### B8. What to focus on

**Question:** What should I fix first?

**Template source:** `cards-statistics.html` insight card shell; `index.html` KPI grid.

**Contents:** three rule-based insight cards, maximum. Each card has:

- A plain finding.
- The number behind it.
- `Why this matters`.
- A link to the section that proves it.
- A sample-size note.

**Allowed rules (no machine learning):**

1. **Checkout leak** — if `Reached checkout` is at least 30 people and `Ordered / Reached checkout < 50%`, show: `Many people reach checkout but do not order.` Link to B4.
2. **Product page leak** — if `Viewed a product` is at least 30 and `Added to cart / Viewed a product < 20%`, show: `People look at products but rarely add them to the cart.` Link to A5.
3. **Mobile gap** — if mobile people are at least 30% of traffic and mobile conversion is less than half of desktop conversion, show: `Mobile visitors buy less often than desktop visitors.` Link to B7.
4. **Location gap** — if more than 20% of visitors have no division, show: `Location data is incomplete, so the district view is not reliable yet.` Link to A3.
5. **No data** — if none of the above has enough sample, show: `We are still collecting data.` + the next threshold.

**Why rule-based:** with 3 orders and 239 events, a predictive model would be noise. These rules are explainable, auditable, and useful on day one.

**Responsive:** three cards across on desktop, one per row on mobile.

### B9. Live now strip

**Question:** Is anyone on the site right now?

**Template source:** `app-ecommerce-dashboard.html` `#liveVisitors` card (line 2654) and the existing live table in `analytics/index.blade.php` (lines 596–640).

**Contents:**

- A compact strip, not a full table: `12 people on the site now`, `Top page: Home`, `Top source: Direct`.
- A sparkline for the last 30 minutes.
- `Open live feed` button linking to `admin/analytics/live`.
- Auto-refresh every 30 seconds; a pause toggle.

**Data:** `event_logs` in the last 5 minutes; distinct `visitor_id`. Use `event_time`, not `created_at`.

**Empty state:** `No one is on the site right now.` + `Last activity: 4 minutes ago.`

**Responsive:** three numbers stack on mobile; the sparkline hides below `576px`.

---

## 7. Component reuse map

| Template file | Component | Used in |
| --- | --- | --- |
| `cards-statistics.html` | KPI stat card, `avatar-initial bg-label-*` | A2, B2, B8 |
| `index.html` lines 1798–1900 | KPI grid and card shell | A2, B2 |
| `index.html` `#totalTransactionChart` (line 1800) | ApexCharts area/line card | B3 |
| `cards-analytics.html` `#performanceOverviewChart` (line 1881) | ApexCharts performance card | B3, B4 |
| `cards-analytics.html` `#visitsByDayChart` (line 1911) | ApexCharts distributed bar | B7 |
| `cards-analytics.html` `#weeklySalesChart` (line 1997) | ApexCharts horizontal bar | A4 |
| `cards-analytics.html` `#shipmentStatisticsChart` | ApexCharts donut card shell | B5 |
| `maps-leaflet.html` `#basicMap`, `.leaflet-map` | Leaflet map | A3 |
| `app-calendar.html` `#calendar`, `.app-calendar-wrapper` (line 1795) | FullCalendar month view | B6 |
| `tables-datatables-advanced.html` `.dt-responsive` (line 1933) | Responsive DataTable | A4 |
| `tables-datatables-advanced.html` `.dt-responsive-child` (line 1973) | DataTable with child rows | A5, A6 |
| `ui-offcanvas.html` `#offcanvasEnd` (line 1820) | Right drawer | A7, B6 day detail |
| `app-ecommerce-customer-details-overview.html` `.nav-pills` (line 1895) | Tabbed detail | A7 |
| `index.html` `.timeline.card-timeline` (line 2524) | Activity timeline | A7 |
| `app-ecommerce-dashboard.html` `#liveVisitors` (line 2654) | Live visitors card | B9 |
| `index.html` card header dropdown (line 2152) | Section action menu | 4.4 |

All vendor libraries are already present in `public/admin/assets/`:

| Library | Path | Size |
| --- | --- | --- |
| ApexCharts | `vendor/libs/apex-charts/apexcharts.js` | 567,538 bytes |
| Leaflet JS | `vendor/libs/leaflet/leaflet.js` | 147,552 bytes |
| Leaflet CSS | `vendor/libs/leaflet/leaflet.css` | 14,806 bytes |
| DataTables BS5 | `vendor/libs/datatables-bs5/datatables-bootstrap5.js` | 2,471,602 bytes |
| FullCalendar | `vendor/libs/fullcalendar/fullcalendar.js` | 265,721 bytes |
| Flatpickr | `vendor/libs/flatpickr/flatpickr.js` | 51,670 bytes |
| Select2 | `vendor/libs/select2/select2.js` | 78,820 bytes |

No new dependency is required for this plan.

---

## 8. Data contract per section

| Section | Primary source | Query rule | Empty / low-data rule |
| --- | --- | --- | --- |
| A2 People | `visitors_master` | Range on `created_at`; exclude `device_type = 'bot'` by default. | Show `0` with `No visitors in this period`. |
| A2 New / Returning | `visitors_master` | `created_at` in range; returning = `last_seen_at > created_at`. | Hide delta if previous period is empty. |
| A2 Pages per person | `visitors_master.pageview_count` | `SUM / COUNT(DISTINCT visitor_id)`. | Show `—` when people = 0. |
| A2 Orders from visitors | `orders` | Join visitor through the order's visitor/session key. | Show `0 orders` and a link to orders. |
| A3 Map / district | `visitors_master.division`, `.district`, `.city` | Group by division; district when available. | `No location data yet`; never draw fake districts. |
| A4 Pages | `event_logs` | `event_name = 'PageView'`; group by `page_path`; exclude AJAX endpoints. | `No page views in this period`. |
| A5 Products | `event_logs.page_path` + `products.slug` | Match `/product/{slug}` and `/products/show/{slug}`; join product. | `No product views yet`. |
| A6 Visitor list | `visitors_master` | Server-side pagination, 50 per page; filters as in A6. | `No visitors match these filters`. |
| A7 Drawer | `visitors_master` + `event_logs` + `orders` | One visitor, ordered by `event_time`. | `No activity recorded yet`. |
| B2 Page views | `event_logs` | `event_name = 'PageView'`, range on `event_time`. | `No page views in this period`. |
| B2 People | `event_logs` | `COUNT(DISTINCT visitor_id)`, bots excluded. | `0` with explanation. |
| B2 Orders / Revenue | `orders` | Range on `created_at`; `SUM(amount)`. | `No orders in this period`. |
| B2 Conversion | `orders` / `event_logs` | Orders / people; only when people ≥ 30. | `Collecting data (12/30)`. |
| B3 Trend | `event_logs.event_time`, `orders.created_at` | Daily buckets; weekly for long ranges. | `No traffic in this period`. |
| B4 Funnel | `event_logs` + `orders` | Five steps as defined in B4; orders from `orders`. | `Not enough journey data yet`. |
| B5 Sources | `visitors_master.channel`, `.utm_source`, `.referrer_host` | Group by source; map null to `Unknown`. | `No source data yet`. |
| B6 Calendar | `visitors_master.created_at`, `event_logs.event_time` | One row per day; count people and views. | `No visits recorded this month`. |
| B7 Devices / hours | `visitors_master.device_type`, `.browser`; `event_logs.event_time` | Group by device/browser; hour buckets. | `No device data yet`. |
| B8 Insights | Derived from B4, A5, B7, A3 | Rules in B8; minimum sample 30. | `We are still collecting data`. |
| B9 Live | `event_logs` | Last 5 minutes, distinct visitors. | `No one is on the site right now`. |

---

## 9. Data readiness gates

These are not design preferences. They are facts from the current database and code. The UI must not pretend otherwise.

### 9.1 Location data is not ready

- `visitors_master.division` is `NULL` for **392 of 392** rows.
- `ip_geo_cache` has **0** rows.
- `visitors_master` has no `district` column, although `GeoIpResolver` returns a district and `ip_geo_cache` stores one.
- Ten seeded rows have inconsistent country/city pairs (for example a United Kingdom IP mapped to `Chattogram`). They must not be presented as real geography.

**Required before the map is useful:**

1. Add a nullable `district` column to `visitors_master`.
2. Write `district` in `VisitorIdentify` when geo is resolved.
3. Configure a real geo database (MaxMind or an equivalent provider) and verify one known IP.
4. Backfill from `ip_geo_cache`; clear or re-resolve the ten inconsistent seed rows.
5. Add a coverage counter: `X of Y visitors have a location`.

**Until then:** A3 shows the empty state and B8 shows the `Location gap` insight. The map component is built and tested with a fixture, but the production page does not display invented districts.

### 9.2 Bot traffic is half the audience

- `device_type = 'bot'` for **200 of 392** visitors.
- Many are `127.0.0.1` with `curl` or HeadlessChrome user agents.
- The default `People only` view is therefore not cosmetic; it is required for a truthful number.

**Rule:** every KPI, chart, and table excludes bots by default. The filter bar shows `200 bot visits hidden`. Turning bots on changes the label to `Including bots` and adds a warning chip.

### 9.3 Purchase events are not orders

- `event_logs` has **29** `Purchase` events.
- `orders` has **3** rows.
- The difference is test/duplicate event traffic, not sales.

**Rule:** orders and revenue always come from `orders`. `Purchase` events may be shown only as `Purchase events` in a technical drill-down, never as `Orders`.

### 9.4 Channel data is effectively empty

- `channel = 'direct'` for **382 of 392** visitors.
- `referrer_host` is empty for all visitors.
- UTM values are mostly empty.

**Rule:** B5 shows the real distribution and a setup note. It does not claim ad performance. The note is plain: `Most traffic is direct or unknown. Add UTM tags to your ad links to see campaign performance.`

### 9.5 Sample size gate

- Current volume: 392 visitors, 239 events, 3 orders, 30 products.
- A percentage on 3 orders is noise.

**Rule:** percentages and conversion rates are shown only when the denominator is at least 30. Below that, show the count and `Collecting data (n/30)`. This applies to A5, B2, B4, B5, and B8.

### 9.6 Page path hygiene

`event_logs.page_path` currently includes non-page endpoints:

- `/products/{id}/variant-detail`
- `/products/check-stock`
- `/cart/store`
- `/cart/increment`
- `/customer/order-save`
- `/add-to-wishlist/{id}`

**Rule:** A4 and B3 count only real page views. Exclude these prefixes. Keep them available in the visitor drawer as `Actions`, not `Pages`.

### 9.7 Funnel event names

- The code maps `BeginCheckout` to `InitiateCheckout`, but the stored data still contains `BeginCheckout` (9 rows) and no `ViewContent` rows.
- The current funnel expects `ViewContent` and `InitiateCheckout`, so two steps can show zero while real product views exist.

**Rule:** B4 uses the explicit five-step definition in section B4 and accepts both checkout spellings. Product views are derived from product page paths, not from `ViewContent`.

---

## 10. Responsive rules

### 10.1 Desktop (≥1200px)

- Content container: `container-xxl`.
- KPI row: five cards in one row.
- A3: map 7 columns, district table 5 columns.
- A4/A5/A6: full-width cards with DataTables.
- B3/B4: two-column split where it helps comparison; otherwise full width.
- B6: calendar 8 columns, day detail 4 columns.
- Drawer: right side, 560px.

### 10.2 Tablet (768px–1199px)

- KPI row: three cards, then two.
- A3: map on top, table below.
- B3/B4: stacked.
- B6: calendar full width; day detail opens as a drawer.
- Tables keep the most important three columns; the rest move to child rows.

### 10.3 Mobile (<768px)

- One column everywhere.
- KPI cards: two columns at `≥576px`, one below.
- Tables become cards or child rows; no horizontal scrolling for primary content.
- Filters stack; the `Apply` button is full width and sticky at the bottom of the filter card.
- Charts: height `220px`; hide legends that duplicate the labels.
- Map: `240px`; the district table is the primary readable view.
- Calendar: week list, not a 7-column grid.
- Drawer: full-width bottom sheet with a drag handle.
- Touch targets: minimum `44px`.

### 10.4 Accessibility

- Every chart has a text summary and a table fallback.
- Colour is never the only signal; pair it with a number or label.
- Focus ring is visible on all interactive elements.
- The map has `role="img"` and an `aria-label` with the top three divisions.
- The drawer traps focus and closes on `Escape`.
- Contrast target: WCAG 2.2 AA.

---

## 11. Plain-English label glossary

| Internal term | UI label |
| --- | --- |
| `visitor_id` | `Visitor` |
| `pageview_count` | `Pages viewed` |
| `bounce` | `Left immediately` |
| `channel` | `Source` |
| `referrer_host` | `Website they came from` |
| `division` | `Division` |
| `district` | `District` |
| `device_type` | `Device` |
| `AddToCart` | `Added to cart` |
| `BeginCheckout` / `InitiateCheckout` | `Reached checkout` |
| `Purchase` event | `Purchase event` (never `Order`) |
| `CAPI` / `server-side` | `Server tracking` |
| `deduplication` | `Duplicate events` |
| `attribution` | `Where the visit came from` |
| `conversion rate` | `Orders per 100 visitors` |
| `funnel` | `Where we lose them` |
| `live visitors` | `People on the site now` |

---

## 12. What we will not build

- **No fake district data.** If the location pipeline is not ready, the section says so.
- **No machine-learning predictions.** Three orders cannot train a model. B8 uses rules.
- **No raw JSON links.** The current live visitor link opens JSON; the new UI always opens a drawer or a page.
- **No duplicate live table.** The live table exists once, on the Live page; Analytics shows a compact strip.
- **No Bengali in the UI.** All labels, buttons, empty states, and tooltips are English.
- **No new chart library.** ApexCharts, Leaflet, FullCalendar, DataTables, Flatpickr, and Select2 are already in `public/admin/assets`.
- **No full-page rewrite before design approval.** This plan is the gate.

---

## 13. Build order and acceptance

### Phase 1 — data truth

1. Add `visitors_master.district` and write it in `VisitorIdentify`.
2. Fix the analytics `division` / `search` ambiguous-column 500.
3. Fix the KPI range semantics: use `event_logs.event_time` for activity, `visitors_master.created_at` only for new visitors.
4. Add the bot filter to every aggregate.
5. Add the product-slug join and the page-path exclusion list.

**Acceptance:** the same date range returns the same people/views/orders on both pages; no 500 on `division` or `search`; bot count is visible.

### Phase 2 — components

1. Shared filter bar and empty-state component.
2. A2 and B2 KPI rows.
3. A3 Leaflet map with the empty state.
4. A4/A5/A6 DataTables and product cards.
5. B3/B4 charts and funnel.
6. B6 FullCalendar.
7. A7 drawer.
8. B8 insight cards.
9. B9 live strip.

**Acceptance:** each section renders with real data, an empty state, and a mobile layout.

### Phase 3 — verification

1. Browser test at `1440px`, `1024px`, `768px`, `390px`.
2. Keyboard test: tab through filters, tables, drawer.
3. Empty-state test: force `division = null`, `orders = 0`, `event_logs = 0`.
4. Data-truth test: compare one KPI with a direct SQL query.
5. Contract check for each task.

**Acceptance:** no horizontal scroll on mobile; no fake percentage below 30 samples; no Bengali string in the rendered HTML.

---

## 14. Open decisions for the client

1. **Page split:** keep Visitors and Analytics as two pages, or merge into one long dashboard with tabs? The plan assumes two pages because the questions are different.
2. **Location priority:** should we fix the district pipeline before the visual redesign, or build the section with the empty state first?
3. **Bot policy:** hide bots by default (recommended), or show all traffic with a warning?
4. **Calendar scope:** visits calendar on Analytics (recommended), or campaign calendar on Visitors?
5. **Order data:** should `Orders from visitors` count all orders in the period, or only orders linked to a tracked visitor? The plan uses linked orders and shows the coverage.
6. **Design direction:** the client will supply the visual direction after this plan. No UI code starts before that.

---

## 15. Current data snapshot (evidence for the gates)

| Metric | Value |
| --- | --- |
| `visitors_master` rows | 392 |
| Visitors with `division` | 0 |
| Visitors with `city` | 10 (seeded, inconsistent) |
| Visitors with `device_type = 'bot'` | 200 |
| Visitors with `channel = 'direct'` | 382 |
| `event_logs` rows | 239 |
| `PageView` events | 71 |
| `AddToCart` events | 16 |
| `BeginCheckout` events | 9 |
| `Purchase` events | 29 |
| `orders` rows | 3 |
| `products` rows | 30 |
| `ip_geo_cache` rows | 0 |
| Visitors active in last 24h | 108 |
| Visitors active in last 5 min | 30 |

**Top page paths:** `/` (100), `/products/show/premium-3d-canvas-cwc-201-1` (27), `/search` (19), `/checkout` (8), `/product/artisan-raw-honey` (7), `/product/organic-spinach-bundle` (6), `/customer/checkout` (5), `/customer/order-success/5` (4), `/customer/order-success/4` (1), `/products` (1).

**Verified product slug mapping:** `premium-3d-canvas-cwc-201-1` → product 1, `artisan-raw-honey` → product 14, `organic-spinach-bundle` → product 17.

---

## 16. Approval

This plan is complete when the client confirms:

- the two-page split,
- the section order,
- the location and bot handling,
- the English labels,
- and the visual design direction.

No UI code will be written before that confirmation.
