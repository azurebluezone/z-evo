<?php

namespace App\Services;

use App\Models\Courierapi;
use App\Models\CourierEvent;
use App\Models\CourierShipment;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * The courier dashboard's data.
 *
 * ---------------------------------------------------------------------------
 * WHAT THIS SCREEN IS FOR
 * ---------------------------------------------------------------------------
 * The admin runs a shop; the courier dashboard is not an API explorer. It has
 * to answer, in business language:
 *
 *   - what is my balance at the courier,
 *   - how many parcels did I book, and where are they,
 *   - how much cash is still out for collection,
 *   - which parcels need attention,
 *   - which pickup point will a Pathao parcel be collected from.
 *
 * ---------------------------------------------------------------------------
 * CORRECT ENDPOINTS ONLY
 * ---------------------------------------------------------------------------
 * The previous version probed invented routes (`get_all_orders`,
 * `get_all_pickup_stores`, `get_product_categories`). Every one of them answers
 * 404 on the live account, so the screen showed a wall of "not offered" while
 * the data that IS available stayed hidden.
 *
 * The endpoints below come from the courier's own API guide (work/c/*.md):
 *
 *   SteadFast  GET /get_balance   - what the courier owes us
 *              GET /parcel_states - the four dashboard counters + COD
 *              GET /stats         - booked/delivered/cancelled counts + COD
 *   Pathao     GET /aladdin/api/v1/stores - the pickup points
 *
 * The parcel rows we display come from our own `courier_shipments` table: those
 * are the parcels this shop booked, with the consignment ids the couriers gave
 * us. They are shown as "our parcels", never as the courier's own counter.
 */
class CourierDashboardService
{
    /** A dashboard may not hang on a slow courier. */
    private const TIMEOUT_SECONDS = 15;

    /** The courier counters change slowly; a few minutes of cache is safe. */
    private const LIVE_CACHE_MINUTES = 5;

    /** A store list almost never changes, and Pathao's sandbox is slow. */
    private const STORE_CACHE_MINUTES = 360;

    /**
     * Everything the dashboard view needs, in one call.
     */
    public function dashboard(): array
    {
        $couriers = Courierapi::query()
            ->orderByRaw("FIELD(type, 'steadfast', 'pathao')")
            ->orderBy('type')
            ->get();

        $snapshots = [];

        foreach ($couriers as $courier) {
            $snapshots[$courier->type] = $this->snapshot($courier);
        }

        return [
            'couriers'  => $couriers,
            'snapshots' => $snapshots,
            'totals'    => $this->totals($snapshots),
            'recent'    => $this->recentShipments(),
            'statuses'  => $this->statusBreakdown(),
        ];
    }

    /**
     * One courier account: balance, our parcel numbers, and the live data that
     * account actually exposes.
     */
    public function snapshot(Courierapi $courier): array
    {
        return [
            'courier' => $courier,
            'balance' => $this->balance($courier),
            'stats'   => $this->stats((string) $courier->type),
            'live'    => $this->live($courier),
        ];
    }

    /**
     * The live balance.
     *
     * SteadFast answers GET /get_balance. Pathao does not expose the merchant
     * balance on the courier API, so the screen says that instead of inventing
     * a zero.
     *
     * @return array{available: bool, amount: ?float, message: string, checked_at: ?string}
     */
    public function balance(Courierapi $courier): array
    {
        if ($courier->type === 'pathao') {
            return [
                'available'  => false,
                'amount'     => null,
                'message'    => 'Pathao keeps the merchant balance on its own portal, not on the courier API.',
                'checked_at' => null,
            ];
        }

        $base = $this->steadfastBaseUrl($courier);

        if ($base === '') {
            return [
                'available'  => false,
                'amount'     => null,
                'message'    => 'Add the courier base URL in Courier Settings.',
                'checked_at' => null,
            ];
        }

        try {
            $response = Http::withHeaders($this->steadfastHeaders($courier))
                ->timeout(self::TIMEOUT_SECONDS)
                ->acceptJson()
                ->get($base . '/get_balance');

            $body = $response->json();

            if ($response->successful() && is_array($body) && array_key_exists('current_balance', $body)) {
                return [
                    'available'  => true,
                    'amount'     => (float) $body['current_balance'],
                    'message'    => 'Live balance from the courier.',
                    'checked_at' => now()->toDateTimeString(),
                ];
            }

            return [
                'available'  => false,
                'amount'     => null,
                'message'    => $this->courierMessage($body, $response->status()),
                'checked_at' => now()->toDateTimeString(),
            ];
        } catch (\Throwable $e) {
            return [
                'available'  => false,
                'amount'     => null,
                'message'    => 'The courier could not be reached just now.',
                'checked_at' => now()->toDateTimeString(),
            ];
        }
    }

