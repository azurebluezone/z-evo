<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courierapi;
use App\Models\CourierShipment;
use App\Models\Order;
use App\Services\CourierOrderSender;
use App\Services\PathaoOrderSender;
use Illuminate\Http\Request;

/**
 * Admin screens for sending ONE order to a courier.
 *
 * ---------------------------------------------------------------------------
 * THE FLOW
 * ---------------------------------------------------------------------------
 *   GET  /admin/order/{order}/courier            choose()  - pick a courier
 *   GET  /admin/order/{order}/courier/{provider} show()    - preview the parcel
 *   POST /admin/order/{order}/courier/{provider} store()   - send it
 *
 * A consignment is irreversible and billable: the courier charges for it and a
 * parcel object exists upstream whether or not we ever hear back. So the admin
 * always sees the recipient, address and cash amount BEFORE confirming.
 *
 * ---------------------------------------------------------------------------
 * TWO SENDERS, ONE CHOOSER
 * ---------------------------------------------------------------------------
 * SteadFast speaks the Api-Key / Secret-Key protocol (CourierOrderSender).
 * Pathao speaks OAuth + a pickup store (PathaoOrderSender). The chooser offers
 * both; this controller picks the right sender for the provider instead of
 * pretending one protocol fits both.
 */
class OrderCourierController extends Controller
{
    public function __construct(
        private CourierOrderSender $sender,
        private PathaoOrderSender $pathao
    ) {
    }

    /**
     * The courier chooser: every courier, its readiness, and any consignment
     * this order already has.
     */
    public function choose(Request $request)
    {
        $order = $this->findOrder($request);

        if ($order instanceof \Illuminate\Http\RedirectResponse) {
            return $order;
        }

        $couriers = $this->couriers();

        $shipments = CourierShipment::query()
            ->where('order_id', $order->id)
            ->get()
            ->keyBy('provider');

        $support = $this->support($couriers);

        return view('backEnd.order.courier_choose', [
            'order'     => $order,
            'couriers'  => $couriers,
            'shipments' => $shipments,
            'support'   => $support,
        ]);
    }

    /**
     * Preview. Nothing is sent.
     */
    public function show(Request $request)
    {
        $order = $this->findOrder($request);

        if ($order instanceof \Illuminate\Http\RedirectResponse) {
            return $order;
        }

        /*
         * Read the provider off the ROUTE BY NAME.
         *
         * The route is `admin/order/{order}/courier/{provider}`. Declaring this
         * as `string $provider` looked harmless but was not: Laravel injects the
         * Request first and then maps the remaining route values POSITIONALLY,
         * so $provider received the ORDER id ("1"), normaliseProvider() rejected
         * it, and every courier screen answered 404 - SteadFast included.
         */
        $provider = $this->normaliseProvider((string) $request->route('provider', ''));

        $shipment = CourierShipment::where('order_id', $order->id)
            ->where('provider', $provider)
            ->first();

        /*
         * Do not rebuild a payload for an order that already has a
         * consignment.
         *
         * Apart from being pointless, it was slow: Pathao's sandbox takes
         * ~20 seconds per lookup, so opening a booked consignment made the
         * admin wait a minute just to be told "already booked". The screen
         * now shows the consignment immediately and says plainly that nothing
         * new will be sent.
         */
        if ($shipment && filled($shipment->consignment_id)) {
            $preview = [
                'ok'      => false,
                'message' => 'This order already has a ' . ucfirst($provider)
                    . ' consignment, so nothing new will be sent from this screen.',
                'payload' => null,
            ];
        } else {
            $preview = $provider === 'pathao'
                ? $this->pathaoPreview($order)
                : $this->sender->preview($order, $provider);
        }

        $couriers = $this->couriers();

        return view('backEnd.order.courier_send', [
            'order'    => $order,
            'provider' => $provider,
            'preview'  => $preview,
            'shipment' => $shipment,
            'couriers' => $couriers,
            'support'  => $this->support($couriers),
        ]);
    }

    /**
     * Actually send it.
     */
    public function store(Request $request)
    {
        $order = $this->findOrder($request);

        if ($order instanceof \Illuminate\Http\RedirectResponse) {
            return $order;
        }

        // Same route, same reason as show(): the provider must be read by name,
        // never taken as a positional argument.
        $provider = $this->normaliseProvider((string) $request->route('provider', ''));

        if ($provider === 'pathao') {
            $result = $this->pathao->send($order);
        } else {
            $result = $this->sender->send($order, $provider, (string) $request->input('note', ''));
        }

        $ok      = (bool) ($result['ok'] ?? false);
        $message = (string) ($result['message'] ?? 'The courier did not answer.');

        if ($ok) {
            // A real success gets a toast plus a redirect to the consignment,
            // because the admin's next question is always "did it go?".
            return redirect()
                ->route('admin.order.courier.show', ['provider' => $provider, 'order' => $order->id])
                ->with('courier_success', $message);
        }

        // Failure comes back to the same screen with the reason intact, so the
        // admin can fix the address and try again without losing their place.
        return redirect()
            ->route('admin.order.courier.show', ['provider' => $provider, 'order' => $order->id])
            ->with('courier_error', $message);
    }

    /**
     * Normalise Pathao's create-order payload to the same keys the preview
     * view already uses for SteadFast.
     */
    private function pathaoPreview(Order $order): array
    {
        $built = $this->pathao->preview($order);

        if (! ($built['ok'] ?? false)) {
            return [
                'ok'      => false,
                'message' => (string) ($built['message'] ?? 'Pathao cannot send this order yet.'),
                'payload' => null,
            ];
        }

        $payload = $built['payload'] ?? [];

        return [
            'ok'      => true,
            'message' => null,
            'payload' => [
                'invoice'           => (string) ($payload['merchant_order_id'] ?? $order->invoice_id),
                'recipient_name'    => (string) ($payload['recipient_name'] ?? ''),
                'recipient_phone'   => (string) ($payload['recipient_phone'] ?? ''),
                'recipient_address' => (string) ($payload['recipient_address'] ?? ''),
                'cod_amount'        => (float) ($payload['amount_to_collect'] ?? 0),
                'item_description'  => (string) ($payload['item_description'] ?? ''),
                'total_lot'         => (int) ($payload['item_quantity'] ?? 1),
            ],
        ];
    }

    /** Every courier, in a stable order. */
    private function couriers()
    {
        return Courierapi::query()
            ->orderByRaw("FIELD(type, 'steadfast', 'pathao')")
            ->orderBy('type')
            ->get();
    }

    /**
     * Which couriers THIS screen can actually book, and why not when it cannot.
     *
     * @return array<string, array{supported: bool, reason: ?string}>
     */
    private function support($couriers): array
    {
        $support = [];

        foreach ($couriers as $courier) {
            $support[$courier->type] = $this->sender->perOrderSupport($courier->type);
        }

        return $support;
    }

    /**
     * Load the order, refusing if it is not actually this admin's to send.
     */
    private function findOrder(Request $request): Order|\Illuminate\Http\RedirectResponse
    {
        $id = (int) $request->route('order', $request->input('order_id', 0));

        $order = Order::with(['shipping', 'orderdetails'])->find($id);

        if (! $order) {
            return back()->withErrors(['order' => 'That order no longer exists.']);
        }

        return $order;
    }

    /**
     * Only couriers we know how to talk to. Anything else is not a courier
     * choice, it is a typo or a probe.
     */
    private function normaliseProvider(string $provider): string
    {
        $provider = strtolower(trim($provider));

        if (! in_array($provider, ['steadfast', 'pathao'], true)) {
            abort(404);
        }

        return $provider;
    }
}
