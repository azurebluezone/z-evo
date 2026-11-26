# PLAN — E-commerce notification system (2026-11-16)

Status: implemented and verified on a throwaway database with
`bash tools/verify_notification_bundle.sh` → `NOTIFICATION_BUNDLE_OK`.

## 1. Goal

Turn the existing notification-template screen into a real, event-keyed
e-commerce notification system:

- one template per business event (`order.placed`, `order.shipped`, ...);
- a renderer that replaces `{variables}` and converts the admin Quill HTML to
  plain text for the customer inbox;
- a dispatcher + queued job that sends through the `database` channel by
  default and `mail` only when an admin opts in;
- domain events fired by the order/payment/refund/customer code paths;
- admin CRUD for `event_key`, `channels`, `variables` and `description`;
- a queue design that works on a low-budget shared host (sync by default,
  `database` queue + cron as the upgrade).

## 2. Baseline before this change

Already present:

- `notification_templates` table (`name`, `title`, `body`, `is_active`);
- admin CRUD (`NotificationTemplateController`, index/create/edit views),
  routes inside the admin auth group and the "Template Manager" sidebar link;
- customer inbox `customer.notifications.*` reading Laravel's `notifications`
  table;
- `GenericDbNotification` (database only);
- hardcoded admin notifications in checkout, landing checkout and service
  request submission.

Missing: events, listeners, jobs table, renderer, dispatcher, customer
delivery, event keys and template data.

## 3. Event matrix

| Event key | Fired by | Recipient | Default channels |
| --- | --- | --- | --- |
| `customer.welcome` | `CustomerController@store` → `CustomerRegistered` | customer | database |
| `order.placed` | `OrderPlacementService::place()` → `OrderPlaced` | customer | database |
| `admin.order.placed` | same event | all admin users | database |
| `order.processing` | `OrderLifecycle::transition()` → `OrderStatusChanged` | customer | database |
| `order.shipped` | same | customer | database |
| `order.delivered` | same | customer | database |
| `order.cancelled` | same | customer | database |
| `order.returned` | same | customer | database |
| `review.request` | `OrderStatusChanged` when status becomes `delivered` | customer | database |
| `payment.paid` | `BkashController` paid path → `PaymentStatusChanged` | customer | database |
| `payment.failed` | bKash failure branch → dispatcher | customer | database |
| `refund.completed` | `ReturnsRefundsController@markRefunded` → `RefundCompleted` | customer | database |
| `admin.customer.registered` | `CustomerRegistered` | all admin users | database |
| `admin.service.request.received` | `Frontend\ServiceRequestController@store` | all admin users | database |
| `support.request.received` | same request, when the phone matches a customer | customer | database |
| `support.request.approved` | admin service-request status change | customer | database |
| `support.request.pending` | admin service-request status change | customer | database |
| `general.offer` | manual broadcast (User Submissions → Notify) | selected customers | database |

`mail` is never enabled by default. An admin can enable it per template; the
dispatcher/job drops the mail channel when the recipient has no email or when
`MAIL_*` is still the placeholder, so an SMTP problem cannot break checkout.

## 4. Schema

`2026_11_16_000007_add_event_key_to_notification_templates.php`

| Column | Type | Notes |
| --- | --- | --- |
| `event_key` | string(100), nullable, unique | null = manual template |
| `channels` | json, nullable | `["database"]` / `["database","mail"]` |
| `variables` | json, nullable | documentation list, e.g. `["customer_name"]` |
| `description` | string(255), nullable | admin help text |

`2026_11_16_000008_seed_ecommerce_notification_templates.php`
installs the catalog through `App\Support\NotificationTemplateCatalog::install()`.
The install:

