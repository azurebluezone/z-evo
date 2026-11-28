<?php

namespace App\Services\Analytics;

use App\Models\Order;
use App\Models\OrderAttribution;
use App\Models\VisitorMaster;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Writes the one primary attribution row of an order.
 *
 * The data already exists: VisitorIdentify classifies the first-touch channel
 * with TrafficSourceResolver and stores it on visitors_master together with the
 * utm_* columns. This class only carries that first-touch answer onto the order
 * at the moment the order is written, so a later report can divide attributed
 * revenue by marketing spend without guessing.
 *
 * Rules:
 *   - first touch wins. firstOrCreate means a second call for the same order
 *     returns the existing row instead of rewriting it, which is also why the
 *     unique index on order_id can never be violated from here.
 *   - no evidence means direct. An order from a visitor we never saw is
 *     honestly "direct", not "unknown" and not dropped.
 *   - nothing is inferred from the request body: only the visitor record, the
 *     request's resolved channel, and the explicit context passed by the caller.
 */
class OrderAttributionRecorder
{
    /**
     * @param  array{visitor_id?: string|null, channel?: string|null, medium?: string|null, campaign?: string|null}  $context
     */
    public function record(Order $order, ?Carbon $at = null, array $context = []): OrderAttribution
    {
        $request = app()->runningInConsole() ? null : request();

        $visitorId = $context['visitor_id'] ?? null;

        if ($visitorId === null && $request !== null) {
            $visitorId = $request->cookie('visitor_id') ?: $request->attributes->get('visitor_id');
        }

        $visitor = is_string($visitorId) && $visitorId !== ''
            ? VisitorMaster::query()->where('visitor_id', $visitorId)->first()
            : null;

        $source = $context['channel']
            ?? ($request?->attributes->get('visitor_channel'))
            ?? $visitor?->channel
            ?? TrafficSourceResolver::CHANNEL_DIRECT;

        return OrderAttribution::query()->firstOrCreate(
            ['order_id' => $order->id],
            [
                'source' => $this->clip($source, 32) ?? TrafficSourceResolver::CHANNEL_DIRECT,
                'medium' => $this->clip($context['medium'] ?? $visitor?->utm_medium, 32),
                'campaign' => $this->clip($context['campaign'] ?? $visitor?->utm_campaign, 120),
                'visitor_id' => $this->clip($visitorId, 64),
                'model' => OrderAttribution::MODEL_FIRST_TOUCH,
                'attributed_at' => $at ?? $order->created_at ?? Carbon::now(),
            ]
        );
    }

    /**
     * Trim and fit a value into its column, so a long utm_campaign can never
     * turn an order into a 1406 "data too long" failure.
     */
    private function clip(mixed $value, int $max): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return Str::limit($value, $max, '');
    }
}
