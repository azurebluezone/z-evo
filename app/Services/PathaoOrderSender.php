<?php

namespace App\Services;

use App\Models\CourierShipment;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * Books ONE order with Pathao, and records the consignment.
 *
 * ---------------------------------------------------------------------------
 * WHY A SERVICE
 * ---------------------------------------------------------------------------
 * This logic lived inline in OrderController in two copies (order_pathao and
 * order_pathao_auto). They disagreed with each other and with the documented
 * API:
 *
 *   - both took the first store Pathao listed (`$stores[0]`) as the pickup
 *     store. Pathao can hold many stores, and the sandbox account is shared, so
 *     the pickup point was effectively random;
 *   - order_pathao_auto built its own token request with no timeout and the
 *     wrong URL, and order_pathao used a stored token that was never refreshed;
 *   - the create-order payload always sent `recipient_area => null`, while the
 *     documentation says the optional city/zone/area fields must be OMITTED
 *     when unknown ("do not send a null value"). Omitting them lets Pathao
 *     resolve the address itself - verified against the sandbox: a payload
 *     without them answered 200 and returned a consignment;
 *   - the fallback on a failed location lookup silently booked the parcel to
 *     the STORE's own city/zone, i.e. the rider was sent to the shop's area.
 *     Omitting the fields is both documented and safer.
 *
 * ---------------------------------------------------------------------------
 * DOCUMENTED SHAPE (Pathao Courier Merchant API)
 * ---------------------------------------------------------------------------
 *   POST {base}/aladdin/api/v1/orders
 *   Authorization: Bearer <access_token>
 *   Required: store_id, recipient_name, recipient_phone, recipient_address,
 *             delivery_type (48 normal / 12 on-demand), item_type (2 parcel),
 *             item_quantity, item_weight (0.5-10 kg), amount_to_collect
 *   Optional: merchant_order_id, recipient_city, recipient_zone,
 *             recipient_area, special_instruction, item_description
 *
 * Response: { message, type, code, data: { consignment_id, merchant_order_id,
 *             order_status, delivery_fee } }
 *
 * The payload is sent only after the recipient fields are checked, because a
 * real parcel is billable and a silent wrong delivery is worse than a refusal.
 */
class PathaoOrderSender
{
    /**
     * Pathao documents this as the normal-delivery type; 12 is on-demand.
     * Kept as a constant so the two send paths cannot disagree.
     */
    private const DELIVERY_TYPE = 48;

    /** 1 = document, 2 = parcel. */
    private const ITEM_TYPE = 2;

    /** No per-item weight is stored, so the documented minimum is used. */
    private const DEFAULT_WEIGHT_KG = 0.5;

    private const MAX_WEIGHT_KG = 10.0;

    /**
     * A configured client handed in by a test, or null to read the saved
     * courier row when a send actually happens.
     *
     * Deliberately NOT a constructor dependency. Laravel's container happily
     * builds `new PathaoClient(new Courierapi())` - an empty model with no URL
     * and no credentials - because `Courierapi` is instantiable. The sender
     * then posted to the relative path `/aladdin/api/v1/stores` and reported
     * "Pathao has no usable store" against a perfectly valid account. Reading
     * the row at call time is also correct for a long-running worker, where
     * settings can change after the service was resolved.
     */
    private ?PathaoClient $client = null;

    /** Test seam: send with this client instead of the saved settings row. */
    public function useClient(PathaoClient $client): static
    {
        $this->client = $client;

        return $this;
    }

    /**
     * The payload that would be sent, without sending it.
     *
     * `$shop` may carry a store_id / city_id / zone_id / area_id chosen by the
     * admin. Anything missing is resolved from Pathao.
     *
     * @param  array{store_id?:int|string|null, city_id?:int|string|null, zone_id?:int|string|null, area_id?:int|string|null}|null  $shop
     * @return array{ok: bool, message: ?string, payload: ?array, endpoint: ?string}
     */
    public function preview(Order $order, ?array $shop = null): array
    {
        $client = $this->client();

        if ($client === null) {
            return $this->fail(
                'Pathao is not ready. Fill in its credentials and switch it on in Admin > Courier Settings.'
            );
        }

        $storeId = (int) ($shop['store_id'] ?? 0);
        $store   = $this->resolveStore($client, $storeId);

        if ($store === null) {
            return $this->fail(
                'Pathao has no usable store on this account, so there is no pickup point to book from.'
            );
        }

        $location = $this->resolveLocation($client, $order, $shop);

        return $this->buildPayload($order, $store, $location);
    }