1. adopts the five legacy rows by exact name and fills their `event_key`,
   `channels`, `variables` and `description` (keeps the admin's title/body);
2. inserts any missing catalog row by `event_key`;
3. never overwrites an existing row, so re-running the seeder is safe.

No additional seeder file is required: `DatabaseSeeder` already calls
`NotificationTemplateSeeder`, and both the seeder and the data migration share
the same catalog.

`2026_11_16_000009_create_jobs_table.php` adds the standard Laravel 10
`jobs`, `job_batches` and `failed_jobs` tables.

## 5. Rendering

`NotificationTemplate`:

- `channelList()` — whitelists `database` / `mail`, defaults to `database`;
- `variableList()` — the documented placeholder names;
- `renderTitle($vars)` / `renderBody($vars)` — `strtr` over `{token}`;
- `plainText()` — `<br>`/`</p>` become newlines, tags are stripped, entities
  decoded, spaces/newlines collapsed. The customer inbox escapes the body with
  `white-space: pre-wrap`, so an admin may keep using the Quill HTML editor
  while customers see clean text.

Any unresolved `{token}` is removed so a raw placeholder can never reach a
customer.

## 6. Delivery

```text
domain event
  → listener (try/catch, never breaks the request)
    → NotificationDispatcher::send(eventKey, variables, notifiables)
      → active template by event_key
      → render title/body
      → SendNotificationJob::dispatch($notifiable, ...)
        → GenericDbNotification via database and/or mail
```

`SendNotificationJob` implements `ShouldQueue`, retries 3 times with a 60s
backoff and logs a final failure. With `QUEUE_CONNECTION=sync` it runs inline;
with `database` it stores a row in `jobs`.

## 7. Shared-hosting operation

Default (no worker): `.env` `QUEUE_CONNECTION=sync`. Everything works on a
normal PHP request.

Upgrade, without a daemon:

1. `php artisan migrate` (adds the notification columns and the jobs tables).
2. `.env`: `QUEUE_CONNECTION=database`.
3. Add cron:

   ```cron
   * * * * * cd /home/USER/public_html && php artisan schedule:run >> /dev/null 2>&1
   ```

   `app/Console/Kernel.php` schedules
   `queue:work --stop-when-empty --max-time=50 --tries=3` every minute with a
   10-minute overlap lock. No supervisor/Horizon needed.

Mail is opt-in per template. Enable it only after `MAIL_*` works, then tick
"Email" on the templates that need it.

## 8. Admin UX

- Index: event key, channels, description and active state per row.
- Create/Edit: Event key (datalist of catalog keys), Description, Channels
  (In-app inbox / Email), Variables (comma separated). The body editor stays
  Quill; placeholders use `{customer_name}`.
- Sidebar "Template Manager" link was already present and still points at
  `admin.notification-templates.index`.

## 9. Verification

`tools/verify_notification_bundle.sh`:

1. copies the DB credentials, refuses to run against the `.env` database;
2. creates/drops scratch DB `z_evo_notify_check` on exit;
3. runs `migrate:fresh --seed`;
4. runs `php tools/verify_notifications.php` twice, with
   `db:seed --class=NotificationTemplateSeeder` in between (idempotency);
5. prints `NOTIFICATION_BUNDLE_OK` only when every check passes.

The PHP verifier proves: columns/tables, 18 active catalog templates, legacy
adoption, variable substitution, HTML→text conversion, real notifications for
`order.placed` → processing → shipped → delivered → review, payment, refund,
welcome and the admin copies, mail opt-in and no-email fallback, a real
`database` queue round trip through `queue:work --once`, the scheduled queue
drain, and the admin form rendering `event_key` / `channels[]` / `variables`.

## 10. Risks and rollback

- The migration is additive; old hardcoded notifications are replaced by the
  event path, so there is no double send.
- Seeded orders pass `notify => false` and lifecycle notifications are disabled
  in `DevOrderSeeder`, so demo data does not create fake inbox rows.
- If a template is deactivated, the event silently sends nothing (logged at
  debug level); checkout never fails.
- Rollback: `php artisan migrate:rollback --step=3` drops the jobs tables and
  the four columns. Template rows are kept on purpose.
