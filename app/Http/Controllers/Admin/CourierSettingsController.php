<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PathaoClient;
use App\Models\Courierapi;
use App\Models\CourierShipment;
use App\Models\Order;
use App\Services\CourierFraudService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CourierSettingsController extends Controller
{
    /**
     * Show courier API settings page
     */
    public function index()
    {
        $steadfast = Courierapi::where('type', 'steadfast')->first();
        $pathao = Courierapi::where('type', 'pathao')->first();
        
        return view('backEnd.courier.settings', compact('steadfast', 'pathao'));
    }

    /**
     * Update SteadFast API settings
     */
    public function updateSteadfast(Request $request)
    {
        $request->validate([
            'api_key' => 'required|string|max:155',
            'secret_key' => 'required|string|max:155',
            'url' => 'required|url|max:99',
            'status' => 'boolean',
        ]);

        $steadfast = Courierapi::where('type', 'steadfast')->first();
        
        if (!$steadfast) {
            $steadfast = new Courierapi(['type' => 'steadfast']);
        }

        $steadfast->api_key = $request->api_key;
        $steadfast->secret_key = $request->secret_key;
        $steadfast->url = $request->url;
        $steadfast->status = $request->boolean('status', false) ? 1 : 0;
        $steadfast->save();

        // Test connection
        $testResult = $this->testSteadfastConnection($steadfast);

        return response()->json([
            'status' => 'success',
            'message' => 'SteadFast settings updated',
            'test_result' => $testResult,
        ]);
    }

    /**
     * Update Pathao API settings
     */
    public function updatePathao(Request $request)
    {
        $request->validate([
            'client_id' => 'required|string|max:155',
            'client_secret' => 'required|string|max:155',
            'username' => 'required|email|max:155',
            'password' => 'required|string|max:155',
            'url' => 'required|url|max:99',
            'status' => 'boolean',
        ]);

        $pathao = Courierapi::where('type', 'pathao')->first();
        
        if (!$pathao) {
            $pathao = new Courierapi(['type' => 'pathao']);
        }

        $pathao->client_id = $request->client_id;
        $pathao->client_secret = $request->client_secret;
        $pathao->username = $request->username;
        $pathao->password = $request->password;
        $pathao->url = $request->url;
        $pathao->status = $request->boolean('status', false) ? 1 : 0;
        $pathao->save();

        // Test connection and get token
        $testResult = $this->testPathaoConnection($pathao);

        return response()->json([
            'status' => 'success',
            'message' => 'Pathao settings updated',
            'test_result' => $testResult,
        ]);
    }

    /**
     * Test SteadFast API connection
     */
    private function testSteadfastConnection($config): array
    {
        try {
            $response = Http::withHeaders([
                'Api-Key' => $config->api_key,
                'Secret-Key' => $config->secret_key,
                'Accept' => 'application/json',
            ])->get(str_replace('/create_order', '/get_balance', $config->url));

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'balance' => $data['current_balance'] ?? null,
                ];
            }

            return [
                'success' => false,
                'message' => 'Connection failed: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Test Pathao API connection
     */
    private function testPathaoConnection($config): array
    {
        /*
         * Routed through PathaoClient so the connection test and the live
         * city/zone lookups build the SAME url and share one token store.
         *
         * The previous inline version had two defects:
         *
         *  1. It always appended '/aladdin' unless the base already contained
         *     it. That is right for production (api-hermes.pathao.com) but wrong
         *     for the sandbox host (courier-api-sandbox.pathao.com), which is
         *     deliberately exempt from the prefix. A sandbox account could
         *     therefore never pass this test, and the failure looked like bad
         *     credentials.
         *
         *  2. It saved the token but never recorded its lifetime, so the token
         *     went stale silently and later lookups failed with 401 while this
         *     screen still said "Connection successful".
         */
        $client = new PathaoClient($config);

        $token = $client->refreshToken();

        if ($token === null) {
            return [
                'success' => false,
                'message' => $client->lastError ?? 'Connection failed.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Connection successful',
            'token_received' => true,
        ];
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
        $orders = Order::whereNull('risk_label')
            ->orWhereNull('risk_score_percent')
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

    /**
     * Check single phone number via courier APIs
     */
    public function checkPhoneViaCourier(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        $phone = $request->phone;
        $fraudService = app(CourierFraudService::class);

        // Try courier APIs
        $result = $fraudService->checkPhoneFromCourierAPIs($phone);

        return response()->json([
            'status' => 'success',
            'phone' => $phone,
            'result' => $result ? [
                'risk_label' => $result->risk_label,
                'success_rate_percent' => $result->success_rate_percent,
                'raw_payload' => $result->raw_payload,
            ] : null,
        ]);
    }
}
