# Report suite completion plan

Locked: 2026-11-28. Source of truth for metric definitions stays
`docs/report-metrics.md`; this file only orders the remaining work.

## 0. Where the suite stands

Done and verified (marker `REPORT_TRUTH_OK`, contract `gc-mwzw7e3l`):

- Distinct-order Return Rate and item-level Return Rate on Returns.
- Gross Profit = `Net Sales - COGS` on the dashboard and Sales & Profit.
- Estimated Net Profit includes `business_expenses` by `spent_at`.
- `courier_shipments.status_normalized` is written on every model save
  (`CourierStatusNormalizer`) and is the only status the Courier report reads.
- Per-variant `low_stock_threshold` is applied on Stock Alerts and in the
  navbar signal.
- Dashboard, Sales Report, Product Report, Stock Alerts, export, and Report
  Health from the earlier waves.
- Metric dictionary section 5 items 1, 2, 5, 10, 11, 12 are resolved; items
  6-9 now state the real gap: the columns exist, the aggregates and screens do
  not.

Still open, in dependency order. Nothing below is claimed as implemented.

## Phase 1 — Cost truth and settlement

Goal: every taka that leaves or returns to the business is entered once and
reported on the day it happened.

1. Expense entry UI.
   - `expense_categories` and `business_expenses` exist but have no screen.
   - Add admin CRUD for categories and expenses (amount, `spent_at`,
     category, payment method, note, `created_by`).
   - Reuse the existing `report-profit-view` permission.
   - Done when a saved expense changes the dashboard Expense and Estimated
     Net Profit on the same page load, proven by an independent SQL test.
2. COD Outstanding. ✅ done 2026-11-28, marker `COD_OUTSTANDING_OK`,
   contract `gc-mwzxs9g5`.
   - Source: `courier_shipments.cod_amount - remitted_amount`; both columns
     exist (`2026_11_27_000005`).
   - Add a Courier report card and a per-provider table column, date basis
     `courier_shipments.created_at` until a settlement date is chosen.
   - Done when the page number equals an independent SQL sum and an unknown
     settlement state is shown, not guessed.
   - Evidence: `tests/Feature/AdminCodOutstandingTest.php` (2 tests, 15
     assertions) compares `metrics['cod_outstanding']` with a raw
     `SUM(cod_amount - remitted_amount)` and counts a `mystery_state`
     settlement row as unknown.
3. Payment settlement. ✅ done 2026-11-28, marker `PAYMENT_SETTLEMENT_OK`,
   contract `gc-mwzxy9w7`.
   - Sources: `payments.gateway_fee`, `settled_amount`,
     `settlement_status`, `settled_at`, `settlement_reference`
     (`2026_11_27_000004`) and `payment_settlements`
     (`2026_11_27_000009`).
   - Add settlement entry plus a Payment Methods report block: collected vs
     settled vs fee.
   - Done when collected - fees - settled is visible per gateway and per
     period.
   - Evidence: `POST admin.reports.payments.settlement` writes one
     `payment_settlements` row and recomputes `payments.settled_amount` and
     `payments.gateway_fee` from its rows; the report shows a Settlement gap
     card and per-method Fee/Settled/Gap columns;
     `tests/Feature/AdminPaymentSettlementTest.php` (3 tests, 22 assertions)
     compares the page gap with `SUM(amount - gateway_fee - settled_amount)`
     and proves Support is denied.
4. Courier settlement. ✅ done 2026-11-28, marker `COURIER_SETTLEMENT_OK`,
   contract `gc-mwzy4hc3`.
   - Sources: `cod_amount`, `return_charge`, `remitted_amount`,
     `settlement_status` (`2026_11_27_000005`) and `courier_settlements`
     (`2026_11_27_000009`).
   - Add a reconciliation view: expected COD, remitted, shortfall, return
     charge.
   - Done when a remittance row updates the shortfall without editing the
     shipment row by hand.
   - Evidence: `POST admin.reports.couriers.settlement` writes one
     `courier_settlements` row and recomputes `courier_shipments.remitted_amount`
     from all settlement rows for that order and provider; the Courier page
     shows a reconciliation table and shortfall card
     (`data-metric="courier-shortfall"`, shortfall = expected - remitted -
     return charge); `tests/Feature/AdminCourierSettlementTest.php` (3 tests,
     23 assertions) proves the page shortfall equals an independent SQL sum
     and that Support is denied.

