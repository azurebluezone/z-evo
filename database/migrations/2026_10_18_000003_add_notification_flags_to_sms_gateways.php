<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add the `order` and `forget_pass` notification flags to sms_gateways.
 *
 * WHY
 * Two independent places already read these columns and neither could work:
 *
 *   1. CustomerController@order_save(), line 619:
 *          SmsGateway::where(['status' => 1, 'order' => '1'])->first();
 *      That runs AFTER the order row is saved, outside any try/catch, so the
 *      unknown column threw and the customer got HTTP 500 instead of the
 *      success page - while the order had in fact been created. Retrying then
 *      produced duplicate orders. Verified live before this migration:
 *
 *          SQLSTATE[42S22]: Unknown column 'order' in 'where clause'
 *
 *   2. The admin screen backEnd/apiintegration/sms_manage.blade.php renders
 *      `<input type="checkbox" name="order" @if($sms->order==1) ...>` and the
 *      same for forget_pass. Both the template and
 *      ApiIntegrationController::update() treat the flags as existing, so the
 *      "Order confirm" and "Forgot password" checkboxes could never persist.
 *
 * The same missing-column pattern was already found twice in this migration run
 * (customers.email, order_details.product_size/product_color), so this is a
 * systematic gap: the admin templates describe a schema the database never
 * received. It is worth a full audit rather than discovering them one 500 at a
 * time.
 *
 * Both flags default to 0 = off, so no existing gateway starts sending SMS it
 * was not already configured for. An admin has to tick the box deliberately.
 *
 * DOWN drops them, which discards which notifications each gateway was
 * configured to send.
 */
return new class extends Migration
{
    /** Flags the storefront and admin UI already read. */
    private const FLAGS = ['order', 'forget_pass'];

    public function up(): void
    {
        if (! Schema::hasTable('sms_gateways')) {
            return;
        }

        Schema::table('sms_gateways', function (Blueprint $table) {
            foreach (self::FLAGS as $flag) {
                if (! Schema::hasColumn('sms_gateways', $flag)) {
                    $table->tinyInteger($flag)->default(0)->after('status');
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sms_gateways')) {
            return;
        }

        Schema::table('sms_gateways', function (Blueprint $table) {
            $existing = array_values(array_filter(
                self::FLAGS,
                fn (string $flag) => Schema::hasColumn('sms_gateways', $flag)
            ));

            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }
};
