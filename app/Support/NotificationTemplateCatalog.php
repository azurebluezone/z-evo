<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The single source of the e-commerce notification templates.
 *
 * The data migration and NotificationTemplateSeeder both call install(), so a
 * fresh database and an existing database (with the old five generic rows)
 * end up with the same event-keyed set. install() never overwrites a row an
 * admin has edited: it only adopts a legacy row by its exact old name and
 * inserts catalog rows that are missing.
 */
final class NotificationTemplateCatalog
{
    /**
     * @return array<string, array{
     *     name: string,
     *     title: string,
     *     body: string,
     *     description: string,
     *     variables: list<string>,
     *     channels: list<string>
     * }>
     */
    public static function all(): array
    {
        return [
            'customer.welcome' => [
                'name' => 'Customer: Welcome',
                'title' => 'Welcome to {site_name}',
                'body' => <<<'TEXT'
Hi {customer_name},

Welcome to {site_name}. Your account is ready.
You can now track your orders and see your order history from your account.

— {site_name}
TEXT,
                'description' => 'Sent once when a customer creates an account.',
                'variables' => ['customer_name', 'site_name'],
                'channels' => ['database'],
            ],

            'general.offer' => [
                'name' => 'General: Offer',
                'title' => 'A new offer from {site_name}',
                'body' => <<<'TEXT'
Hi {customer_name},

We have new offers for you. Visit {site_name} to see this week's deals.

— {site_name}
TEXT,
                'description' => 'Manual broadcast: User Submissions -> Notify.',
                'variables' => ['customer_name', 'site_name'],
                'channels' => ['database'],
            ],

            'support.request.received' => [
                'name' => 'Support: We Received Your Request',
                'title' => 'We received your request',
                'body' => <<<'TEXT'
Hi {customer_name},

We received your request about {service_type}. Our team will contact you soon.

— {site_name}
TEXT,
                'description' => 'Sent when a service request matches a customer phone.',
                'variables' => ['customer_name', 'service_type', 'site_name'],
                'channels' => ['database'],
            ],

            'support.request.approved' => [
                'name' => 'Support: Request Approved',
                'title' => 'Your request was approved',
                'body' => <<<'TEXT'
Hi {customer_name},

Your request has been approved. Thank you for your patience.
{support_note}

— {site_name}
TEXT,
                'description' => 'Sent when an admin approves a service request.',
                'variables' => ['customer_name', 'support_note', 'site_name'],
                'channels' => ['database'],
            ],

            'support.request.pending' => [
                'name' => 'Support: Request Pending',
                'title' => 'Your request is pending',
                'body' => <<<'TEXT'
Hi {customer_name},

Your request is pending review. We will update you as soon as there is news.
{support_note}

— {site_name}
TEXT,
                'description' => 'Sent when an admin moves a service request back to pending.',
                'variables' => ['customer_name', 'support_note', 'site_name'],
                'channels' => ['database'],
            ],

            'order.placed' => [
                'name' => 'Order: Placed',
                'title' => 'Order {order_number} received',
                'body' => <<<'TEXT'
Hi {customer_name},

Thank you for your order {order_number}. We have received it and will start processing shortly.

Order total: Tk {order_total}
Payment method: {payment_method}
Payment status: {payment_status}

You can track your order from your account.

— {site_name}
TEXT,
                'description' => 'Sent to the customer when an order is placed.',
                'variables' => [
                    'customer_name',
                    'order_number',
                    'order_total',
                    'payment_method',
                    'payment_status',
                    'site_name',
                ],
                'channels' => ['database'],
            ],

            'order.processing' => [
                'name' => 'Order: Processing',
                'title' => 'Order {order_number} is being prepared',
                'body' => <<<'TEXT'
Hi {customer_name},

Your order {order_number} is now being prepared. We will let you know as soon as it ships.

— {site_name}
TEXT,
                'description' => 'Sent when an order moves to processing.',
                'variables' => ['customer_name', 'order_number', 'site_name'],
                'channels' => ['database'],
            ],

            'order.shipped' => [
                'name' => 'Order: Shipped',
                'title' => 'Order {order_number} has shipped',
                'body' => <<<'TEXT'
Hi {customer_name},

Good news — order {order_number} has been shipped.
Courier: {courier}
Tracking number: {tracking_number}

Please keep your phone reachable for the delivery call.

— {site_name}
TEXT,
                'description' => 'Sent when an order moves to shipped.',
                'variables' => [
                    'customer_name',
                    'order_number',
                    'courier',
                    'tracking_number',
                    'site_name',
                ],
                'channels' => ['database'],
            ],

            'order.delivered' => [
                'name' => 'Order: Delivered',
                'title' => 'Order {order_number} delivered',
                'body' => <<<'TEXT'
Hi {customer_name},

Order {order_number} has been delivered. We hope you are happy with it.
If anything is wrong, contact our support team and we will help.

— {site_name}
TEXT,
                'description' => 'Sent when an order is marked delivered.',
                'variables' => ['customer_name', 'order_number', 'site_name'],
                'channels' => ['database'],
            ],

            'order.cancelled' => [
                'name' => 'Order: Cancelled',
                'title' => 'Order {order_number} cancelled',
                'body' => <<<'TEXT'
Hi {customer_name},

Order {order_number} has been cancelled.
If this was not expected, please contact us and we will help.

— {site_name}
TEXT,
                'description' => 'Sent when an order is cancelled.',
                'variables' => ['customer_name', 'order_number', 'site_name'],
                'channels' => ['database'],
            ],

            'order.returned' => [
                'name' => 'Order: Returned',
                'title' => 'Return recorded for order {order_number}',
                'body' => <<<'TEXT'
Hi {customer_name},

We have recorded the return of order {order_number}. Our team will contact you about the next step.

— {site_name}
TEXT,
                'description' => 'Sent when an order is marked returned.',
                'variables' => ['customer_name', 'order_number', 'site_name'],
                'channels' => ['database'],
            ],

            'payment.paid' => [
                'name' => 'Payment: Paid',
                'title' => 'Payment received for order {order_number}',
                'body' => <<<'TEXT'
Hi {customer_name},

We received your payment for order {order_number}.
Amount: Tk {order_total}
Payment method: {payment_method}

Thank you.

— {site_name}
TEXT,
                'description' => 'Sent when a payment is confirmed as paid.',
                'variables' => [
                    'customer_name',
                    'order_number',
                    'order_total',
                    'payment_method',
                    'site_name',
                ],
                'channels' => ['database'],
            ],

            'payment.failed' => [
                'name' => 'Payment: Failed',
                'title' => 'Payment did not complete for order {order_number}',
                'body' => <<<'TEXT'
Hi {customer_name},

Your payment attempt for order {order_number} did not complete.
Your order is still saved. You can try the payment again or choose cash on delivery.

— {site_name}
TEXT,
                'description' => 'Sent when the gateway reports a failed payment attempt.',
                'variables' => ['customer_name', 'order_number', 'site_name'],
                'channels' => ['database'],
            ],

            'refund.completed' => [
                'name' => 'Refund: Completed',
                'title' => 'Refund completed for order {order_number}',
                'body' => <<<'TEXT'
Hi {customer_name},

Your refund for order {order_number} has been completed.
Refund amount: Tk {refund_amount}

— {site_name}
TEXT,
                'description' => 'Sent when an admin marks a return as refunded.',
                'variables' => ['customer_name', 'order_number', 'refund_amount', 'site_name'],
                'channels' => ['database'],
            ],

            'review.request' => [
                'name' => 'Review: Request',
                'title' => 'How was order {order_number}?',
                'body' => <<<'TEXT'
Hi {customer_name},

Thank you for shopping with {site_name}. Could you take a minute to review the products from order {order_number}? Your feedback helps other customers.

— {site_name}
TEXT,
                'description' => 'Sent once when an order is marked delivered.',
                'variables' => ['customer_name', 'order_number', 'site_name'],
                'channels' => ['database'],
            ],

            'admin.order.placed' => [
                'name' => 'Admin: New Order',
                'title' => 'New order {order_number}',
                'body' => <<<'TEXT'
New order {order_number} placed by {customer_name} ({customer_phone}).
Total: Tk {order_total}
Payment: {payment_method} / {payment_status}
TEXT,
                'description' => 'Internal copy sent to every admin user.',
                'variables' => [
                    'customer_name',
                    'customer_phone',
                    'order_number',
                    'order_total',
                    'payment_method',
                    'payment_status',
                ],
                'channels' => ['database'],
            ],

            'admin.customer.registered' => [
                'name' => 'Admin: New Customer',
                'title' => 'New customer registered',
                'body' => <<<'TEXT'
New customer registered: {customer_name} ({customer_phone}).
TEXT,
                'description' => 'Internal copy sent to every admin user.',
                'variables' => ['customer_name', 'customer_phone'],
                'channels' => ['database'],
            ],

            'admin.service.request.received' => [
                'name' => 'Admin: New Service Request',
                'title' => 'New service request',
                'body' => <<<'TEXT'
New service request from {customer_name} ({customer_phone}): {service_type}
TEXT,
                'description' => 'Internal copy sent to every admin user.',
                'variables' => ['customer_name', 'customer_phone', 'service_type'],
                'channels' => ['database'],
            ],
        ];
    }