Exit marker: `SETTLEMENT_TRUTH_OK`.

Phase 1 exit: SETTLEMENT_TRUTH_OK (2026-11-28, contract `gc-mwzy8v6u`).
One run proves all four items: `AdminExpenseEntryTest`,
`AdminCodOutstandingTest`, `AdminPaymentSettlementTest`,
`AdminCourierSettlementTest`, plus the `AdminReportAnalyticsTest` and
`AdminReportCourierNormalizedTest` regressions. Dictionary items 6-8 are
resolved in `docs/report-metrics.md` section 5.

## Phase 2 — Geography and saved views

Goal: reports answer "where" and an admin can keep a filter without retyping
it.

1. Seed `districts` (the table exists and has 0 rows). ✅ done 2026-11-28,
   marker `DISTRICTS_SEEDED_OK`, contract `gc-mwzybqlt`.
   - Evidence: `DistrictShippingSeeder::districtsOnly()` reads the vendored
     `database/data/bangladesh-geo.json` and upserts on
     `(district, area_name)`; `DatabaseSeeder` runs it after
     `DevShippingChargeSeeder`; `tests/Feature/DistrictsSeedTest.php` proves a
     second run keeps 495 rows and leaves `shipping_charges` at 4; the dev
     table now has 495 rows.
2. Make `shippings.district_id` mandatory at checkout; keep
   `shippings.division` in sync.
3. Add district/division breakdowns to Sales, Returns and Courier reports.
   Date bases stay the same as their parent report.
4. Use `report_views` (table exists, 0 rows) for saved filter sets: name,
   route, query string, owner, shared flag.
5. Done when a saved view restores the exact filter and the district totals
   add up to the national total for the same range.

Exit marker: `GEO_SAVED_VIEWS_OK`.

## Phase 3 — Acquisition and lifecycle

Goal: revenue can be traced to a spend and a repeat behaviour, not only to an
order.

1. Marketing spend entry on `marketing_spends`.
2. Attribution rows on `order_attributions` at order creation; one order has
   one primary attribution.
3. ROAS / CPA / CAC blocks on the dashboard and a Marketing report.
4. Cohort retention by first delivered order month.
5. Abandoned cart: a cart with no order after a configurable window, with
   recovery state stored, not inferred from logs.
6. Done when ROAS = attributed delivered revenue / spend for the same range,
   recomputed independently, and a cohort row's month-1 repeat rate matches a
   plain SQL count.

Exit marker: `GROWTH_TRUTH_OK`.

## Phase 4 — Release verification and operations

Goal: the suite is proven outside the test database and stays proven.

1. Open a generated XLSX in a real spreadsheet application (current
   environment's LibreOffice 7.3 cannot open even a one-line CSV, so this is
   still `NOT_TESTED`). Record the application and version.
2. Send the daily report through the real SMTP account with
   `auto_send_enabled=1`; confirm delivery and the rendered numbers, then turn
   it back off unless the owner wants it on.
3. Run the full suite plus `AdminReport` filter; record counts and assertions.
4. Re-run the report data-quality checks (Report Health) and the schema
   marker `REPORT_MIGRATIONS_SCHEMA_OK`.
5. Update `docs/report-metrics.md` section 5 and the change log; no item may
   stay "resolved" without a test or a live probe.

Exit marker: `REPORT_RELEASE_OK`.

## Rules for every phase

- Dictionary first: formula, grain, source, date basis, then code.
- One goal contract per phase; the check must finish inside 120 seconds.
- Fixtures run in `DatabaseTransactions`; destructive database work needs a
  backup first.
- A phase closes only when its marker is printed by a real check. No marker,
  no claim.
- New dependencies need a reason in the phase note; the standard library and
  existing packages come first.
