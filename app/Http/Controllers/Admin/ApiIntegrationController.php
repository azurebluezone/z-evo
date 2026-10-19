<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\PaymentGateway;
use App\Models\SmsGateway;
use App\Models\Courierapi;
use Toastr;
use File;
use Str;
use Image;
use DB;

class ApiIntegrationController extends Controller
{
    
    private function joinUrl(string $base, string $path): string
    {
        $base = rtrim($base, '/');
        $path = '/' . ltrim($path, '/');
        return $base . $path;
    }

    private function pathaoUrl(string $base, string $path): string
    {
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'aladdin/')) {
            return $this->joinUrl($base, $path);
        }
        return $this->joinUrl($base, 'aladdin/' . $path);
    }

    public function pay_manage ()
    {
        $bkash = PaymentGateway::firstOrCreate(
            ['type' => 'bkash'],
            [
                'username' => '',
                'app_key' => '',
                'app_secret' => '',
                'base_url' => '',
                'password' => '',
                'status' => 0,
            ]
        );

        $shurjopay = PaymentGateway::firstOrCreate(
            ['type' => 'shurjopay'],
            [
                'username' => '',
                'prefix' => '',
                'success_url' => '',
                'return_url' => '',
                'base_url' => '',
                'password' => '',
                'status' => 0,
            ]
        );
        return view('backEnd.apiintegration.pay_manage',compact('bkash','shurjopay'));
    }
    
    public function pay_update(Request $request)
    {
      
        $update_data = PaymentGateway::find($request->id);
        if (!$update_data) {
            Toastr::error('Error', 'Payment gateway settings not found');
            return redirect()->back();
        }
        $input = $request->all();
        $input['status'] = $request->status?1:0;
        $update_data->update($input);
        
        Toastr::success('Success','Data update successfully');
        return redirect()->back();
    }
    
    public function sms_manage ()
    {
        /*
         * `SmsGateway::first()` is null on a fresh install, and the template
         * reads $sms->id on its very first line - so this page answered 500
         * until a row happened to exist. sms_gateways is empty, so it always
         * did. A fresh, status-ON model renders the same form empty instead,
         * and the first submit creates the row.
         */
        $sms = SmsGateway::first() ?? new SmsGateway(['status' => 1]);

        return view('backEnd.apiintegration.sms_manage', compact('sms'));
    }
    
    public function sms_update(Request $request)
    {
        /*
         * Three separate defects met here:
         *   1. find($request->id) is null on a fresh install and ->update() then
         *      threw, so the FIRST gateway could never be saved - which is why
         *      sms_gateways stayed empty and every OTP / order SMS was skipped.
         *   2. $request->all() was handed straight to update(), carrying
         *      `_token` (and anything else posted) into the SQL.
         *   3. `password_g` was written to a column that did not exist until
         *      migration 2026_10_18_000009.
         * Columns are now listed explicitly and the row is created on first save.
         */
        $request->validate([
            'url'      => 'required',
            'api_key'  => 'required',
            'serderid' => 'required',
        ]);

        $sms = $request->filled('id') ? SmsGateway::find($request->id) : null;

        if (!$sms) {
            $sms = new SmsGateway();
        }

        $sms->url         = $request->input('url');
        $sms->api_key     = $request->input('api_key');
        $sms->serderid    = $request->input('serderid');
        $sms->status      = $request->input('status') ? 1 : 0;
        $sms->order       = $request->input('order') ? 1 : 0;
        $sms->forget_pass = $request->input('forget_pass') ? 1 : 0;
        $sms->password_g  = $request->input('password_g') ? 1 : 0;
        $sms->save();

        Toastr::success('Success','Data update successfully');
        return redirect()->back();
    }
    
    public function courier_manage ()
    {
        $steadfast = Courierapi::where('type','=','steadfast')->first();
        $pathao = Courierapi::where('type','=','pathao')->first();
        return view('backEnd.apiintegration.courier_manage',compact('steadfast','pathao'));
    }
    
    public function courier_update (Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer'],
            'url' => ['required', 'string', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'secret_key' => ['nullable', 'string', 'max:255'],
            'client_id' => ['nullable', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'token' => ['nullable', 'string'],
        ]);

        $update_data = Courierapi::find($request->id);
        if (!$update_data) {
            Toastr::error('Error', 'Courier settings not found');
            return redirect()->back();
        }

        $input = $request->only([
            'url',
            'api_key',
            'secret_key',
            'client_id',
            'client_secret',
            'username',
            'password',
            'token',
        ]);

        $input['status'] = $request->status ? 1 : 0;

        $update_data->update($input);

        if ($update_data->type === 'pathao' && $update_data->status == 1) {
            $hasCredentials = $update_data->client_id && $update_data->client_secret && $update_data->username && $update_data->password;
            if ($hasCredentials && $update_data->url) {
                try {
                    $response = Http::withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ])->post($this->pathaoUrl($update_data->url, 'api/v1/issue-token'), [
                        'client_id' => $update_data->client_id,
                        'client_secret' => $update_data->client_secret,
                        'grant_type' => 'password',
                        'username' => $update_data->username,
                        'password' => $update_data->password,
                    ]);

                    if ($response->successful()) {
                        $token = data_get($response->json(), 'access_token');
                        if ($token) {
                            $update_data->update(['token' => $token]);
                        }
                    }
                } catch (\Throwable $e) {
                    // keep silent and allow manual token input
                }
            }
        }
        
        Toastr::success('Success','Data update successfully');
        return redirect()->back();
    }

    /**
     * Test SteadFast API connection
     */
    public function testSteadfastConnection(Request $request)
    {
        $request->validate([
            'api_key' => 'required|string',
            'secret_key' => 'required|string',
            'url' => 'required|url',
        ]);

        try {
            $url = str_replace('/create_order', '/get_balance', $request->url);
            
            $response = Http::withHeaders([
                'Api-Key' => $request->api_key,
                'Secret-Key' => $request->secret_key,
                'Accept' => 'application/json',
            ])->get($url);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'message' => 'Connection successful',
                    'balance' => $data['current_balance'] ?? null,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $response->body(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Test Pathao API connection
     */
    public function testPathaoConnection(Request $request)
    {
        $request->validate([
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
            'username' => 'required|string',
            'password' => 'required|string',
            'url' => 'required|url',
        ]);

        try {
            $baseUrl = rtrim($request->url, '/');
            if (!str_contains($baseUrl, '/aladdin')) {
                $baseUrl .= '/aladdin';
            }

            $response = Http::post($baseUrl . '/api/v1/issue-token', [
                'client_id' => $request->client_id,
                'client_secret' => $request->client_secret,
                'grant_type' => 'password',
                'username' => $request->username,
                'password' => $request->password,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['access_token'] ?? null;
                
                return response()->json([
                    'success' => true,
                    'message' => 'Connection successful',
                    'token_received' => $token ? true : false,
                    'token' => $token,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $response->body(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Sync orders with courier fraud check
     */
    public function syncOrdersWithCourier(Request $request)
    {
        $request->validate([
            'limit' => 'integer|min:1|max:100',
        ]);

        $limit = $request->input('limit', 50);
        
        // Get orders that don't have fraud check data
        $orders = \App\Models\Order::where(function($q) {
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
        $fraudService = app(\App\Services\CourierFraudService::class);

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