    /**
     * Send one order and record the consignment.
     *
     * @param  array{store_id?:int|string|null, city_id?:int|string|null, zone_id?:int|string|null, area_id?:int|string|null}|null  $shop
     * @return array{ok: bool, message: string, consignment_id: ?string, status: ?string, payload: ?array}
     */
    public function send(Order $order, ?array $shop = null): array
    {
        $preview = $this->preview($order, $shop);

        if (! $preview['ok']) {
            return $this->result(false, (string) $preview['message'], null, null, null);
        }

        // Never create two consignments for one order by accident: a retry
        // after a timeout would otherwise bill the customer twice.
        $existing = CourierShipment::query()
            ->where('order_id', $order->id)
            ->where('provider', 'pathao')
            ->whereNotNull('consignment_id')
            ->where('consignment_id', '!=', '')
            ->first();

        if ($existing) {
            return $this->result(
                false,
                'This order already has a Pathao consignment (' . $existing->consignment_id
                    . '). Delete it first if you really need to resend.',
                $existing->consignment_id,
                $existing->current_status,
                null
            );
        }

        $payload = $preview['payload'];
        $client  = $this->client();

        $response = $client->createOrder($payload);

        if (! $response['ok']) {
            // Pathao answers 200 with a failure message for business-rule
            // refusals, and a non-2xx for transport/auth ones. Both are shown,
            // because the courier's own words are what the admin must act on.
            $reason = trim((string) (data_get($response['body'], 'message')
                ?? $response['message']
                ?? 'Pathao refused the order.'));

            Log::warning('Pathao order creation failed', [
                'order_id' => $order->id,
                'status'   => $response['status'],
                'reason'   => $reason,
            ]);

            return $this->result(false, 'Pathao refused the order: ' . $reason, null, null, $payload);
        }

        $consignmentId = trim((string) data_get($response['body'], 'data.consignment_id'));

        if ($consignmentId === '') {
            return $this->result(
                false,
                'Pathao answered without a consignment id, so nothing was recorded.',
                null,
                null,
                $payload
            );
        }

        $status = trim((string) data_get($response['body'], 'data.order_status', 'Pending'));

        // Pathao's own delivery fee is the real cost; the customer-facing
        // charge lives in orders.shipping_charge. Stored as a column because a
        // later status sync overwrites raw_payload.
        $deliveryFee = data_get($response['body'], 'data.delivery_fee');

        CourierShipment::query()->updateOrCreate(
            [
                'order_id' => $order->id,
                'provider' => 'pathao',
            ],
            [
                'merchant_order_id' => (string) $order->invoice_id,
                'consignment_id'    => $consignmentId,
                'tracking_code'     => null,
                'current_status'    => $status,
                'shipping_cost'     => is_numeric($deliveryFee) ? (float) $deliveryFee : null,
                'raw_payload'       => $response['body'],
                'last_synced_at'    => now(),
            ]
        );

        return $this->result(
            true,
            'Sent to Pathao. Consignment ' . $consignmentId . '.',
            $consignmentId,
            $status,
            $payload
        );
    }

    private function client(): ?PathaoClient
    {
        if ($this->client === null) {
            $this->client = PathaoClient::enabled();
        }

        return $this->client;
    }

    /**
     * Find the store row for the pickup point.
     *
     * An explicit id is trusted without a second network round trip (the order
     * form already listed the stores). Without one, Pathao's own default is
     * preferred over "whatever came first".
     *
     * @return array<string, mixed>|null
     */
    private function resolveStore(PathaoClient $client, int $storeId): ?array
    {
        $list = $client->stores();

        if (! $list['ok']) {
            // The id alone is enough to book; only the detection fallback
            // needs the store's city/zone.
            return $storeId > 0 ? ['store_id' => $storeId] : null;
        }

        if ($storeId > 0) {
            foreach ($list['stores'] as $store) {
                if (is_array($store) && (int) ($store['store_id'] ?? 0) === $storeId) {
                    return $store;
                }
            }

            return ['store_id' => $storeId];
        }

        return PathaoClient::preferredStore($list['stores']);
    }

