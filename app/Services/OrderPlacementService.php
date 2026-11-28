<?php

namespace App\Services;

use App\Models\District;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Payment;
use App\Models\Shipping;
use App\Support\EventId;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * One place where an order is written.
 *
 * The storefront checkout (CustomerController@order_save) and the landing page
 * order form (ShoppingController@landingOrder) must store the SAME data in the
 * SAME tables: orders, shippings, payments and order_details. Before this
 * service the landing form only pushed a line into the session cart, so a
 * visitor had to type name, phone and address a second time on the checkout
 * page.
 *
 * place() writes the order rows for a set of lines and one recipient.
 * trackPurchase() fires the server-side purchase events, so both paths report
 * the same conversion once, with the same deterministic event id.
 */
class OrderPlacementService
{
    /**
     * Write the order, its shipping row, its payment row and its detail rows.
     *
     * $lines: one entry per ordered line:
     *   product_id, name, qty, price (the price actually charged),
     *   regular_price, purchase_price, product_color, product_size,
     *   campaign_id, variant_id
     *
     * $customer: customer_id, name, phone, address, area (the area NAME),
     *   payment_method
     *
     * $options: shipping_fee, discount, fraud_source (checkout | landing),
     *   invoice_id, placed_at, payment_status, shipping_is_free,
     *   shipping_list_price, reservation_ttl_minutes, fraud_check, source,
     *   user_id, notify (default true; seeders pass false), visitor_id,
     *   attribution_channel.
     *
     * The money is computed here from the line prices, never from the request.
     * The dev order seeder is the reason for the deterministic options: it must
     * be able to place a historical order with the invoice, date and payment
     * state the report fixtures expect, through this same real code path.
     */
    public function place(array $lines, array $customer, array $options = []): Order
    {
        $shippingFee = max(0.0, (float) ($options['shipping_fee'] ?? 0));
        $discount = max(0.0, (float) ($options['discount'] ?? 0));

        $placedAt = $options['placed_at'] ?? null;
        $placedAt = $placedAt instanceof Carbon
            ? $placedAt->copy()
            : ($placedAt ? Carbon::parse((string) $placedAt) : Carbon::now());

        $shippingIsFree = (bool) ($options['shipping_is_free'] ?? false);
        $reservationTtl = max(1, (int) ($options['reservation_ttl_minutes'] ?? 180));

        $subtotal = 0.0;
        foreach ($lines as $line) {
            $subtotal += ((float) ($line['price'] ?? 0)) * ((int) ($line['qty'] ?? 0));
        }

        $amount = max(0.0, ($subtotal + $shippingFee) - $discount);

        $order = new Order();
        $order->invoice_id = (string) ($options['invoice_id'] ?? rand(11111, 99999));
        $order->amount = $amount;
        $order->discount = $discount;
        $order->shipping_charge = $shippingFee;
        $order->shipping_is_free = $shippingIsFree ? 1 : 0;
        $order->shipping_list_price = $options['shipping_list_price'] ?? null;
        $order->customer_id = $customer['customer_id'];
        $order->order_status = (int) ($options['order_status'] ?? 1);
        $order->created_at = $placedAt;
        $order->updated_at = $placedAt;
        $order->save();

        // The audit trail: the storefront order is a fact worth recording.
        app(\App\Services\AuditLogger::class)->orderCreated(
            $order,
            (string) ($options['source'] ?? \App\Services\AuditLogger::SOURCE_WEB)
        );

        $shipping = new Shipping();
        $shipping->order_id = $order->id;
        $shipping->customer_id = $customer['customer_id'];
        $shipping->name = $customer['name'];
        $shipping->phone = $customer['phone'];
        $shipping->address = $customer['address'];
        $shipping->area = $customer['area'];

        // district_id is the geography the report layer groups by. When the
        // caller supplies it, the division is copied from the same reference
        // row so shippings.division can never disagree with its district. A
        // caller that supplies no district leaves both NULL: no invented
        // geography (the old rows are backfill-impossible by design).
        if (!empty($customer['district_id'])) {
            $district = District::query()->find((int) $customer['district_id']);

            if ($district) {
                $shipping->district_id = (int) $district->id;
                $shipping->division = $district->division;
            }
        }

        $shipping->created_at = $placedAt;
        $shipping->updated_at = $placedAt;
        $shipping->save();

        $payment = new Payment();
        $payment->order_id = $order->id;
        $payment->customer_id = $customer['customer_id'];
        $payment->payment_method = $customer['payment_method'];
        $payment->amount = $order->amount;
        $payment->payment_status = (string) ($options['payment_status'] ?? 'pending');
        $payment->created_at = $placedAt;
        $payment->updated_at = $placedAt;
        $payment->save();

        $this->writeDetails($order, $lines, $placedAt);

        // Phase 3 item 2: one primary attribution row per order, written on the
        // same path that writes the order. The visitor's first-touch channel
        // already lives on visitors_master (VisitorIdentify), so nothing is
        // guessed from the request body here. A failure must never fail a paid
        // order, so it is logged and the order stands.
        try {
            app(\App\Services\Analytics\OrderAttributionRecorder::class)->record(
                $order,
                $placedAt,
                [
                    'visitor_id' => $options['visitor_id'] ?? null,
                    'channel' => $options['attribution_channel'] ?? null,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Order attribution record failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        // The single stock path: a hold in the ledger plus one row in
        // inventory_reservations. writeDetails() only writes the line rows.
        app(InventoryLedger::class)->reserveOrder(
            $order,
            $placedAt,
            $reservationTtl,
            isset($options['user_id']) ? (int) $options['user_id'] : null
        );

        // The fraud check reads the phone and the order, so it can only run
        // after the order exists. It is a convenience, never a blocker.
        if (($options['fraud_check'] ?? true) === true) {
            try {
                app(CourierFraudService::class)->checkAndAttachToOrder(
                    $order,
                    (string) $customer['phone'],
                    null,
                    (string) ($options['fraud_source'] ?? 'checkout'),
                    720
                );
            } catch (\Throwable $e) {
                Log::warning('Order fraud check failed', [
                    'order_id' => $order->id,
                    'phone' => $customer['phone'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // The order, its lines and its stock hold are complete: publish the
        // event that sends the customer receipt and the admin copy. Seeders
        // pass notify=false so historical fixtures do not fill the inbox.
        if (($options['notify'] ?? true) !== false) {
            event(new \App\Events\OrderPlaced($order));
        }

        return $order;
    }

    /**
     * Fire the server-side purchase events for a placed order.
     *
     * The browser pixel fires on the order-success page and both sides must
     * carry the same event id, so it is derived from the order itself.
     *
     * $options: visitor_id, url, page_path, user_data (email, phone, first_name)
     */
    public function trackPurchase(Order $order, array $lines, array $options = []): void
    {
        $productIds = [];
        $numItems = 0;
        foreach ($lines as $line) {
            $productIds[] = (int) ($line['product_id'] ?? 0);
            $numItems += (int) ($line['qty'] ?? 0);
        }

        $orderData = [
            'order_id' => $order->id,
            'amount' => $order->amount,
            'product_ids' => $productIds,
            'num_items' => $numItems,
        ];

        $userData = [
            'email' => $options['user_data']['email'] ?? null,
            'phone' => $options['user_data']['phone'] ?? null,
            'first_name' => $options['user_data']['first_name'] ?? null,
            'external_id' => $order->customer_id ?: $order->id,
        ];

        $purchaseEventId = EventId::purchase($order);

        // Only the first view of the confirmation page should fire the browser
        // pixel. Without this marker a refresh (or a stranger opening the URL)
        // re-fires a Purchase.
        Session::put('recent_order_' . $order->id, true);

        // Facebook Server-Side Tracking - Purchase Event
        if (app(FacebookTrackingService::class)->isEnabled()) {
            app(FacebookTrackingService::class)->sendPurchaseEvent($orderData, $userData, $purchaseEventId);
        }

        try {
            $tiktok = app(TikTokTrackingService::class);
            if ($tiktok->isEnabled()) {
                $tiktok->sendPurchaseEvent($orderData, $userData, $purchaseEventId);
            }
        } catch (\Throwable $e) {
            // no-op
        }

        // GA4 Measurement Protocol - the fallback for a visitor whose browser
        // tag was blocked. Off unless TRACKING_GA4_SERVER_SIDE is on and both
        // credentials exist; GA4 deduplicates it against the browser purchase
        // by transaction_id (the invoice number).
        try {
            $ga4 = app(Ga4TrackingService::class);
            if ($ga4->isEnabled()) {
                $ga4->sendPurchase($order);
            }
        } catch (\Throwable $e) {
            // no-op: a lost analytics event must never fail a paid order
        }

        // First-party record, independent of whether the ad platform call
        // succeeded: the shop's own analytics must never depend on Meta being up.
        try {
            $visitorId = $options['visitor_id'] ?? null;
            if (is_string($visitorId) && $visitorId !== '') {
                app(\App\Services\Analytics\EventRecorder::class)->recordBatch(
                    $visitorId,
                    [[
                        'name' => 'Purchase',
                        'event_id' => $purchaseEventId,
                        'url' => $options['url'] ?? null,
                        'page_path' => $options['page_path'] ?? '/checkout',
                        'session_id' => $options['session_id'] ?? null,
                        'ts' => now()->toIso8601String(),
                        'event_data' => $orderData,
                    ]],
                    \App\Services\Analytics\EventRecorder::METHOD_SERVER
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Purchase event record failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * One order_details row per line.
     *
     * Every line must end up with a variant_id: `order_details.variant_id` is
     * the join the inventory ledger uses, and a line without it cannot be
     * reserved, sold or returned. When the caller did not pass one, the
     * product's default variant is resolved (or created once) BEFORE the row is
     * written, so the stored row is always complete.
     *
     * Stock is NOT touched here: OrderPlacementService::place() hands the order
     * to InventoryLedger::reserveOrder() after this runs, and that is the only
     * writer of reservations and ledger movements.
     */
    protected function writeDetails(Order $order, array $lines, ?Carbon $at = null): void
    {
        $at = $at ?: Carbon::now();

        DB::transaction(function () use ($order, $lines, $at) {
            foreach ($lines as $line) {
                $qty = (int) ($line['qty'] ?? 0);

                $sizeValue = $line['product_size'] ?? null;
                $variantOptionId = $line['variant_id'] ?? null;
                if (empty($sizeValue) && !empty($variantOptionId)) {
                    $sizeValue = 'variant_id:' . (int) $variantOptionId;
                }

                $variantId = $this->resolveVariantId($line, $variantOptionId, $sizeValue);
                if (!$variantId) {
                    $variantId = $this->ensureDefaultVariant((int) $line['product_id']);
                }

                if (!$variantId) {
                    throw new \RuntimeException(
                        'Cannot resolve a variant for product ' . (int) $line['product_id']
                        . ' on order ' . $order->invoice_id . '; refusing to write an incomplete line.'
                    );
                }

                $orderDetails = new OrderDetails();
                $orderDetails->order_id = $order->id;
                $orderDetails->product_id = $line['product_id'];
                $orderDetails->product_name = $line['name'] ?? null;
                $orderDetails->purchase_price = $line['purchase_price'] ?? null;
                $orderDetails->product_color = $line['product_color'] ?? null;
                $orderDetails->product_size = $sizeValue;
                $orderDetails->sale_price = $line['price'] ?? null;
                $orderDetails->regular_price = $line['regular_price'] ?? ($line['price'] ?? null);

                $campaignId = !empty($line['campaign_id']) ? (int) $line['campaign_id'] : null;
                if ($campaignId && !DB::table('campaigns')->where('id', $campaignId)->exists()) {
                    // The campaign row may have been deleted after the form was
                    // rendered. The sale price is already snapshotted, so keep
                    // the money and drop only the dangling attribution.
                    $campaignId = null;
                }
                $orderDetails->campaign_id = $campaignId;
                $orderDetails->qty = $qty;
                $orderDetails->variant_id = $variantId;
                $orderDetails->created_at = $at;
                $orderDetails->updated_at = $at;
                $orderDetails->save();
            }
        });
    }

    /**
     * The product's default variant, created once when the catalog has none.
     *
     * The double-check inside the transaction keeps two concurrent checkouts
     * from creating the same SKU twice.
     */
    protected function ensureDefaultVariant(int $productId): ?int
    {
        if ($productId <= 0) {
            return null;
        }

        $product = \App\Models\Product::select('id', 'product_code')->find($productId);
        if (!$product) {
            return null;
        }

        $defaultSku = ($product->product_code ? $product->product_code : ('P' . $product->id)) . '-DEFAULT';

        $existing = DB::table('product_variants')->where('sku', $defaultSku)->value('id');
        if ($existing) {
            return (int) $existing;
        }

        return (int) DB::table('product_variants')->insertGetId([
            'product_id' => $product->id,
            'sku' => $defaultSku,
            'title' => 'Default',
            'price_override' => null,
            'weight_gram' => null,
            'status' => 1,
            'attributes_json' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * The authoritative variant of a line: the one the customer picked, when it
     * still belongs to the product.
     */
    protected function resolveVariantId(array $line, $variantOptionId, $sizeValue): ?int
    {
        $productId = (int) ($line['product_id'] ?? 0);

        if (!empty($variantOptionId)) {
            $candidateId = (int) $variantOptionId;
            $exists = DB::table('product_variants')
                ->where('id', $candidateId)
                ->where('product_id', $productId)
                ->exists();
            if ($exists) {
                return $candidateId;
            }
        }

        if (!empty($sizeValue) && preg_match('/\bvariant_id\s*:\s*(\d+)\b/i', (string) $sizeValue, $m)) {
            $candidateId = (int) $m[1];
            $exists = DB::table('product_variants')
                ->where('id', $candidateId)
                ->where('product_id', $productId)
                ->exists();
            if ($exists) {
                return $candidateId;
            }
        }

        return null;
    }
}
