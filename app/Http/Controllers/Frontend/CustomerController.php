<?php

namespace App\Http\Controllers\Frontend;

use App\Services\SmsGatewayService;

use App\Http\Controllers\Controller;
use App\Mail\ForgotPasswordOtpMail;
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
use App\Services\Analytics\EventRecorder;
use App\Support\EventId;
use App\Services\CampaignCartRevalidator;
use App\Services\CourierFraudService;
use App\Services\GuestCheckoutAccountService;
use App\Services\OrderPlacementService;
use App\Services\FacebookTrackingService;
use App\Services\Ga4TrackingService;
use App\Services\TikTokTrackingService;
use App\Models\CourierShipment;
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
            Toastr::error('Your phone or password is wrong.');
            return redirect()->back();
        } else {
            Toastr::error('No account found for this phone number.');
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

        // The listener sends the customer welcome and the admin copy from the
        // event-keyed templates; a notification failure never blocks signup.
        event(new \App\Events\CustomerRegistered($store));

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
        $request->validate([
            'email' => ['required', 'email', 'max:120'],
        ]);

        $customer_info = Customer::where('email', $request->email)->first();
        if (! $customer_info) {
            Toastr::error('Your email address not found');
            return back()->withInput();
        }

        $customer_info->forgot = rand(1111, 9999);
        $customer_info->save();

        $site_setting = GeneralSetting::where('status', 1)->first();
        $site_name = $site_setting->name ?? config('app.name');

        // Email OTP: the delivery answer is read, just like the old SMS path.
        // A stored code nobody received must never lead to the reset screen.
        $mail = $this->sendForgotOtpMail($customer_info, $site_name);

        if (! $mail['sent']) {
            $customer_info->forgot = null;
            $customer_info->save();

            Log::warning('Forgot-password OTP email failed', $mail + ['customer_id' => $customer_info->id]);
            Toastr::error('Code could not be sent: ' . $mail['reason']);

            return back()->withInput();
        }

        Session::put('verify_email', $customer_info->email);
        Toastr::success('A verification code has been sent to your email address');
        return redirect()->route('customer.forgot.reset');
    }

    /**
     * Mail the forgot-password OTP and report the real delivery outcome.
     *
     * @return array{sent: bool, reason: ?string}
     */
    private function sendForgotOtpMail(Customer $customer_info, string $site_name): array
    {
        if (! $customer_info->email) {
            return ['sent' => false, 'reason' => 'this account has no email address'];
        }

        try {
            Mail::to($customer_info->email)->send(new ForgotPasswordOtpMail(
                (string) $customer_info->name,
                (string) $customer_info->forgot,
                $site_name
            ));
        } catch (\Throwable $e) {
            return ['sent' => false, 'reason' => $e->getMessage()];
        }

        return ['sent' => true, 'reason' => null];
    }

    public function forgot_resend(Request $request)
    {
        $email = Session::get('verify_email');
        $customer_info = $email ? Customer::where('email', $email)->first() : null;

        if (! $customer_info) {
            // A missing customer used to hit ->forgot on null and 500.
            Toastr::error('Your session expired, please start again');
            return redirect()->route('customer.forgot.password');
        }

        $customer_info->forgot = rand(1111, 9999);
        $customer_info->save();

        $site_setting = GeneralSetting::where('status', 1)->first();
        $site_name = $site_setting->name ?? config('app.name');

        $mail = $this->sendForgotOtpMail($customer_info, $site_name);

        if ($mail['sent']) {
            Toastr::success('A new code has been sent to your email address');
        } else {
            // Never claim success when delivery failed: that silence is what
            // made a missing code impossible to diagnose.
            Toastr::error('Code could not be sent: ' . $mail['reason']);
            Log::warning('OTP resend email failed', $mail);
        }

        return redirect()->back();
    }
    public function forgot_reset()
    {
        if (!Session::get('verify_email')) {
            Toastr::error('Something wrong please try again');
            return redirect()->route('customer.forgot.password');
        };
        return view('frontend.layouts.customer.forgot_reset');
    }
    public function forgot_store(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'digits:4'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $email = Session::get('verify_email');
        $customer_info = $email ? Customer::where('email', $email)->first() : null;

        if (! $customer_info || $customer_info->forgot === null) {
            // No session or no live OTP: the old code crashed on null here.
            Toastr::error('Something wrong please try again');
            return redirect()->route('customer.forgot.password');
        }

        if ((string) $customer_info->forgot !== (string) $request->otp) {
            Toastr::error('Success', 'Your OTP not match');
            return redirect()->back();
        }

        // Retire the code BEFORE saving the password. The old code stored the
        // literal 1 (`forgot = 1`), so "1" stayed a valid OTP forever.
        $customer_info->forgot = null;
        $customer_info->password = bcrypt($request->password);
        $customer_info->save();

        // Setting a real password through the OTP flow also retires any
        // temporary password left over from a guest order, for the same reason
        // password_update() clears it.
        app(GuestCheckoutAccountService::class)->clearTemporaryPassword();

        Session::forget('verify_email');

        if (Auth::guard('customer')->attempt(['email' => $customer_info->email, 'password' => $request->password])) {
            Toastr::success('You are login successfully', 'success!');
            return redirect()->intended('customer/account');
        }

        Toastr::error('Password updated, but automatic sign-in failed. Please log in.');
        return redirect()->route('customer.login');
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
        // Geography is chosen once, at checkout: the districts reference row
        // carries both the district and its division, so the order stores a
        // real district_id instead of only the free-text shipping zone name.
        $districts = District::query()
            ->orderBy('district')
            ->orderBy('area_name')
            ->get(['id', 'district', 'area_name']);
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
        return view('frontend.layouts.customer.checkout', compact('shippingcharge', 'districts', 'bkash_gateway', 'shurjopay_gateway'));
    }
    public function order_save(Request $request)
    {
        // dd($request);
        $this->validate($request, [
            'name' => 'required',
            // B4: format check only, no OTP (product decision). Matches the
            // BD mobile pattern the courier sender enforces, so a garbage
            // number is refused here instead of failing at the courier API.
            'phone' => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'address' => 'required',
            'area' => 'required',
            // Phase 2 item 2: the district is the geography the report layer
            // groups by. A free-text shipping zone alone cannot answer "where",
            // so the checkout refuses an order without a real districts row.
            'district_id' => 'required|integer|exists:districts,id',
        ]);
        if (Cart::instance('shopping')->count() <= 0) {
            Toastr::error('Your shopping empty', 'Failed!');
            return redirect()->back();
        }

        /*
         * Campaign lines can go stale while the cart waits in a session. The
         * price was valid when the item was added; by now the campaign may have
         * ended, been disabled, changed price, or lost its product. Reprice the
         * cart from the live campaign state and stop this checkout so the
         * customer sees the new total before anything is charged.
         */
        $changedCampaignLines = app(CampaignCartRevalidator::class)->revalidate();
        if (!empty($changedCampaignLines)) {
            Toastr::warning(
                'An offer in your cart has changed, so the item price was refreshed. Review the new total and place the order again.',
                'Cart updated'
            );
            return redirect()->route('customer.checkout');
        }

        // Price the order from the same server-side cart lines the checkout
        // view showed. Cart::subtotal() is a formatted string; summing the
        // lines avoids locale/format parsing and keeps the coupon base exact.
        $cartItems = Cart::instance('shopping')->content();
        $subtotal = 0.0;

        foreach ($cartItems as $cart) {
            $subtotal += ((float) $cart->price) * ((int) $cart->qty);
        }

        $shipping_area  = ShippingCharge::where('id', $request->area)->first();
        if (!$shipping_area) {
            Toastr::error('Invalid shipping area selected', 'Failed!');
            return redirect()->back();
        }

        $district = District::query()->find((int) $request->input('district_id'));
        if (!$district) {
            Toastr::error('Invalid district selected', 'Failed!');
            return redirect()->back();
        }
        $shippingfee = (float) $shipping_area->amount;
        Session::put('shipping', $shippingfee);
        Session::put('shipping_area_id', (int) $shipping_area->id);

        /*
         * Server-side coupon revalidation (B1 fix).
         *
         * The session's 'discount' value used to be trusted as-is, so a
         * client-claimed subtotal at apply-time could freeze an oversized
         * discount here. Recompute it from the REAL cart, re-check the usage
         * limit (apply-time check can go stale before this order lands), and
         * clamp so the discount can never exceed what the customer owes.
         */
        $discount = 0.0;
        $couponId = (int) Session::get('coupon_id');
        $coupon   = $couponId
            ? \App\Models\Coupon::where('id', $couponId)->running()->first()
            : null;

        if ($coupon && $coupon->isUsageAvailable()) {
            // Re-price against the real cart and the coupon's eligible lines.
            // Apply-time and order-time must use the same base, otherwise a
            // product-restricted coupon could discount the whole cart here.
            $discount = (float) $coupon->calculateDiscountForItems($cartItems);
        }

        if (!$coupon || $discount <= 0) {
            // Gone, expired, limit reached, or no eligible line in this cart.
            $coupon = null;
            Session::forget('coupon_id');
            Session::forget('coupon_code');
            Session::forget('discount');
        }

        $discount = max(0.0, $discount);
        $discount = min($discount, (float) $subtotal + $shippingfee);
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

        // Order rows: OrderPlacementService writes the order, the shipping
        // row, the payment row and the detail rows. The landing page order
        // form calls the same service, so both paths store the same data in
        // the same tables.
        $lines = [];
        foreach ($cartItems as $cart) {
            $lines[] = [
                'product_id' => $cart->id,
                'name' => $cart->name,
                'qty' => (int) $cart->qty,
                'price' => (float) $cart->price,
                'regular_price' => $cart->options->regular_price ?? $cart->price,
                'purchase_price' => $cart->options->purchase_price,
                'product_color' => $cart->options->product_color,
                'product_size' => $cart->options->product_size,
                'campaign_id' => data_get($cart->options, 'campaign_id'),
                'variant_id' => data_get($cart->options, 'variant_id'),
            ];
        }

        $order = app(OrderPlacementService::class)->place(
            $lines,
            [
                'customer_id' => $customer_id,
                'name' => (string) $request->name,
                'phone' => (string) $request->phone,
                'address' => (string) $request->address,
                'area' => $shipping_area->name,
                'district_id' => (int) $district->id,
                'payment_method' => (string) $request->payment_method,
            ],
            [
                'shipping_fee' => $shippingfee,
                'discount' => $discount,
                'fraud_source' => 'checkout',
            ]
        );

        // Coupon usage log: the checkout session carries coupon_id (set by
        // Frontend\CouponController::apply). Record the use against this
        // order so the admin coupon details page can count it.
        // Only records when the coupon survived the revalidation above
        // (running + limit available).
        if ($coupon && $discount > 0) {
            \App\Models\CouponUsage::record($coupon, $order->id, $discount, 'checkout');
        }
        Session::forget('coupon_id');
        Session::forget('coupon_code');
        Session::forget('discount');

        // Server-side purchase events (Meta, TikTok, GA4 and the shop's own
        // event log) all carry the same deterministic purchase id, so the
        // conversion is counted once and not twice.
        app(OrderPlacementService::class)->trackPurchase($order, $lines, [
            'visitor_id' => $request->cookie('visitor_id'),
            'session_id' => $request->cookie('session_id'),
            'url' => $request->fullUrl(),
            'page_path' => '/checkout',
            'user_data' => [
                'email' => $request->email ?? null,
                'phone' => $request->phone ?? null,
                'first_name' => $request->name ?? null,
            ],
        ]);
        Cart::instance('shopping')->destroy();

        // OrderPlaced (fired inside place()) sends the customer receipt and the
        // admin copy from the templates, so no hardcoded notification here.

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
        } else {
            return redirect('customer/order-success/' . $order->id);
        }
    }

    public function orders()
    {
        $orders = Order::where('customer_id', Auth::guard('customer')->user()->id)->with('status')->withCount('orderdetails')->latest()->get();
        return view('frontend.layouts.customer.orders', compact('orders'));
    }
    public function order_success($id)
    {
        $order = Order::where('id', $id)
            ->with(['orderdetails', 'shipping', 'payment'])
            ->firstOrFail();

        // Same deterministic id the checkout used, so the pixel hit and the
        // Conversions API event share one value. It is handed to the view only
        // for the visit that actually follows the order, never for a shared or
        // refreshed URL.
        $purchaseEventId = Session::pull('recent_order_' . $order->id)
            ? EventId::purchase($order)
            : null;

        return view('frontend.layouts.customer.order_success', compact('order', 'purchaseEventId'));
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
            // The form renders @error('old_password'), so the mismatch must land
            // in the error bag under that key - a bare back() showed no error.
            return redirect()->back()->withErrors(['old_password' => 'Old password not match!']);
        }
    }
}