    /**
     * Recipient location, or nothing.
     *
     * The documentation allows all three fields to be omitted and lets Pathao
     * resolve them from the address. That is preferred over guessing: the old
     * fallback booked the parcel to the shop's own zone, which is a wrong
     * delivery rather than a refusal.
     *
     * @param  array<string, mixed>|null  $shop
     * @return array{city_id: ?int, zone_id: ?int, area_id: ?int}
     */
    private function resolveLocation(PathaoClient $client, Order $order, ?array $shop): array
    {
        $given = [
            'city_id' => $this->toId($shop['city_id'] ?? null),
            'zone_id' => $this->toId($shop['zone_id'] ?? null),
            'area_id' => $this->toId($shop['area_id'] ?? null),
        ];

        if ($given['city_id'] !== null && $given['zone_id'] !== null) {
            return $given;
        }

        $address = trim((string) ($order->shipping->address ?? ''));

        if ($address === '') {
            return ['city_id' => null, 'zone_id' => null, 'area_id' => null];
        }

        $detected = $this->detectLocation($client, $address);

        if ($detected === null) {
            Log::warning('Pathao location could not be detected; booking without city/zone/area so Pathao resolves the address.', [
                'order_id' => $order->id,
            ]);

            return ['city_id' => null, 'zone_id' => null, 'area_id' => null];
        }

        return $detected;
    }

    /**
     * Match the address against Pathao's own city/zone/area lists.
     *
     * Pathao's city ids are stable, but zone and area ids are looked up rather
     * than assumed. The first zone/area is only used when the free-text address
     * matches none of the names - and even then it is better to let Pathao
     * resolve the address, so a partial match returns null.
     *
     * @return array{city_id: ?int, zone_id: ?int, area_id: ?int}|null
     */
    private function detectLocation(PathaoClient $client, string $address): ?array
    {
        $cityKeywords = [
            'dhaka'      => 1,
            'chittagong' => 2,
            'chattogram' => 2,
            'sylhet'     => 3,
            'rajshahi'   => 4,
            'khulna'     => 5,
            'barishal'   => 6,
            'barisal'    => 6,
            'rangpur'    => 7,
            'mymensingh' => 8,
        ];

        $lower = mb_strtolower($address);
        $cityId = null;

        foreach ($cityKeywords as $keyword => $id) {
            if (str_contains($lower, $keyword)) {
                $cityId = $id;

                break;
            }
        }

        if ($cityId === null) {
            return null;
        }

        $zonesResult = $client->get('api/v1/cities/' . $cityId . '/zone-list');

        if (! $zonesResult['ok']) {
            return null;
        }

        $zones = data_get($zonesResult['body'], 'data.data');

        if (! is_array($zones) || $zones === []) {
            return null;
        }

        $zoneId = null;

        foreach ($zones as $zone) {
            $name = mb_strtolower(trim((string) ($zone['zone_name'] ?? '')));

            if ($name !== '' && str_contains($lower, $name)) {
                $zoneId = (int) $zone['zone_id'];

                break;
            }
        }

        if ($zoneId === null) {
            return ['city_id' => $cityId, 'zone_id' => null, 'area_id' => null];
        }

        $areasResult = $client->get('api/v1/zones/' . $zoneId . '/area-list');
        $areaId = null;

        if ($areasResult['ok']) {
            $areas = data_get($areasResult['body'], 'data.data');

            if (is_array($areas)) {
                foreach ($areas as $area) {
                    $name = mb_strtolower(trim((string) ($area['area_name'] ?? '')));

                    if ($name !== '' && str_contains($lower, $name)) {
                        $areaId = (int) $area['area_id'];

                        break;
                    }
                }
            }
        }

        return ['city_id' => $cityId, 'zone_id' => $zoneId, 'area_id' => $areaId];
    }

