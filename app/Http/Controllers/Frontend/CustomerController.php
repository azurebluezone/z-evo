<?php

namespace App\Http\Controllers\Frontend;

use shurjopayv2\ShurjopayLaravelPackage8\Http\Controllers\ShurjopayController;
use App\Services\SmsGatewayService;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use Intervention\Image\Facades\Image;
use App\Models\Customer;
use App\Models\District;
use App\Models\Order;
use App\Models\ShippingCharge;
use App\Models\OrderDetails;
use App\Models\Payment;
use App\Models\Shipping;
use App\Models\Review;
use App\Models\Product;
use App\Models\PaymentGateway;
use App\Models\SmsGateway;
use App\Models\GeneralSetting;
use App\Models\EventLog;
use App\Services\CourierFraudService;
use App\Services\GuestCheckoutAccountService;
use App\Services\FacebookTrackingService;
use App\Services\TikTokTrackingService;
use App\Models\CourierShipment;
use App\Models\User;
use App\Notifications\GenericDbNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Session;
use Hash;
use Auth;
use Cart;
use Mail;
use Str;
use DB;

class CustomerController extends Controller
{
    function __construct()
    {
        $this->middleware('customer', ['except' => ['register', 'store', 'verify', 'resendotp', 'account_verify', 'login', 'signin', 'logout', 'checkout', 'forgot_password', 'forgot_verify', 'forgot_reset', 'forgot_store', 'forgot_resend', 'order_save', 'order_success', 'order_track', 'order_track_result']]);
    }

    public function review(Request $request)
    {
        $this->validate($request, [
            'ratting' => 'required',
            'review' => 'required',
        ]);

        // data save
        $review              =   new Review();
        $review->name        =   Auth::guard('customer')->user()->name ? Auth::guard('customer')->user()->name : 'N / A';
        $review->email       =   Auth::guard('customer')->user()->email ? Auth::guard('customer')->user()->email : 'N / A';
        $review->product_id  =   $request->product_id;
        $review->review      =   $request->review;
        $review->ratting     =   $request->ratting;
        $review->customer_id =   Auth::guard('customer')->user()->id;
        $review->status      =   'pending';
        $review->save();

        Toastr::success('Thanks, Your review send successfully', 'Success!');
        return redirect()->back();
    }

    public function login()
    {
        return view('frontend.pages.auth.login');
    }

    public function signin(Request $request)
    {
        $auth_check = Customer::where('phone', $request->phone)->first();
        if ($auth_check) {
            if (Auth::guard('customer')->attempt(['phone' => $request->phone, 'password' => $request->password])) {
                Toastr::success('You are login successfully', 'success!');
                if (Cart::instance('shopping')->count() > 0) {
                    return redirect()->route('customer.checkout');
                }
                return redirect()->intended('customer/account');
            }
            Toastr::error('message', 'Opps! your phone or password wrong');
            return redirect()->back();
        } else {
            Toastr::error('message', 'Sorry! You have no account');
            return redirect()->back();
        }
    }

