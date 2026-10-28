<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourierShipment;
use App\Models\Order;
use App\Models\OrderStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Inbound delivery-status callbacks from couriers.
 *
 * PUBLIC ENDPOINT. Both routes are reachable by anyone on the internet, which
 * is why they are wrapped in the courier.webhook middleware - a per-courier
 * bearer token compared in constant time, plus a per-IP rate limit. Before that
 * middleware existed this controller accepted unauthenticated POSTs, so anyone
 * could mark a real order delivered.
 *
 * PROVIDER PAYLOAD SHAPES (from official documentation, not guessed)
 * ---------------------------------------------------------------------------
 * SteadFast sends:
 *     {
 *       "notification_type": "delivery_status",
 *       "consignment_id": 12345,
 *       "invoice": "INV-67890",
 *       "cod_amount": 1500.00,
 *       "status": "Delivered",
 *       "delivery_charge": 100.00,
 *       "tracking_message": "Your package has been delivered successfully.",
 *       "updated_at": "2025-03-02 12:45:30"
 *     }
 * Note there is no tracking_code in the payload - matching has to go through
 * consignment_id or invoice.
 *
 * Pathao sends merchant_order_id / consignment_id with order_status.
 *
 * A NOTE ON STATUS NAMES
 * ---------------------------------------------------------------------------
 * The two providers disagree on vocabulary and casing:
 *     SteadFast  "Delivered", "in_review", "partial_delivered"
 *     Pathao     "delivered", "in_review"
 * SteadFast's human-readable form is title-cased while its API endpoints use
 * snake_case, so the raw string cannot be compared directly. Everything is
 * lower-cased before mapping.
 *
 * An UNRECOGNISED status is logged and IGNORED. Guessing a mapping would move
 * real orders into a wrong state - marking something delivered that was not is
 * far worse than leaving it pending for an admin to read.
 */
class CourierWebhookController extends Controller
{
    /**
     * SteadFast delivery-status callback.
     *
     * @see https://portal.packzy.com/api/v1 (Webhook Integration)
     */
    public function steadfast(Request $request)
    {
        $payload = $request->all();

        // SteadFast sends notification_type alongside the event; anything else
        // (balance refreshes, payment updates) is not ours to act on.
        $notificationType = strtolower(trim((string) data_get($payload, 'notification_type', '')));

        if ($notificationType !== '' && $notificationType !== 'delivery_status') {
            Log::info('SteadFast webhook ignored: unrelated notification type', [
                'notification_type' => $notificationType,
            ]);

            // 200 on purpose: the courier only retries on a failure status, and
            // a non-2xx would make it hammer this endpoint for something we
            // deliberately do not handle.
            return response()->json(['ok' => true, 'handled' => false]);
        }

        $consignmentId = trim((string) data_get($payload, 'consignment_id', ''));
        $invoice       = trim((string) data_get($payload, 'invoice', ''));

        // SteadFast's human-facing status string, e.g. "Delivered".
        $status = trim((string) data_get($payload, 'status', ''));

        // The machine message is a better description of what happened than the
        // status word, when present.
        $message = trim((string) data_get($payload, 'tracking_message', ''));

        $shipment = $this->findShipment('steadfast', $consignmentId, $invoice, $request);

        if (! $shipment) {
            Log::warning('SteadFast webhook: shipment not matched', [
                'consignment_id' => $consignmentId,
                'invoice' => $invoice,
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Shipment not found',
            ], 404);
        }

        return $this->applyStatus($shipment, 'steadfast', $status, $payload, $message);
    }

    /**
     * Pathao delivery-status callback. Pre-existing route, now authenticated.
     */
    public function pathao(Request $request)
    {
        $payload = $request->all();

        $merchantOrderId = trim((string) data_get($payload, 'merchant_order_id', ''));
        $consignmentId   = trim((string) data_get($payload, 'consignment_id', ''));
        $status          = trim((string) (data_get($payload, 'order_status') ?? data_get($payload, 'status') ?? ''));

        $shipment = $this->findShipment('pathao', $consignmentId, $invoice = '', $request, $merchantOrderId);

        if (! $shipment) {
            Log::warning('Pathao webhook: shipment not matched', [
                'merchant_order_id' => $merchantOrderId,
                'consignment_id' => $consignmentId,
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Shipment not found',
            ], 404);
        }

        return $this->applyStatus($shipment, 'pathao', $status, $payload);
    }

    /**
     * Resolve the shipment a callback refers to.
     *
     * Matching order matters: the most specific identifier wins, because a
     * consignment id is unique per parcel while a fallback on invoice alone
     * could match the wrong row if the courier ever reuses it.
     */
    private function findShipment(
        string $provider,
        string $consignmentId,
        string $invoice,
        Request $request,
        string $merchantOrderId = ''
    ): ?CourierShipment {
        $query = CourierShipment::query()->where('provider', $provider);

        if ($merchantOrderId !== '') {
            return (clone $query)->where('merchant_order_id', $merchantOrderId)->first();
        }

        if ($consignmentId !== '') {
            $byConsignment = (clone $query)->where('consignment_id', $consignmentId)->first();
            if ($byConsignment) {
                return $byConsignment;
            }
        }

        if ($invoice !== '') {
            // orders.invoice_id is the invoice WE generate and send to the
            // courier, so it is the reliable join back to our own order.
            return (clone $query)
                ->whereHas('order', fn ($q) => $q->where('invoice_id', $invoice))
                ->first();
        }

        return null;
    }

