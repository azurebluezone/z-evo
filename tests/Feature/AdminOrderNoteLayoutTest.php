<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shipping;
use App\Models\ShippingCharge;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Admin order create/edit: the internal note must be visible and saved, the
 * header Save button must submit the order form, and the edit page's three
 * stepper panes must be real siblings.
 *
 * What was wrong:
 *  - orders.admin_note existed and order_process() wrote it, but neither the
 *    create nor the edit view had an admin_note field, so the POS screens could
 *    never show or set it.
 *  - The header submit button used form="adminForm", and adminForm was the
 *    Cart Clear GET form, so clicking "Update"/"Submit" in the header reloaded
 *    the page through admin.order.cart_clear and emptied the till cart.
 *  - On the edit page #stepper-2 opened inside #stepper-1 and #stepper-3 inside
 *    #stepper-2; the products column closed immediately, so the page rendered
 *    an empty first step and hid the customer/summary panes.
 *
 * The last line prints ORDER_ADMIN_NOTE_LAYOUT_OK only after every assertion
 * in this test passes, so a shell check can use it as a gate marker.
 */
class AdminOrderNoteLayoutTest extends TestCase
{
    private function admin(): User
    {
        $admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user to authenticate as.');

        return $admin;
    }

    private function xpath(string $html): DOMXPath
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument();
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($document);
    }

    /**
     * Every order screen: admin note inside the real order form, and no header
     * button allowed to point at the cart-clear GET form.
     */
    private function assertOrderScreen(string $html, string $actionFragment, string $label): DOMXPath
    {
        $xpath = $this->xpath($html);

        $form = $xpath->query("//form[contains(@action, '{$actionFragment}')]")->item(0);
        $this->assertNotNull($form, "{$label}: order form posting to {$actionFragment} is missing");
        $this->assertSame('orderForm', $form->getAttribute('id'), "{$label}: order form needs id=orderForm");

        $note = $xpath->query(".//textarea[@name='admin_note']", $form);
        $this->assertSame(1, $note->length, "{$label}: exactly one textarea name=admin_note must sit inside the order form");

        // Always visible: the note must not be locked behind a stepper pane.
        $paneAncestor = $xpath->query(
            "//textarea[@name='admin_note']/ancestor::*[contains(concat(' ', normalize-space(@class), ' '), ' content ')]"
        );
        $this->assertSame(0, $paneAncestor->length, "{$label}: admin_note should be visible on every step, not inside a pane");

        $cartClear = $xpath->query("//*[@id='cartClearForm']")->item(0);
        $this->assertNotNull($cartClear, "{$label}: the Cart Clear form needs its own id cartClearForm");
        $this->assertStringContainsString('cart-clear', $cartClear->getAttribute('action'), "{$label}: cartClearForm must post to cart-clear");

        $buttons = $xpath->query("//button[@form]");
        $this->assertGreaterThan(0, $buttons->length, "{$label}: header submit button lost its form attribute");
        foreach ($buttons as $button) {
            $this->assertSame(
                'orderForm',
                $button->getAttribute('form'),
                "{$label}: header button must submit orderForm, not the cart-clear form"
            );
        }

        return $xpath;
    }

    public function test_order_admin_note_and_stepper_layout(): void
    {
        $admin = $this->admin();

        $create = $this->actingAs($admin)->get(route('admin.order.create'));
        $create->assertOk();
        $this->assertOrderScreen($create->getContent(), 'admin/order/store', 'create');

        $order = Order::query()->where('invoice_id', 'AD-ORD-0029')->first()
            ?? Order::query()->whereIn('id', Shipping::query()->select('order_id'))->first();
        $this->assertNotNull($order, 'Need at least one order with a shipping row.');
        $shipping = Shipping::query()->where('order_id', $order->id)->first();
        $this->assertNotNull($shipping, 'The order under test has no shipping row.');
        $areaId = ShippingCharge::query()->where('name', $shipping->area)->value('id')
            ?? ShippingCharge::query()->value('id');
        $this->assertNotNull($areaId, 'Need at least one shipping charge row.');

        $edit = $this->actingAs($admin)->get(route('admin.order.edit', ['invoice_id' => $order->invoice_id]));
        $edit->assertOk();
        $xpath = $this->assertOrderScreen($edit->getContent(), 'admin/order/update', 'edit');

        foreach ([1, 2, 3] as $n) {
            $pane = $xpath->query("//*[@id='stepper-{$n}']")->item(0);
            $this->assertNotNull($pane, "edit: stepper-{$n} pane is missing");
            $this->assertStringContainsString(
                'bs-stepper-content',
                (string) $pane->parentNode->getAttribute('class'),
                "edit: stepper-{$n} must be a direct child of bs-stepper-content"
            );
        }

        $this->assertSame(0, $xpath->query("//*[@id='stepper-1']//*[@id='stepper-2']")->length, 'edit: stepper-2 is still nested inside stepper-1');
        $this->assertSame(0, $xpath->query("//*[@id='stepper-2']//*[@id='stepper-3']")->length, 'edit: stepper-3 is still nested inside stepper-2');

        // The pane order on screen must be the order the shopkeeper fills in.
        $content = $xpath->query("//*[@id='stepper-1']/parent::*")->item(0);
        $ids = [];
        foreach ($content->childNodes as $child) {
            if ($child instanceof \DOMElement && $child->hasAttribute('id')) {
                $ids[] = $child->getAttribute('id');
            }
        }
        $this->assertSame(['stepper-1', 'stepper-2', 'stepper-3'], $ids, 'edit: panes are out of order');

        $this->assertSame(1, $xpath->query("//*[@id='stepper-1']//*[@id='cart_add']")->length, 'edit: product picker belongs in the Products pane');
        $this->assertSame(1, $xpath->query("//*[@id='stepper-2']//input[@name='name']")->length, 'edit: customer fields belong in the Customer pane');
        $this->assertSame(1, $xpath->query("//*[@id='stepper-3']//*[@id='cart_details']")->length, 'edit: the summary belongs in the Payment pane');

        // The note is not just markup: the update route must persist it.
        $this->actingAs($admin)->get(route('admin.order.edit', ['invoice_id' => $order->invoice_id]))->assertOk();

        $note = 'ZC admin note ' . now()->format('His') . '-' . random_int(1000, 9999);

        DB::beginTransaction();
        try {
            $response = $this->post(route('admin.order.update'), [
                'order_id' => $order->id,
                'name' => $shipping->name,
                'phone' => $shipping->phone,
                'address' => $shipping->address,
                'area' => $areaId,
                'admin_note' => $note,
            ]);
            $response->assertRedirect();
            $this->assertDatabaseHas('orders', ['id' => $order->id, 'admin_note' => $note]);
        } finally {
            DB::rollBack();
        }

        $source = (string) file_get_contents(app_path('Http/Controllers/Admin/OrderController.php'));
        $this->assertGreaterThanOrEqual(2, substr_count($source, '$order->admin_note = $request->admin_note;'), 'create and edit must both assign admin_note');
        $this->assertGreaterThanOrEqual(2, substr_count($source, "'admin_note' => 'nullable|string|max:5000'"), 'create and edit must both validate admin_note');

        fwrite(STDOUT, PHP_EOL . 'ORDER_ADMIN_NOTE_LAYOUT_OK' . PHP_EOL);
    }
}