    /**
     * Old generic template names and the event key each one represents.
     *
     * @return array<string, string>
     */
    public static function legacyMap(): array
    {
        return [
            'General: Welcome' => 'customer.welcome',
            'General: Offer' => 'general.offer',
            'Support: We Received Your Request' => 'support.request.received',
            'Support: Request Approved' => 'support.request.approved',
            'Support: Request Pending' => 'support.request.pending',
        ];
    }

    /**
     * Adopt legacy rows, then insert missing catalog rows.
     *
     * Idempotent: existing event keys are never touched, so an admin edit
     * survives every seeder run.
     *
     * @return array{inserted:int, adopted:int, present:int}
     */
    public static function install(): array
    {
        if (! Schema::hasTable('notification_templates')
            || ! Schema::hasColumn('notification_templates', 'event_key')) {
            return ['inserted' => 0, 'adopted' => 0, 'present' => 0];
        }

        $catalog = self::all();
        $now = now();

        $adopted = 0;

        foreach (self::legacyMap() as $legacyName => $eventKey) {
            $alreadyKeyed = DB::table('notification_templates')
                ->where('event_key', $eventKey)
                ->exists();

            if ($alreadyKeyed) {
                continue;
            }

            $legacy = DB::table('notification_templates')
                ->whereNull('event_key')
                ->where('name', $legacyName)
                ->orderBy('id')
                ->first();

            if (! $legacy) {
                continue;
            }

            $definition = $catalog[$eventKey] ?? null;

            DB::table('notification_templates')
                ->where('id', $legacy->id)
                ->update([
                    'event_key' => $eventKey,
                    'channels' => json_encode(['database']),
                    'variables' => json_encode($definition['variables'] ?? []),
                    'description' => $legacy->description ?: ($definition['description'] ?? null),
                    'updated_at' => $now,
                ]);

            $adopted++;
        }

        $inserted = 0;
        $present = 0;

        foreach ($catalog as $eventKey => $definition) {
            $exists = DB::table('notification_templates')
                ->where('event_key', $eventKey)
                ->exists();

            if ($exists) {
                $present++;
                continue;
            }

            DB::table('notification_templates')->insert([
                'name' => $definition['name'],
                'event_key' => $eventKey,
                'title' => $definition['title'],
                'body' => $definition['body'],
                'description' => $definition['description'],
                'channels' => json_encode($definition['channels']),
                'variables' => json_encode($definition['variables']),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $inserted++;
        }

        return ['inserted' => $inserted, 'adopted' => $adopted, 'present' => $present];
    }
}
