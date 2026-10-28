<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courierapi;
use App\Models\Order;
use App\Services\CourierFraudService;
use Illuminate\Http\Request;

class CourierSettingsController extends Controller
{
    /**
     * The one courier API screen.
     *
     * Both supported providers are always rendered, even before their first
     * save: firstOrNew() gives the form empty fields instead of hiding the
     * courier behind a missing row. Saving is handled by
     * CourierController::update() - one validator and one connection tester
     * for both this page and the API.
     */
    public function index()
    {
        $couriers = collect(['steadfast', 'pathao'])
            ->map(fn (string $type) => Courierapi::query()->firstOrNew(['type' => $type]));

        return view('backEnd.courier.settings', compact('couriers'));
    }

    /**
     * Sync existing orders with courier fraud check
     */
    public function syncOrdersWithCourier(Request $request)
    {
        $request->validate([
            'limit' => 'integer|min:1|max:100',
        ]);

        $limit = $request->input('limit', 50);
        
        // Get orders that don't have fraud check data
        // The two "not scored yet" columns belong to ONE grouped condition.
        // Written as whereNull()->orWhereNull()->whereHas(), the OR escaped the
        // phone filter and every unscored order without a shipping row was
        // selected too.
        $orders = Order::query()
            ->where(function ($q) {
                $q->whereNull('risk_label')
                  ->orWhereNull('risk_score_percent');
            })
            ->whereHas('shipping', function ($q) {
                $q->whereNotNull('phone');
            })
            ->with('shipping')
            ->limit($limit)
            ->get();

        $results = [];
        $fraudService = app(CourierFraudService::class);

        foreach ($orders as $order) {
            $phone = $order->shipping->phone ?? null;
            if (!$phone) {
                continue;
            }

            // Check courier history first
            $check = $fraudService->checkPhoneFromCourierHistory($phone);
            
            if (!$check || $check->risk_label === 'unknown') {
                // If no history, check real courier APIs
                $check = $fraudService->checkPhoneFromCourierAPIs($phone);
            }

            if ($check) {
                $fraudService->attachToOrder($order, $check, 'sync');
                $results[] = [
                    'order_id' => $order->id,
                    'invoice_id' => $order->invoice_id,
                    'phone' => $phone,
                    'risk_label' => $check->risk_label,
                    'success_rate' => $check->success_rate_percent,
                ];
            }
        }

        return response()->json([
            'status' => 'completed',
            'processed' => count($results),
            'results' => $results,
        ]);
    }
}