    public function register()
    {
        return view('frontend.pages.auth.register');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name'    => 'required',
            'phone'    => 'required|unique:customers',
            'password' => 'required|min:6'
        ]);

        $last_id = Customer::orderBy('id', 'desc')->first();
        $last_id = $last_id ? $last_id->id + 1 : 1;
        $store              = new Customer();
        $store->name        = $request->name;
        $store->slug        = strtolower(Str::slug($request->name . '-' . $last_id));
        $store->phone       = $request->phone;
        $store->email       = $request->email;
        $store->password    = bcrypt($request->password);
        $store->verify      = 1;
        $store->status      = 'active';
        $store->save();

        // Notify admin about new customer registration
        try {
            Notification::send(User::all(), new GenericDbNotification(
                title: 'New Customer Registered',
                body: "New customer registered: {$store->name} ({$store->phone})",
                meta: ['type' => 'customer_register', 'customer_id' => $store->id]
            ));
        } catch (\Throwable $e) {
            // no-op
        }

        Toastr::success('Success', 'Account Create Successfully');
        return redirect()->route('customer.login');
    }
    public function verify()
    {
        return view('frontend.layouts.customer.verify');
    }
    public function resendotp(Request $request)
    {
        $customer_info = Customer::where('phone', session::get('verify_phone'))->first();
        $customer_info->verify = rand(1111, 9999);
        $customer_info->save();
        $site_setting = GeneralSetting::where('status', 1)->first();
        // Sent through SmsGatewayService, which READS the gateway's answer.
        // The previous inline curl discarded $response, so every failure was
        // reported to the customer as a success.
        $sms = app(SmsGatewayService::class)->send(
            (string) $customer_info->phone,
            "Dear $customer_info->name!\r\nYour account verify OTP is $customer_info->verify \r\nThank you for using $site_setting->name",
            null
        );
        if ($sms['sent']) {
            Toastr::success('Resend code send successfully');
        } else {
            // Never claim success when the gateway refused or was unreachable:
            // that silence is what made a missing SMS impossible to diagnose.
            Toastr::error('Code could not be sent: ' . $sms['reason']);
            Log::warning('OTP resend failed', $sms);
        }

        return redirect()->back();
    }
    public function account_verify(Request $request)
    {
        $this->validate($request, [
            'otp' => 'required',
        ]);
        $customer_info = Customer::where('phone', session::get('verify_phone'))->first();
        if ($customer_info->verify != $request->otp) {
            Toastr::error('Success', 'Your OTP not match');
            return redirect()->back();
        }

        $customer_info->verify = 1;
        $customer_info->status = 'active';
        $customer_info->save();
        Auth::guard('customer')->loginUsingId($customer_info->id);
        return redirect()->route('customer.account');
    }
    public function forgot_password()
    {
        return view('frontend.layouts.customer.forgot_password');
    }

    public function forgot_verify(Request $request)
    {
        $customer_info = Customer::where('phone', $request->phone)->first();
        if (!$customer_info) {
            Toastr::error('Your phone number not found');
            return back();
        }
        $customer_info->forgot = rand(1111, 9999);
        $customer_info->save();
        $site_setting = GeneralSetting::where('status', 1)->first();
        // Sent through SmsGatewayService, which READS the gateway's answer.
        // The previous inline curl discarded $response, so every failure was
        // reported to the customer as a success.
        $sms = app(SmsGatewayService::class)->send(
            (string) $customer_info->phone,
            "Dear $customer_info->name!\r\nYour forgot password verify OTP is $customer_info->forgot \r\nThank you for using $site_setting->name",
            'forget_pass'
        );

        session::put('verify_phone', $request->phone);
        Toastr::success('Your account register successfully');
        return redirect()->route('customer.forgot.reset');
    }

    public function forgot_resend(Request $request)
    {
        $customer_info = Customer::where('phone', session::get('verify_phone'))->first();
        $customer_info->forgot = rand(1111, 9999);
        $customer_info->save();
        $site_setting = GeneralSetting::where('status', 1)->first();
        // Sent through SmsGatewayService, which READS the gateway's answer.
        // The previous inline curl discarded $response, so every failure was
        // reported to the customer as a success.
        $sms = app(SmsGatewayService::class)->send(
            (string) $customer_info->phone,
            "Dear $customer_info->name!\r\nYour forgot password verify OTP is $customer_info->forgot \r\nThank you for using $site_setting->name",
            null
        );

        if ($sms['sent']) {
            Toastr::success('Resend code send successfully');
        } else {
            // Never claim success when the gateway refused or was unreachable:
            // that silence is what made a missing SMS impossible to diagnose.
            Toastr::error('Code could not be sent: ' . $sms['reason']);
            Log::warning('OTP resend failed', $sms);
        }

        return redirect()->back();
    }
    public function forgot_reset()
    {
        if (!Session::get('verify_phone')) {
            Toastr::error('Something wrong please try again');
            return redirect()->route('customer.forgot.password');
        };
        return view('frontend.layouts.customer.forgot_reset');
    }
    public function forgot_store(Request $request)
    {

        $customer_info = Customer::where('phone', session::get('verify_phone'))->first();

        if ($customer_info->forgot != $request->otp) {
            Toastr::error('Success', 'Your OTP not match');
            return redirect()->back();
        }

        $customer_info->forgot = 1;
        $customer_info->password = bcrypt($request->password);
        $customer_info->save();

        // Setting a real password through the OTP flow also retires any
        // temporary password left over from a guest order, for the same reason
        // password_update() clears it.
        app(GuestCheckoutAccountService::class)->clearTemporaryPassword();

        if (Auth::guard('customer')->attempt(['phone' => $customer_info->phone, 'password' => $request->password])) {
            Session::forget('verify_phone');
            Toastr::success('You are login successfully', 'success!');
            return redirect()->intended('customer/account');
        }
    }
    public function account()
    {
        return view('frontend.layouts.customer.account');
    }
    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();

        // The temporary password is session scoped, so logging out has to drop
        // it explicitly - otherwise a shared machine would still surface the
        // plaintext in the session payload for the rest of its lifetime.
        app(GuestCheckoutAccountService::class)->clearTemporaryPassword();

        Toastr::success('You are logout successfully', 'success!');
        return redirect()->route('customer.login');
    }
    public function checkout()
    {
        $shippingcharge = ShippingCharge::where('status', 1)->get();
        $select_charge = ShippingCharge::where('status', 1)->first();
        $bkash_gateway = PaymentGateway::where(['status' => 1, 'type' => 'bkash'])->first();
        $shurjopay_gateway = PaymentGateway::where(['status' => 1, 'type' => 'shurjopay'])->first();
        if ($select_charge) {
            Session::put('shipping', (float) $select_charge->amount);
            Session::put('shipping_area_id', (int) $select_charge->id);
        }

        $cartItems = Cart::instance('shopping')->content();
        if ($cartItems->count() > 0) {
            $productIds = [];
            $numItems = 0;
            $total = 0;
            foreach ($cartItems as $cart) {
                $productIds[] = $cart->id;
                $lineQty = (int) ($cart->qty ?? 0);
                $numItems += $lineQty;
                $total += ((float) ($cart->price ?? 0) * $lineQty);
            }

            $userData = [
                'email' => Auth::guard('customer')->user()->email ?? null,
                'phone' => Auth::guard('customer')->user()->phone ?? null,
                'first_name' => Auth::guard('customer')->user()->name ?? null,
            ];
            $checkoutData = [
                'total' => $total,
                'product_ids' => $productIds,
                'num_items' => $numItems,
            ];

            try {
                $facebook = app(FacebookTrackingService::class);
                if ($facebook->isEnabled()) {
                    $facebook->sendInitiateCheckoutEvent($checkoutData, $userData);
                }
            } catch (\Throwable $e) {
                // no-op
            }

            try {
                $tiktok = app(TikTokTrackingService::class);
                if ($tiktok->isEnabled()) {
                    $tiktok->sendInitiateCheckoutEvent($checkoutData, $userData);
                }
            } catch (\Throwable $e) {
                // no-op
            }
        }

        //  return redirect()->back();
        return view('frontend.layouts.customer.checkout', compact('shippingcharge', 'bkash_gateway', 'shurjopay_gateway'));
    }
    public function order_save(Request $request)
    {
        // dd($request);
        $this->validate($request, [
            'name' => 'required',
            'phone' => 'required',
            'address' => 'required',
            'area' => 'required',
        ]);
        if (Cart::instance('shopping')->count() <= 0) {
            Toastr::error('Your shopping empty', 'Failed!');
            return redirect()->back();
        }

        $subtotal = Cart::instance('shopping')->subtotal();
        $subtotal = str_replace(',', '', $subtotal);
        $subtotal = str_replace('.00', '', $subtotal);
        $discount = Session::get('discount');

        $shipping_area  = ShippingCharge::where('id', $request->area)->first();
        if (!$shipping_area) {
            Toastr::error('Invalid shipping area selected', 'Failed!');
            return redirect()->back();
        }
        $shippingfee = (float) $shipping_area->amount;
        Session::put('shipping', $shippingfee);
        Session::put('shipping_area_id', (int) $shipping_area->id);
        // Resolve the account behind this order.
        //
        // This block used to be inline and was fatal for every guest order:
        // the new Customer was saved without an email while customers.email was
        // NOT NULL ("Field 'email' doesn't have a default value", HTTP 500), and
        // the password was rand(111111, 999999) with the plaintext then
        // discarded so nobody could ever see it. Both are handled by
        // GuestCheckoutAccountService; see that class for the three outcomes
        // and for the recorded risk of matching on phone.
        try {
            $account = app(GuestCheckoutAccountService::class)->resolveForOrder(
                (string) $request->name,
                (string) $request->phone,
                (string) $request->address,
                (int) $request->area
            );
            $customer_id = $account['customer_id'];
        } catch (\Throwable $e) {
            Log::error('Guest checkout account resolution failed', [
                'phone' => $request->phone,
                'error' => $e->getMessage(),
            ]);

            Toastr::error('We could not start your account. Please try again.', 'Failed!');
            return redirect()->back();
        }

        // order data save
        $order                   = new Order();
        $order->invoice_id       = rand(11111, 99999);
        $order->amount           = ($subtotal + $shippingfee) - $discount;
        $order->discount         = $discount ? $discount : 0;
        $order->shipping_charge  = $shippingfee;
        $order->customer_id      =  $customer_id;
        $order->order_status     = 1;
        // NOTE: the line that used to be here was
        //     $order->note = $request->note;
        // There is no `note` column on `orders` - the admin-facing column is
        // `admin_note`, written by Admin\OrderController - so the INSERT threw
        // "Unknown column 'note' in 'field list'" and EVERY order, guest or
        // signed in, returned HTTP 500. No checkout form ever sent a `note`
        // field either, so the value was always null. Dropped rather than
        // remapped: `admin_note` is admin-owned and must not be writable from
        // the storefront.
        $order->save();

        try {
            app(CourierFraudService::class)->checkAndAttachToOrder($order, (string) $request->phone, null, 'checkout', 720);
        } catch (\Throwable $e) {
            // no-op
        }

        // shipping data save
        $shipping              =   new Shipping();
        $shipping->order_id    =   $order->id;
        $shipping->customer_id =   $customer_id;
        $shipping->name        =   $request->name;
        $shipping->phone       =   $request->phone;
        $shipping->address     =   $request->address;
        $shipping->area        =   $shipping_area->name;
        $shipping->save();

        // payment data save
        $payment                 = new Payment();
        $payment->order_id       = $order->id;
        $payment->customer_id    = $customer_id;
        $payment->payment_method = $request->payment_method;
        $payment->amount         = $order->amount;
        $payment->payment_status = 'pending';
        $payment->save();

        // order details data save + reserve inventory (180 min) until admin approve
        $cartItems = Cart::instance('shopping')->content();
        DB::transaction(function () use ($cartItems, $order) {
            foreach ($cartItems as $cart) {
                $order_details                  =   new OrderDetails();
                $order_details->order_id        =   $order->id;
                $order_details->product_id      =   $cart->id;
                $order_details->product_name    =   $cart->name;
                $order_details->purchase_price  =   $cart->options->purchase_price;
                $order_details->product_color   =   $cart->options->product_color;
                $sizeValue = $cart->options->product_size;
                if (empty($sizeValue) && !empty($cart->options->variant_id)) {
                    $sizeValue = 'variant_id:' . (int) $cart->options->variant_id;
                }
                $order_details->product_size    =   $sizeValue;
                $order_details->sale_price      =   $cart->price;
                $order_details->qty             =   $cart->qty;

                // Patch-3: persist authoritative variant_id
                $variantId = null;
                if (!empty($cart->options->variant_id)) {
                    $candidateId = (int) $cart->options->variant_id;
                    $exists = DB::table('product_variants')
                        ->where('id', $candidateId)
                        ->where('product_id', (int) $cart->id)
                        ->exists();
                    if ($exists) {
                        $variantId = $candidateId;
                    }
                }
                if (!$variantId && !empty($sizeValue) && preg_match('/\bvariant_id\s*:\s*(\d+)\b/i', (string) $sizeValue, $m)) {
                    $candidateId = (int) $m[1];
                    $exists = DB::table('product_variants')
                        ->where('id', $candidateId)
                        ->where('product_id', (int) $cart->id)
                        ->exists();
                    if ($exists) {
                        $variantId = $candidateId;
                    }
                }
                $order_details->variant_id = $variantId ? (int) $variantId : null;

                $order_details->save();

                $qty = (int) ($cart->qty ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $product = Product::select('id', 'stock', 'product_code')->find((int) $cart->id);
                if (!$product) {
                    continue;
                }

                $variantId = null;
                if (!empty($cart->options->variant_id)) {
                    $candidateId = (int) $cart->options->variant_id;
                    $exists = DB::table('product_variants')
                        ->where('id', $candidateId)
                        ->where('product_id', $product->id)
                        ->exists();
                    if ($exists) {
                        $variantId = $candidateId;
                    }
                }

                if (!$variantId && !empty($sizeValue) && preg_match('/\bvariant_id\s*:\s*(\d+)\b/i', (string) $sizeValue, $m)) {
                    $candidateId = (int) $m[1];
                    $exists = DB::table('product_variants')
                        ->where('id', $candidateId)
                        ->where('product_id', $product->id)
                        ->exists();
                    if ($exists) {
                        $variantId = $candidateId;
                    }
                }

                if (!$variantId) {
                    $defaultSku = ($product->product_code ? $product->product_code : ('P' . $product->id)) . '-DEFAULT';
                    $variantId = DB::table('product_variants')
                        ->where('sku', $defaultSku)
                        ->value('id');
                    if (!$variantId) {
                        $variantId = DB::table('product_variants')->insertGetId([
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
                }

                DB::table('inventory_reservations')->updateOrInsert(
                    [
                        'order_id' => (int) $order->id,
                        'variant_id' => (int) $variantId,
                    ],
                    [
                        'qty' => (int) $qty,
                        'expires_at' => now()->addMinutes(180),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        });

        $userData = [
            'email' => $request->email ?? null,
            'phone' => $request->phone ?? null,
            'first_name' => $request->name ?? null
        ];

        $productIds = [];
        $numItems = 0;
        foreach ($cartItems as $cart) {
            $productIds[] = $cart->id;
            $numItems += $cart->qty;
        }

        $orderData = [
            'order_id' => $order->id,
            'amount' => $order->amount,
            'product_ids' => $productIds,
            'num_items' => $numItems
        ];

        // Facebook Server-Side Tracking - Purchase Event
        if (app(FacebookTrackingService::class)->isEnabled()) {
            app(FacebookTrackingService::class)->sendPurchaseEvent($orderData, $userData);
        }

        try {
            $tiktok = app(TikTokTrackingService::class);
            if ($tiktok->isEnabled()) {
                $tiktok->sendPurchaseEvent($orderData, $userData);
            }
        } catch (\Throwable $e) {
            // no-op
        }

        // 🔥 PATCH: Log Purchase event to local event_logs table
        try {
            $visitorId = $request->cookie('visitor_id');
            if ($visitorId) {
                EventLog::create([
                    'visitor_id' => $visitorId,
                    'event_name' => 'Purchase',
                    'url' => $request->url(),
                    'event_time' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            // no-op
        }

        Cart::instance('shopping')->destroy();

        // Notify admin about new order
        try {
            $customerName = $request->name ?? 'Guest';
            Notification::send(User::all(), new GenericDbNotification(
                title: 'New Order Placed',
                body: "New order #{$order->invoice_id} placed by {$customerName} (Tk {$order->amount})",
                meta: ['type' => 'new_order', 'order_id' => $order->id, 'invoice_id' => $order->invoice_id]
            ));
        } catch (\Throwable $e) {
            // no-op
        }

        Toastr::success('Thanks, Your order place successfully', 'Success!');
        $site_setting = GeneralSetting::where('status', 1)->first();
        // Sent through SmsGatewayService, which READS the gateway's answer.
        // The previous inline curl discarded $response, so every failure was
        // reported to the customer as a success.
        $sms = app(SmsGatewayService::class)->send(
            (string) $request->phone,
            "Dear $request->name!\r\nYour order has been successfully placed. check your customer panel on our website to know more about your order. Thank you for using $site_setting->name",
            'order'
        );

        if ($request->payment_method == 'bkash') {
            return redirect('/bkash/checkout-url/create?order_id=' . $order->id);
        } elseif ($request->payment_method == 'shurjopay') {
            $info = array(
                'currency' => "BDT",
                'amount' => $order->amount,
                'order_id' => uniqid(),
                'discsount_amount' => 0,
                'disc_percent' => 0,
                'client_ip' => $request->ip(),
                'customer_name' =>  $request->name,
                'customer_phone' => $request->phone,
                'email' => "customer@gmail.com",
                'customer_address' => $request->address,
                'customer_city' => $request->area,
                'customer_state' => $request->area,
                'customer_postcode' => "1212",
                'customer_country' => "BD",
                'value1' => $order->id
            );
            $shurjopay_service = new ShurjopayController();
            return $shurjopay_service->checkout($info);
        } else {
            return redirect('customer/order-success/' . $order->id);
        }
    }

    public function orders()
    {
        $orders = Order::where('customer_id', Auth::guard('customer')->user()->id)->with('status')->latest()->get();
        return view('frontend.layouts.customer.orders', compact('orders'));
    }
    public function order_success($id)
    {
        $order = Order::where('id', $id)
            ->with(['orderdetails', 'shipping', 'payment'])
            ->firstOrFail();
        return view('frontend.layouts.customer.order_success', compact('order'));
    }
    public function invoice(Request $request)
    {
        $order = Order::where(['id' => $request->id, 'customer_id' => Auth::guard('customer')->user()->id])->with('orderdetails', 'payment', 'shipping', 'customer')->firstOrFail();
        return view('frontend.layouts.customer.invoice', compact('order'));
    }
    public function order_note(Request $request)
    {
        $order = Order::where(['id' => $request->id, 'customer_id' => Auth::guard('customer')->user()->id])->firstOrFail();
        return view('frontend.layouts.customer.order_note', compact('order'));
    }
    public function profile_edit(Request $request)
    {
        $profile_edit = Customer::where(['id' => Auth::guard('customer')->user()->id])->firstOrFail();
        $districts = District::distinct()->select('district')->get();
        $areas = District::where(['district' => $profile_edit->district])->select('area_name', 'id')->get();
        return view('frontend.layouts.customer.profile_edit', compact('profile_edit', 'districts', 'areas'));
    }
    public function profile_update(Request $request)
    {
        $update_data = Customer::where(['id' => Auth::guard('customer')->user()->id])->firstOrFail();

        $image = $request->file('image');
        if ($image) {
            // image with intervention 
            $name =  time() . '-' . $image->getClientOriginalName();
            $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name);
            $name = strtolower(Str::slug($name));
            $uploadpath = 'public/uploads/customer/';
            $imageUrl = $uploadpath . $name;
            $img = Image::make($image->getRealPath());
            $img->encode('webp', 90);
            $width = 120;
            $height = 120;
            $img->resize($width, $height);
            $img->save($imageUrl);
        } else {
            $imageUrl = $update_data->image;
        }

        $update_data->name        =   $request->name;
        $update_data->phone       =   $request->phone;
        $update_data->email       =   $request->email;
        $update_data->address     =   $request->address;
        $update_data->district    =   $request->district;
        $update_data->area        =   $request->area;
        $update_data->image       =   $imageUrl;
        $update_data->save();

        Toastr::success('Your profile update successfully', 'Success!');
        return redirect()->route('customer.account');
    }

    public function order_track()
    {
        return view('frontend.layouts.customer.order_track');
    }

    public function order_track_result(Request $request)
    {
        $phone = trim((string) $request->get('phone', ''));
        $invoiceId = trim((string) $request->get('invoice_id', ''));

        if ($phone === '' && $invoiceId === '') {
            Toastr::error('Error', 'Please provide phone or invoice number');
            return redirect()->back();
        }

        $ordersQuery = \App\Models\Order::query()
            ->with(['shipping', 'status', 'orderdetails'])
            ->when($invoiceId !== '', fn($q) => $q->where('invoice_id', $invoiceId))
            ->when($phone !== '', function ($q) use ($phone) {
                return $q->whereHas('shipping', fn($sq) => $sq->where('phone', $phone));
            })
            ->latest();

        $orders = $ordersQuery->get();

        if ($orders->count() === 0) {
            Toastr::error('Error', 'Order not found');
            return redirect()->back();
        }

        $orderIds = $orders->pluck('id')->all();
        $shipments = CourierShipment::query()
            ->whereIn('order_id', $orderIds)
            ->orderByDesc('updated_at')
            ->get()
            ->groupBy('order_id');

        foreach ($orders as $o) {
            $o->courier_shipment = $shipments->get($o->id) ? $shipments->get($o->id)->first() : null;
        }

        return view('frontend.layouts.customer.tracking_result', ['orders' => $orders]);
    }


    public function change_pass()
    {
        return view('frontend.layouts.customer.change_password');
    }

    public function password_update(Request $request)
    {
        $this->validate($request, [
            'old_password' => 'required',
            'new_password' => 'required',
            'confirm_password' => 'required_with:new_password|same:new_password|'
        ]);

        $customer = Customer::find(Auth::guard('customer')->user()->id);
        $hashPass = $customer->password;

        if (Hash::check($request->old_password, $hashPass)) {

            $customer->fill([
                'password' => Hash::make($request->new_password)
            ])->save();

            // The temporary password a guest was shown after their first order
            // is now redundant, and it must stop being visible IMMEDIATELY
            // rather than lingering for the rest of its 30 minute window.
            // Reaching this point also proves the customer can log back in with
            // a real password, so clearing on use beats waiting for the TTL.
            app(GuestCheckoutAccountService::class)->clearTemporaryPassword();

            Toastr::success('Success', 'Password changed successfully!');
            return redirect()->route('customer.account');
        } else {
            Toastr::error('Failed', 'Old password not match!');
            return redirect()->back();
        }
    }
}