    /**
     * The live counters a courier exposes beyond the balance.
     *
     * SteadFast: the four dashboard states (in review, delivery processing, COD
     * processing, return requests) and the 30-day outcome stats, both straight
     * from the documented endpoints.
     *
     * Pathao: the merchant's pickup stores, because that is the one piece of
     * account data its courier API documents.
     */
    public function live(Courierapi $courier): array
    {
        return $courier->type === 'pathao'
            ? $this->pathaoStores($courier)
            : $this->steadfastCounters($courier);
    }

    /**
     * SteadFast's own dashboard counters.
     *
     * Both calls are GETs, so opening this screen never books, cancels or
     * changes anything upstream.
     */
    private function steadfastCounters(Courierapi $courier): array
    {
        $base = $this->steadfastBaseUrl($courier);

        if ($base === '') {
            return ['ok' => false, 'kind' => 'steadfast', 'error' => 'No courier base URL is configured.'];
        }

        $cacheKey = 'courier.dashboard.steadfast.' . md5($base . '|' . (string) $courier->api_key);

        return Cache::remember($cacheKey, now()->addMinutes(self::LIVE_CACHE_MINUTES), function () use ($base, $courier) {
            try {
                $headers = $this->steadfastHeaders($courier);

                $statesResponse = Http::withHeaders($headers)
                    ->timeout(self::TIMEOUT_SECONDS)
                    ->acceptJson()
                    ->get($base . '/parcel_states');

                $statsResponse = Http::withHeaders($headers)
                    ->timeout(self::TIMEOUT_SECONDS)
                    ->acceptJson()
                    ->get($base . '/stats');

                $statesBody = $statesResponse->json();
                $statsBody  = $statsResponse->json();

                if (! $statesResponse->successful() || ! is_array($statesBody)) {
                    return [
                        'ok'         => false,
                        'kind'       => 'steadfast',
                        'error'      => $this->courierMessage($statesBody, $statesResponse->status()),
                        'checked_at' => now()->toDateTimeString(),
                    ];
                }

                $states = [];

                foreach ([
                    'in_review'           => 'In review',
                    'delivery_processing' => 'Delivery processing',
                    'cod_processing'      => 'COD processing',
                    'return_requests'     => 'Return requests',
                ] as $key => $label) {
                    $states[] = [
                        'key'        => $key,
                        'label'      => $label,
                        'count'      => (int) data_get($statesBody, 'states.' . $key . '.count', 0),
                        'cod_amount' => (float) data_get($statesBody, 'states.' . $key . '.cod_amount', 0),
                    ];
                }

                $stats = is_array($statsBody) && isset($statsBody['total'])
                    ? [
                        'total'        => (int) data_get($statsBody, 'total.count', 0),
                        'total_cod'    => (float) data_get($statsBody, 'total.cod_amount', 0),
                        'delivered'    => (int) data_get($statsBody, 'delivered.count', 0),
                        'pending'      => (int) data_get($statsBody, 'pending.count', 0),
                        'cancelled'    => (int) data_get($statsBody, 'cancelled.count', 0),
                        'success_rate' => (float) data_get($statsBody, 'success_rate', 0),
                        'cancel_rate'  => (float) data_get($statsBody, 'cancel_rate', 0),
                        'from'         => (string) data_get($statsBody, 'from', ''),
                        'to'           => (string) data_get($statsBody, 'to', ''),
                    ]
                    : null;

                return [
                    'ok'         => true,
                    'kind'       => 'steadfast',
                    'states'     => $states,
                    'stats'      => $stats,
                    'error'      => null,
                    'checked_at' => now()->toDateTimeString(),
                ];
            } catch (\Throwable $e) {
                return [
                    'ok'         => false,
                    'kind'       => 'steadfast',
                    'error'      => 'The courier could not be reached just now.',
                    'checked_at' => now()->toDateTimeString(),
                ];
            }
        });
    }

