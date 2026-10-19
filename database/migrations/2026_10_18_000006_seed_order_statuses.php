<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed the order_statuses table.
 *
 * ---------------------------------------------------------------------------
 * WHY THIS IS A MIGRATION AND NOT A SEEDER
 * ---------------------------------------------------------------------------
 * order_statuses was EMPTY (0 rows) while orders.order_status was already
 * pointing at it, which breaks three things at once:
 *
 * 1. CustomerController@order_save() hardcodes
 *        $order->order_status = 1;
 *    so every new order points at OrderStatus id 1 - which did not exist.
 *    Admin\OrderController@index() renders $order->status?->name through the
 *    belongsTo(OrderStatus, 'order_status') relation, so the status column came
 *    out blank for every order the storefront had ever created.
 *
 * 2. The courier webhook maps a courier word to a slug and then looks the slug
 *    up: OrderStatus::where('slug', $slug)->first(). With no rows, every
 *    callback would fall through to "mapped => false" and no delivery status
 *    would ever reach an order.
 *
 * 3. Orders.order_status is varchar(55) but holds BOTH conventions - order_save
 *    writes the numeric id "1", while a pre-existing row holds the word
 *    "pending". Nothing reconciles the two, so the relation silently returns
 *    null for both.
 *
 * A migration (not db:seed) because this is baseline data the application
 * already assumes exists: order_save writes id 1 before an admin has ever
 * opened the status screen.
 *
 * ---------------------------------------------------------------------------
 * WHY id 1 MUST BE pending
 * ---------------------------------------------------------------------------
 * order_save() writes the literal 1. Changing that to a slug lookup is a
 * controller change with wider blast radius, so the row is seeded at id 1
 * instead. That makes the existing hardcoded write land on a real row.
 *
 * The slugs are the vocabulary the webhook maps into (see
 * Api\CourierWebhookController::mapCourierStatusToOrderStatusSlug):
 * pending, processing, shipped, delivered, cancelled, returned.
 *
 * ---------------------------------------------------------------------------
 * IDEMPOTENCY
 * ---------------------------------------------------------------------------
 * Every insert is guarded by a slug/upsert, so re-running changes nothing and
 * never duplicates a status an admin has renamed. Existing rows keep their id,
 * name and slug; only genuinely absent slugs are added. Because new rows are
 * appended with auto-increment ids, an admin's id 1 keeps its meaning.
 */
return new class extends Migration
{
    /**
     * slug => [name, id]
     * The ids are only applied to rows this migration creates.
     */
    private const STATUSES = [
        ['slug' => 'pending',    'name' => 'Pending',    'id' => 1],
        ['slug' => 'processing', 'name' => 'Processing', 'id' => 2],
        ['slug' => 'shipped',    'name' => 'Shipped',    'id' => 3],
        ['slug' => 'delivered',  'name' => 'Delivered',  'id' => 4],
        ['slug' => 'cancelled',  'name' => 'Cancelled',  'id' => 5],
        ['slug' => 'returned',   'name' => 'Returned',   'id' => 6],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('order_statuses')) {
            return;
        }

        $now = now();

        foreach (self::STATUSES as $row) {
            $exists = DB::table('order_statuses')
                ->where('slug', $row['slug'])
                ->exists();

            if ($exists) {
                continue;   // never overwrite what an admin has configured
            }

            DB::table('order_statuses')->insert([
                'id'         => $row['id'],
                'name'       => $row['name'],
                'slug'       => $row['slug'],
                // status = 1 means "active / selectable". The classic
                // OrderStatusController filters on this when building the admin
                // dropdown and the storefront tracking steps.
                'status'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_statuses')) {
            return;
        }

        $slugs = array_column(self::STATUSES, 'slug');

        // Only remove rows that still match the seeded name exactly. If an admin
        // has edited one, deleting it could orphan orders that reference its id.
        foreach (self::STATUSES as $row) {
            DB::table('order_statuses')
                ->where('slug', $row['slug'])
                ->where('name', $row['name'])
                ->delete();
        }
    }
};