    /**
     * Store the courier status and, when it maps to one of our own statuses,
     * move the order across.
     */
    private function applyStatus(
        CourierShipment $shipment,
        string $provider,
        string $courierStatus,
        array $payload,
        string $message = ''
    ) {
        $raw = trim($courierStatus);

        if ($raw !== '') {
            $shipment->current_status = $raw;
        }

        // Keep the whole callback for support: when a customer says "the app
        // says delivered but I never got it", this is the evidence.
        $shipment->raw_payload = $payload;
        $shipment->last_synced_at = Carbon::now();
        $shipment->save();

        $slug = $this->mapCourierStatusToOrderStatusSlug($provider, $raw);

        /*
         * B5: append this callback to the event log BEFORE anything can
         * overwrite the shipment's single raw_payload. The log is append-only,
         * so a delivered -> returned transition keeps both steps instead of
         * leaving only the last one.
         */
        try {
            \App\Models\CourierEvent::create([
                'shipment_id'       => (int) $shipment->id,
                'order_id'          => (int) $shipment->order_id,
                'provider'          => $provider,
                'consignment_id'    => $shipment->consignment_id,
                'courier_status'    => $raw !== '' ? $raw : null,
                'order_status_slug' => $slug,
                'payload'           => $payload,
                'message'           => $message !== '' ? mb_substr($message, 0, 255) : null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Courier event log write failed', [
                'shipment_id' => $shipment->id,
                'error'       => $e->getMessage(),
            ]);
        }

        if ($slug === null) {
            // Unknown word: recorded on the shipment, but the ORDER is left
            // alone rather than being guessed at.
            Log::info('Courier status not mapped to an order status', [
                'provider' => $provider,
                'courier_status' => $raw,
                'shipment_id' => $shipment->id,
                'message' => $message,
            ]);

            return response()->json([
                'ok' => true,
                'mapped' => false,
                'status' => $raw,
            ]);
        }

        $status = OrderStatus::query()->where('slug', $slug)->select('id')->first();

        if (! $status) {
            // We have a mapping but our own statuses table has no such slug.
            // Also left unmapped, and loudly logged.
            Log::warning('Order status slug does not exist locally', [
                'slug' => $slug,
                'shipment_id' => $shipment->id,
            ]);

            return response()->json([
                'ok' => true,
                'mapped' => false,
                'reason' => 'unknown_local_status',
            ]);
        }

        $order = Order::query()->find($shipment->order_id);

        if ($order) {
            // Never walk an order backwards: a late "in_transit" callback must
            // not undo a "delivered" that already happened.
            if ($this->wouldRegress($order->order_status, $status->id)) {
                Log::info('Courier webhook would regress order status, ignored', [
                    'order_id' => $order->id,
                    'from' => $order->order_status,
                    'to' => $status->id,
                    'courier_status' => $raw,
                ]);

                return response()->json(['ok' => true, 'mapped' => true, 'regressed' => true]);
            }

            $order->order_status = $status->id;
            $order->save();
        }

        return response()->json([
            'ok' => true,
            'mapped' => true,
            'status' => $slug,
        ]);
    }

    /**
     * Courier status word -> our OrderStatus slug.
     *
     * Keys are lower-cased because SteadFast title-cases its webhook status
     * while using snake_case on its API, and Pathao sends lower case.
     */
    private function mapCourierStatusToOrderStatusSlug(string $provider, string $courierStatus): ?string
    {
        $status = strtolower(trim($courierStatus));

        if ($status === '') {
            return null;
        }

        $map = [
            // --- delivered -------------------------------------------------
            'delivered' => 'delivered',
            'delivered_approval_pending' => 'delivered',
            'partial_delivered' => 'delivered',
            'partial_delivered_approval_pending' => 'delivered',

            // --- cancelled / returned --------------------------------------
            'cancelled' => 'cancelled',
            'cancelled_approval_pending' => 'cancelled',
            // B6: these used to map to 'cancelled', so a returned parcel was
            // recorded as cancelled and the local 'returned' status (id 6) could
            // never be produced from courier data. They now map to 'returned'.
            'returned' => 'returned',
            'return_dispatched' => 'returned',
            'return_approval_pending' => 'returned',

            // --- failed / on hold -------------------------------------------
            'failed' => 'pending',
            'hold' => 'pending',
            'unknown' => null,
            'unknown_approval_pending' => null,

            // --- in progress ------------------------------------------------
            'pending' => 'pending',
            'in_review' => 'processing',
            'picked_up' => 'processing',
            'in_transit' => 'processing',
            'out_for_delivery' => 'processing',
        ];

        return $map[$status] ?? null;
    }

    /**
     * Should this transition be refused as a backwards step?
     *
     * OrderStatus ids are admin-defined rows, so their numeric order is not
     * guaranteed to reflect lifecycle order. Rather than assume, this compares
     * against a small set of terminal statuses: once an order is delivered or
     * cancelled, nothing moves it back automatically. A human can still change
     * it from the admin panel.
     */
    private function wouldRegress(mixed $currentId, int $incomingId): bool
    {
        if ($currentId === null) {
            return false;
        }

        $terminal = OrderStatus::query()
            ->whereIn('slug', ['delivered', 'cancelled', 'returned'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($terminal === []) {
            // No terminal statuses configured, so nothing can be protected.
            return false;
        }

        return in_array((int) $currentId, $terminal, true)
            && ! in_array((int) $incomingId, $terminal, true);
    }
}