    /**
     * Pathao's pickup stores.
     */
    private function pathaoStores(Courierapi $courier): array
    {
        $cacheKey = 'courier.dashboard.pathao.stores.' . md5((string) $courier->url . '|' . (string) $courier->client_id);

        return Cache::remember($cacheKey, now()->addMinutes(self::STORE_CACHE_MINUTES), function () use ($courier) {
            try {
                $client = new PathaoClient($courier);
                $result = $client->stores();

                if (! $result['ok']) {
                    return [
                        'ok'         => false,
                        'kind'       => 'pathao',
                        'error'      => $result['message'] ?: 'Pathao stores could not be read.',
                        'checked_at' => now()->toDateTimeString(),
                    ];
                }

                $active = array_values(array_filter(
                    $result['stores'],
                    fn ($store) => is_array($store) && (int) ($store['is_active'] ?? 1) === 1
                ));

                $preferred = PathaoClient::preferredStore($result['stores']);

                return [
                    'ok'           => true,
                    'kind'         => 'pathao',
                    'stores_count' => count($active),
                    'store_names'  => array_values(array_filter(array_map(
                        fn ($store) => trim((string) ($store['store_name'] ?? '')),
                        array_slice($active, 0, 3)
                    ))),
                    'preferred'    => $preferred ? [
                        'name'    => trim((string) ($preferred['store_name'] ?? '')),
                        'address' => trim((string) ($preferred['store_address'] ?? '')),
                    ] : null,
                    'error'        => null,
                    'checked_at'   => now()->toDateTimeString(),
                ];
            } catch (\Throwable $e) {
                return [
                    'ok'         => false,
                    'kind'       => 'pathao',
                    'error'      => 'Pathao could not be reached just now.',
                    'checked_at' => now()->toDateTimeString(),
                ];
            }
        });
    }

    /**
     * Our own parcel numbers for one provider.
     *
     * `booked_value` is the cash value of the orders we handed to this courier;
     * `delivered_value` is the part the courier says is delivered, and
     * `in_flight_value` is what is still out for collection.
     */
    public function stats(string $provider): array
    {
        $base = CourierShipment::query()->where('provider', $provider);

        $total = (clone $base)->count();
        $today = (clone $base)->whereDate('created_at', today())->count();
        $week  = (clone $base)->where('created_at', '>=', now()->subDays(7))->count();
        $month = (clone $base)->where('created_at', '>=', now()->subDays(30))->count();

        $rows = (clone $base)->get(['current_status', 'order_id', 'last_synced_at']);

        $amounts = Order::query()
            ->whereIn('id', $rows->pluck('order_id')->filter()->unique()->all())
            ->pluck('amount', 'id');

        $delivered = 0;
        $cancelled = 0;
        $bookedValue = 0.0;
        $deliveredValue = 0.0;
        $inFlightValue = 0.0;
        $statusCounts = [];

        foreach ($rows as $row) {
            $meta  = self::statusMeta($row->current_status);
            $value = (float) ($amounts[$row->order_id] ?? 0);

            $bookedValue += $value;

            if ($meta['group'] === 'delivered') {
                $delivered++;
                $deliveredValue += $value;
            } elseif ($meta['group'] === 'cancelled') {
                $cancelled++;
            } else {
                $inFlightValue += $value;
            }

            $key = $meta['label'];
            $statusCounts[$key] = ($statusCounts[$key] ?? 0) + 1;
        }

        arsort($statusCounts);

        $lastEvent = CourierEvent::query()->where('provider', $provider)->orderByDesc('id')->first();

        return [
            'total'           => $total,
            'today'           => $today,
            'week'            => $week,
            'month'           => $month,
            'delivered'       => $delivered,
            'cancelled'       => $cancelled,
            'pending'         => max(0, $total - $delivered - $cancelled),
            'booked_value'    => $bookedValue,
            'delivered_value' => $deliveredValue,
            'in_flight_value' => $inFlightValue,
            'status_counts'   => $statusCounts,
            'last_synced'     => $rows->max('last_synced_at'),
            'events'          => CourierEvent::query()->where('provider', $provider)->count(),
            'last_event'      => $lastEvent ? $lastEvent->created_at : null,
        ];
    }

    /**
     * Totals across every courier, for the top row.
     */
    private function totals(array $snapshots): array
    {
        $totals = [
            'total'           => 0,
            'delivered'       => 0,
            'pending'         => 0,
            'cancelled'       => 0,
            'booked_value'    => 0.0,
            'delivered_value' => 0.0,
            'in_flight_value' => 0.0,
            'today'           => 0,
            'week'            => 0,
            'month'           => 0,
        ];

        foreach ($snapshots as $snapshot) {
            $stats = $snapshot['stats'] ?? [];

            foreach (array_keys($totals) as $key) {
                $totals[$key] += (float) ($stats[$key] ?? 0);
            }
        }

        return $totals;
    }

