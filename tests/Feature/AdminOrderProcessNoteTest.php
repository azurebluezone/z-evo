<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shipping;
use App\Models\ShippingCharge;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The order status/process screen must not erase the admin note.
 *
 * order_process() did `$order->admin_note = $request->admin_note;`
 * unconditionally, while the "Update Order" form had no admin_note input: every
 * save from admin/order/process/{invoice} wrote NULL over the note an admin had
 * typed on the order edit screen. That is why the note kept "disappearing".
 *
 * This test proves both halves:
 *  - a payload WITHOUT admin_note (the old form) keeps the stored note;
 *  - a payload WITH admin_note updates it;
 *  - the process screen itself renders the note and posts a real area id.
 *
 * All writes run inside a rolled-back transaction. The marker line at the end
 * is printed only when every assertion passes.
 */
class AdminOrderProcessNoteTest extends TestCase
{
    private function admin(): User
    {
        $admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user to authenticate as.');

        return $admin;
    }

    private function order(): Order
    {
        $order = Order::query()->where('invoice_id', 'AD-ORD-0029')->first()
            ?? Order::query()->whereIn('id', Shipping::query()->select('order_id'))->first();
        $this->assertNotNull($order, 'Need at least one order with a shipping row.');
        $this->assertNotNull(Shipping::query()->where('order_id', $order->id)->first(), 'The order under test has no shipping row.');

        return $order;
    }

    private function areaId(Shipping $shipping): int
    {
        $id = ShippingCharge::query()->where('name', $shipping->area)->value('id')
            ?? ShippingCharge::query()->value('id');
        $this->assertNotNull($id, 'Need at least one shipping charge row.');

        return (int) $id;
    }

    public function test_status_update_keeps_the_admin_note(): void
    {
        $admin = $this->admin();
        $order = $this->order();
        $shipping = Shipping::query()->where('order_id', $order->id)->first();

        $payload = [
            'id' => $order->id,
            'name' => $shipping->name,
            'phone' => $shipping->phone,
            'address' => $shipping->address,
            'area' => $this->areaId($shipping),
            'status' => (int) $order->order_status,
        ];

        DB::beginTransaction();
        try {
            // 1. An old-style save (no admin_note field) must not wipe the note.
            $order->admin_note = 'ZC kept note';
            $order->save();

            $this->actingAs($admin)->post(route('admin.order_change'), $payload)->assertRedirect();
            $this->assertSame(
                'ZC kept note',
                $order->fresh()->admin_note,
                'order_process wiped the admin note when the payload had no admin_note field'
            );

            // 2. A save that does carry the field must update it.
            $this->actingAs($admin)
                ->post(route('admin.order_change'), $payload + ['admin_note' => 'ZC new note'])
                ->assertRedirect();
            $this->assertSame('ZC new note', $order->fresh()->admin_note, 'order_process ignored a posted admin_note');

            // 3. The process screen itself shows the note and passes a real area id.
            $page = $this->actingAs($admin)->get(route('admin.order.process', ['invoice_id' => $order->invoice_id]));
            $page->assertOk();
            $html = $page->getContent();

            $this->assertMatchesRegularExpression(
                '~<textarea[^>]*name="admin_note"[^>]*>\s*ZC new note\s*</textarea>~u',
                $html,
                'the process screen does not render the stored admin note'
            );
            $this->assertMatchesRegularExpression(
                '~<select[^>]*name="area"[^>]*>~u',
                $html,
                'the delivery-area select must submit name=area'
            );
        } finally {
            DB::rollBack();
        }

        fwrite(STDOUT, PHP_EOL . 'ORDER_PROCESS_NOTE_KEEP_OK' . PHP_EOL);
    }
}
