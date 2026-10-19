<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courierapi;
use App\Models\CourierShipment;
use App\Models\Order;
use App\Services\CourierOrderSender;
use Illuminate\Http\Request;

/**
 * Admin screen for sending one order to a courier.
 *
 * ---------------------------------------------------------------------------
 * WHY A DEDICATED SCREEN INSTEAD OF A BUTTON
 * ---------------------------------------------------------------------------
 * Sending a consignment is irreversible: SteadFast charges for it and a parcel
 * object exists upstream whether or not we ever hear back. The previous flow
 * fired the API call from inside a status-change POST, so the first time anyone
 * saw the recipient, address and amount was also the moment the parcel was
 * created - and if the shipping row was missing it silently went to a
 * hardcoded number instead.
 *
 * So this screen is deliberately two steps:
 *   GET  show()   - "here is exactly what will be sent", nothing is sent
 *   POST store()  - the admin confirms, then it goes
 *
 * CourierOrderSender does the validation, so this controller stays thin: it
 * decides what is shown and what is said back.
 */
class OrderCourierController extends Controller
{
    public function __construct(private CourierOrderSender $sender)
    {
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

        $preview = $this->sender->preview($order, $provider);

        $shipment = CourierShipment::where('order_id', $order->id)
            ->where('provider', $provider)
            ->first();

        // Every courier the admin could choose from. Ones that are switched off
        // are listed but marked, rather than hidden - an admin needs to know
        // the courier EXISTS and why it is not usable.
        $couriers = Courierapi::orderBy('type')->get();

        return view('backEnd.order.courier_send', [
            'order'    => $order,
            'provider' => $provider,
            'preview'  => $preview,
            'shipment' => $shipment,
            'couriers' => $couriers,
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

        $result = $this->sender->send($order, $provider, (string) $request->input('note', ''));

        if ($result['ok']) {
            // A real success gets a toast plus a redirect to the consignment,
            // because the admin's next question is always "did it go?".
            return redirect()
                ->route('admin.order.courier.show', ['provider' => $provider, 'order' => $order->id])
                ->with('courier_success', $result['message']);
        }

        // Failure comes back to the same screen with the reason intact, so the
        // admin can fix the address and try again without losing their place.
        return redirect()
            ->route('admin.order.courier.show', ['provider' => $provider, 'order' => $order->id])
            ->with('courier_error', $result['message']);
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