    /**
     * The latest parcels we booked, with their status already translated for
     * the view.
     *
     * @return array<int, array{shipment: CourierShipment, status: array}>
     */
    private function recentShipments(int $limit = 12): array
    {
        return CourierShipment::query()
            ->with(['order.shipping'])
            ->orderByDesc('last_synced_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (CourierShipment $shipment) => [
                'shipment' => $shipment,
                'status'   => self::statusMeta($shipment->current_status),
            ])
            ->all();
    }

    /**
     * Our parcels grouped by the status the courier last reported.
     *
     * @return array<int, array{label: string, count: int, meta: array}>
     */
    private function statusBreakdown(): array
    {
        $rows = CourierShipment::query()
            ->selectRaw('current_status, COUNT(*) AS aggregate')
            ->groupBy('current_status')
            ->get();

        return $rows
            ->map(fn ($row) => [
                'label' => self::statusMeta($row->current_status)['label'],
                'count' => (int) $row->aggregate,
                'meta'  => self::statusMeta($row->current_status),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /**
     * Turn a courier status into a human label and a colour band.
     *
     * The couriers use different vocabularies (SteadFast: pending / delivered /
     * partial_delivered; Pathao: Pending / Picked / In_Transit / Delivered), so
     * the screen should not print the raw token or colour everything the same.
     *
     * @return array{label: string, class: string, group: string}
     */
    public static function statusMeta(?string $status): array
    {
        $raw = trim((string) $status);
        $key = strtolower($raw);

        if ($key === '') {
            return ['label' => 'Awaiting update', 'class' => 'bg-label-secondary', 'group' => 'pending'];
        }

        $label = ucwords(str_replace(['_', '-'], ' ', $key));

        if (str_contains($key, 'deliver') && ! str_contains($key, 'not_delivered')) {
            return ['label' => $label, 'class' => 'bg-label-success', 'group' => 'delivered'];
        }

        if (str_contains($key, 'cancel') || str_contains($key, 'return') || str_contains($key, 'refund')) {
            return ['label' => $label, 'class' => 'bg-label-danger', 'group' => 'cancelled'];
        }

        if (
            str_contains($key, 'pending')
            || str_contains($key, 'review')
            || str_contains($key, 'processing')
            || str_contains($key, 'pickup')
            || str_contains($key, 'pick')
            || str_contains($key, 'transit')
            || str_contains($key, 'hold')
        ) {
            return ['label' => $label, 'class' => 'bg-label-warning', 'group' => 'pending'];
        }

        return ['label' => $label, 'class' => 'bg-label-info', 'group' => 'pending'];
    }

    /**
     * The API root, whatever shape the admin typed it in.
     *
     * SteadFast's guide gives `https://portal.packzy.com/api/v1`. An admin who
     * pasted only the host would otherwise get a 404 on every call, which looks
     * exactly like "the API is wrong". Known endpoint suffixes are removed and
     * the documented /api/v1 root is restored for Packzy.
     */
    private function steadfastBaseUrl(Courierapi $courier): string
    {
        $url = rtrim(trim((string) $courier->url), '/');

        $url = preg_replace(
            '#/(create_order|get_balance|ping|parcel_states|stats|payments)$#',
            '',
            $url
        ) ?? $url;

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if (str_contains($host, 'packzy.com') && ! preg_match('#/api/v\d+$#', $url)) {
            $url .= '/api/v1';
        }

        return $url;
    }

    /** @return array<string, string> */
    private function steadfastHeaders(Courierapi $courier): array
    {
        return [
            'Api-Key'    => (string) $courier->api_key,
            'Secret-Key' => (string) $courier->secret_key,
            'Accept'     => 'application/json',
        ];
    }

    /** The courier's own reason, when it gave one. */
    private function courierMessage(mixed $body, int $status): string
    {
        $message = is_array($body) ? trim((string) ($body['message'] ?? '')) : '';

        if ($message !== '') {
            return $message;
        }

        return match (true) {
            $status === 401 => 'The courier refused the API keys.',
            $status === 403 => 'The courier account is not active yet.',
            $status === 404 => 'The courier did not recognise that request.',
            $status >= 500  => 'The courier is having a problem right now.',
            default         => 'The courier answered HTTP ' . $status . '.',
        };
    }
}
