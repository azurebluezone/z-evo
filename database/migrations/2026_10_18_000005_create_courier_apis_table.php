<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Create courier_apis — the table the entire courier admin UI reads and writes.
 *
 * ---------------------------------------------------------------------------
 * WHY THIS TABLE HAS TO EXIST
 * ---------------------------------------------------------------------------
 * App\Models\Courierapi maps to `courier_apis`, and there was NO migration for
 * it anywhere in database/migrations/. Every screen that touches courier
 * credentials therefore threw on its first query:
 *
 *     SQLSTATE[42S02] 1146 Table 'courier_apis' doesn't exist
 *
 * Confirmed dead as a result (all three verified by reading the controller and
 * reproducing the query):
 *   GET  admin/courier/settings    CourierSettingsController@index
 *   GET  admin/courierapi/manage   ApiIntegrationController@courier_manage
 *   POST admin/courier/{steadfast,pathao}/update
 *   POST admin/courierapi/save
 *   POST admin/courierapi/test-steadfast | test-pathao
 *   POST admin/courierapi/sync-orders
 *
 * So the answer to "does the admin see a form after clicking?" is: no page
 * rendered at all. There was nothing to send an order with.
 *
 * ---------------------------------------------------------------------------
 * COLUMNS - THE EXACT SET THE CODE ALREADY USES
 * ---------------------------------------------------------------------------
 * Derived by grepping every read and write of a Courierapi-backed variable in
 * app/ and resources/views/backEnd/, not guessed:
 *
 *   url (12)  status (12)  secret_key (7)  password (7)  api_key (7)
 *   username (6)  client_secret (6)  client_id (6)  token (3)  type (1)
 *
 * Per courier:
 *   SteadFast needs url + api_key + secret_key.
 *   Pathao    needs url + client_id + client_secret + username + password, and
 *             exchanges them for a short lived access token stored in `token`
 *             (ApiIntegrationController@courier_update).
 *
 * Everything is nullable except type, because the two couriers do not share a
 * credential set and a half-configured row must still save.
 *
 * ---------------------------------------------------------------------------
 * webhook_token - NEW, AND IT IS THE POINT OF THIS MIGRATION
 * ---------------------------------------------------------------------------
 * The existing webhook (Api\CourierWebhookController@pathao, POST
 * /api/pathao-webhook) performs NO authentication whatsoever. Anyone who can
 * reach the URL can POST
 *
 *     {"merchant_order_id":"X","order_status":"delivered"}
 *
 * and move a real order to "delivered" — corrupting delivery records and COD
 * reconciliation.
 *
 * SteadFast authenticates its callbacks with a Bearer token ("SteadFast sends
 * real-time status updates to your callback URL using a Bearer token for
 * authentication"), so `webhook_token` is what our endpoint will require in
 * `Authorization: Bearer <token>`. It is generated per courier here and is
 * compared with hash_equals() (constant time) by the webhook middleware.
 *
 * It is nullable so a courier can be configured before its webhook is
 * registered; the endpoint then refuses every call rather than accepting an
 * unauthenticated one. Silence beats an open door.
 *
 * ---------------------------------------------------------------------------
 * SEED ROWS
 * ---------------------------------------------------------------------------
 * Two rows are inserted (steadfast, pathao) so the admin form has something to
 * edit. Both default to status = 0: credentials are absent and the courier is
 * therefore OFF. Nothing is sent anywhere until an admin fills the form in and
 * ticks it active.
 *
 * Base URLs are left null rather than guessed - SteadFast has been served from
 * both portal.packzy.com and portal.steadfast.com.bd, and pinning a stale one
 * would be a silent failure. The form shows the expected host as a hint.
 *
 * insertOrIgnore is used so re-running is safe, and a pre-existing table is
 * left untouched.
 */
return new class extends Migration
{
    /** Courier rows the admin UI expects to always be able to edit. */
    private const COURIERS = [
        'steadfast' => 'SteadFast Courier',
        'pathao'    => 'Pathao Courier',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('courier_apis')) {
            Schema::create('courier_apis', function (Blueprint $table) {
                $table->id();

                // Which courier this row configures. Unique so updateOrCreate
                // and first() in the controllers behave predictably.
                $table->string('type', 40)->unique();

                // Human readable label for admin dropdowns.
                // ShippingVerificationController selects it to label the courier
                // picker; it guards the read with Schema::hasColumn and would
                // otherwise degrade to id+type only, which renders an
                // unlabelled list.
                $table->string('title', 120)->nullable();

                $table->string('url', 255)->nullable();
                $table->string('api_key', 255)->nullable();
                $table->string('secret_key', 255)->nullable();

                // Pathao OAuth
                $table->string('client_id', 255)->nullable();
                $table->string('client_secret', 255)->nullable();
                $table->string('username', 255)->nullable();
                $table->string('password', 255)->nullable();

                // Short lived access token obtained by exchanging the above.
                $table->text('token')->nullable();

                // Shared secret OUR webhook endpoint requires in the
                // Authorization: Bearer header. Generated per courier.
                $table->string('webhook_token', 128)->nullable();

                $table->boolean('status')->default(false);

                $table->timestamps();
            });
        }

        $this->seedCouriers();
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_apis');
    }

    /**
     * One disabled row per courier, each with its own webhook secret.
     */
    private function seedCouriers(): void
    {
        $now = now();

        foreach (self::COURIERS as $type => $title) {
            // insertOrIgnore respects the unique index on `type`, so this is
            // safe to run on a database that already has the rows.
            DB::table('courier_apis')->insertOrIgnore([
                'type'          => $type,
                'title'         => $title,
                'url'           => null,
                'api_key'       => null,
                'secret_key'    => null,
                'client_id'     => null,
                'client_secret' => null,
                'username'      => null,
                'password'      => null,
                'token'         => null,
                // 96 hex chars = 48 random bytes. Generated once, at seed time,
                // so the token is not derived from anything guessable.
                'webhook_token' => bin2hex(random_bytes(48)),
                'status'        => false,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }
    }
};