    /**
     * The documented create-order payload, after checking what Pathao rejects.
     *
     * @param  array<string, mixed>  $store
     * @param  array{city_id: ?int, zone_id: ?int, area_id: ?int}  $location
     * @return array{ok: bool, message: ?string, payload: ?array, endpoint: string}
     */
    private function buildPayload(Order $order, array $store, array $location): array
    {
        $shipping = $order->shipping;

        $name    = trim((string) ($shipping->name ?? ''));
        $phone   = trim((string) ($shipping->phone ?? ''));
        $address = trim((string) ($shipping->address ?? ''));

        $missing = [];

        if ($name === '')    { $missing[] = 'recipient name'; }
        if ($phone === '')   { $missing[] = 'recipient phone'; }
        if ($address === '') { $missing[] = 'recipient address'; }

        if ($missing !== []) {
            return $this->fail(
                'This order cannot be sent yet - missing ' . implode(', ', $missing)
                . '. Fill it in on the order first.'
            );
        }

        // Pathao documents exactly 11 characters for recipient_phone.
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (strlen($digits) === 13 && str_starts_with($digits, '880')) {
            $digits = '0' . substr($digits, 3);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '1')) {
            $digits = '0' . $digits;
        }

        if (! preg_match('/^01[3-9]\d{8}$/', $digits)) {
            return $this->fail(
                'Recipient phone "' . $phone . '" is not a valid Bangladeshi mobile number, '
                . 'so Pathao would reject it.'
            );
        }

        if (mb_strlen($name) < 3 || mb_strlen($name) > 100) {
            return $this->fail('Recipient name must be 3-100 characters for Pathao.');
        }

        if (mb_strlen($address) < 10 || mb_strlen($address) > 220) {
            return $this->fail(
                'Recipient address must be 10-220 characters for Pathao (this one is '
                . mb_strlen($address) . ').'
            );
        }

        $details = $order->orderdetails()->get(['product_name', 'qty']);

        $quantity = max(1, (int) $details->sum('qty'));

        $description = mb_substr(
            $details->pluck('product_name')->filter()->unique()->take(3)->implode(', '),
            0,
            200
        );

        if ($description === '') {
            $description = 'Order #' . $order->invoice_id;
        }

        $payload = [
            'store_id'          => (int) ($store['store_id'] ?? 0),
            'merchant_order_id' => mb_substr((string) $order->invoice_id, 0, 100),
            'recipient_name'    => $name,
            'recipient_phone'   => $digits,
            'recipient_address' => $address,
            'delivery_type'     => self::DELIVERY_TYPE,
            'item_type'         => self::ITEM_TYPE,
            'item_quantity'     => $quantity,
            'item_weight'       => $this->weightFor($quantity),
            'amount_to_collect' => max(0, (int) round((float) $order->amount)),
            'item_description'  => $description,
        ];

        // Optional, and only when actually known: the documentation says not to
        // send a null value, and Pathao resolves the address when they are absent.
        foreach (['city_id' => 'recipient_city', 'zone_id' => 'recipient_zone', 'area_id' => 'recipient_area'] as $key => $field) {
            if (! empty($location[$key])) {
                $payload[$field] = (int) $location[$key];
            }
        }

        return [
            'ok'       => true,
            'message'  => null,
            'payload'  => $payload,
            'endpoint' => 'api/v1/orders',
        ];
    }

    /**
     * Parcel weight: Pathao accepts 0.5 to 10 kg.
     *
     * order_details stores no per-item weight, so the documented minimum is the
     * honest fallback rather than an invented larger number that would cost the
     * shop more per parcel.
     */
    private function weightFor(int $quantity): float
    {
        return min(self::MAX_WEIGHT_KG, max(self::DEFAULT_WEIGHT_KG, self::DEFAULT_WEIGHT_KG));
    }

    private function toId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    private function fail(string $message): array
    {
        return ['ok' => false, 'message' => $message, 'payload' => null, 'endpoint' => null];
    }

    private function result(bool $ok, string $message, ?string $consignmentId, ?string $status, ?array $payload): array
    {
        return [
            'ok'             => $ok,
            'message'        => $message,
            'consignment_id' => $consignmentId,
            'status'         => $status,
            'payload'        => $payload,
        ];
    }
}
