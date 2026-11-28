<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use App\Services\Analytics\CartActivityRecorder;
use Illuminate\Http\Request;

/**
 * Phase 3 item 5 — the abandoned cart report.
 *
 * Read-only except for one action: marking a cart recovered. That action writes
 * recovered_at, because the recovery state must be a stored fact; the report
 * never infers it from logs or from the absence of a later order.
 */
class AbandonedCartController extends Controller
{
    public function index(Request $request, CartActivityRecorder $recorder)
    {
        $status = (string) $request->query('status', '');
        $allowed = AbandonedCart::STATUSES;

        $query = AbandonedCart::query()->orderByDesc('last_activity_at');

        if (in_array($status, $allowed, true)) {
            $query->where('status', $status);
        }

        $carts = $query->limit(200)->get();

        $counts = AbandonedCart::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $abandonedValue = (float) AbandonedCart::query()
            ->where('status', AbandonedCart::STATUS_ABANDONED)
            ->sum('subtotal');

        return view('backEnd.reports.abandoned-carts', [
            'carts' => $carts,
            'status' => $status,
            'statuses' => $allowed,
            'counts' => $counts,
            'abandonedValue' => round($abandonedValue, 2),
            'windowHours' => $recorder->windowHours(),
        ]);
    }

    public function markRecovered(int $id, CartActivityRecorder $recorder)
    {
        $cart = AbandonedCart::query()->findOrFail($id);

        $recorder->markRecovered($cart);

        return redirect()
            ->back()
            ->with('success', 'Cart '.$cart->cart_key.' marked recovered.');
    }
}
