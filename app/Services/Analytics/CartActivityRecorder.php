<?php

namespace App\Services\Analytics;

use App\Models\AbandonedCart;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Phase 3 item 5 — the cart lifecycle, stored.
 *
 * The shopping cart lives in the session, so it disappears with the browser
 * and leaves no evidence behind. This recorder keeps a durable row per cart
 * lifecycle and moves it through four states:
 *
 *   record()        called on every cart mutation: create the row, or refresh
 *                   item_count / subtotal / last_activity_at
 *   markAbandoned() called by `carts:mark-abandoned`: open rows idle past the
 *                   configured window become abandoned
 *   markConverted() called from OrderPlacementService::place(): the cart
 *                   produced an order, so it was never abandoned
 *   markRecovered() called by an admin: the recovery state is a stored fact,
 *                   not something a later report has to infer from logs
 *
 * A converted cart that sees new items in the same session is reopened as a
 * new lifecycle: the previous conversion is already recorded on the order.
 */
class CartActivityRecorder
{
    /**
     * Store or refresh the current cart snapshot.
     *
     * @return AbandonedCart|null null when there is no cart to record: no
     *                            session (CLI), an empty cart, or a missing key
     */
    public function record(
        int $itemCount,
        float $subtotal,
        ?string $cartKey = null,
        ?int $customerId = null
    ): ?AbandonedCart {
        $cartKey = $cartKey ?: $this->currentCartKey();

        if ($cartKey === null || $itemCount < 1) {
            return null;
        }

        $customerId = $customerId ?: $this->currentCustomerId();
        $contact = $this->contactFor($customerId);
        $now = Carbon::now();

        $cart = AbandonedCart::query()->firstOrCreate(
            ['cart_key' => $cartKey],
            [
                'customer_id' => $customerId,
                'email' => $contact['email'] ?? null,
                'phone' => $contact['phone'] ?? null,
                'item_count' => $itemCount,
                'subtotal' => $subtotal,
                'status' => AbandonedCart::STATUS_OPEN,
                'recovery_token' => Str::random(40),
                'first_seen_at' => $now,
                'last_activity_at' => $now,
            ]
        );

        if ($cart->wasRecentlyCreated) {
            return $cart;
        }

        $updates = [
            'item_count' => $itemCount,
            'subtotal' => $subtotal,
            'last_activity_at' => $now,
        ];

        if ($customerId !== null) {
            $updates['customer_id'] = $customerId;
        }

        if (!empty($contact['email'])) {
            $updates['email'] = $contact['email'];
        }

        if (!empty($contact['phone'])) {
            $updates['phone'] = $contact['phone'];
        }

        if ($cart->status === AbandonedCart::STATUS_CONVERTED) {
            $updates['status'] = AbandonedCart::STATUS_OPEN;
            $updates['order_id'] = null;
            $updates['converted_at'] = null;
            $updates['first_seen_at'] = $now;
        }

        $cart->fill($updates)->save();

        return $cart;
    }

    /**
     * The cart produced an order. Only a cart that has not already been
     * converted is touched, so replaying an old order cannot rewrite a newer
     * lifecycle.
     */
    public function markConverted(string $cartKey, int $orderId, ?Carbon $at = null): int
    {
        $at = $at ?: Carbon::now();

        return AbandonedCart::query()
            ->where('cart_key', $cartKey)
            ->where('status', '!=', AbandonedCart::STATUS_CONVERTED)
            ->update([
                'status' => AbandonedCart::STATUS_CONVERTED,
                'order_id' => $orderId,
                'converted_at' => $at,
                'updated_at' => Carbon::now(),
            ]);
    }

    /**
     * Flag every open cart whose last activity is older than the window.
     *
     * @return int number of rows flagged
     */
    public function markAbandoned(int $windowHours, ?Carbon $now = null): int
    {
        $now = $now ?: Carbon::now();
        $cutoff = $now->copy()->subHours(max(1, $windowHours));

        return AbandonedCart::query()
            ->where('status', AbandonedCart::STATUS_OPEN)
            ->whereNotNull('last_activity_at')
            ->where('last_activity_at', '<=', $cutoff)
            ->update([
                'status' => AbandonedCart::STATUS_ABANDONED,
                'abandoned_at' => $now,
                'updated_at' => $now,
            ]);
    }

    public function markRecovered(AbandonedCart $cart, ?Carbon $at = null): AbandonedCart
    {
        $cart->status = AbandonedCart::STATUS_RECOVERED;
        $cart->recovered_at = $at ?: Carbon::now();
        $cart->save();

        return $cart;
    }

    /**
     * The configurable window, in hours. One hour is the floor: a window of 0
     * would flag a cart the customer is still filling.
     */
    public function windowHours(): int
    {
        return max(1, (int) config('reports.abandoned_cart_window_hours', 24));
    }

    public function currentCartKey(): ?string
    {
        if (!app()->bound('session')) {
            return null;
        }

        try {
            $id = session()->getId();
        } catch (\Throwable $e) {
            return null;
        }

        return is_string($id) && $id !== '' ? $id : null;
    }

    private function currentCustomerId(): ?int
    {
        try {
            $id = Auth::guard('customer')->id();
        } catch (\Throwable $e) {
            return null;
        }

        return $id ? (int) $id : null;
    }

    /**
     * @return array{email?: string|null, phone?: string|null}
     */
    private function contactFor(?int $customerId): array
    {
        if (!$customerId) {
            return [];
        }

        $row = DB::table('customers')
            ->select('email', 'phone')
            ->where('id', $customerId)
            ->first();

        return $row ? ['email' => $row->email, 'phone' => $row->phone] : [];
    }
}
