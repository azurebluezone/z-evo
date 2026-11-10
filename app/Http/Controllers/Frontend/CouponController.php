<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Session;
use Cart;

class CouponController extends Controller
{
    public function apply(Request $request)
    {
        $codeRaw = (string) ($request->input('coupon_code') ?? $request->input('code') ?? '');
        $code = strtoupper(trim($codeRaw));

        /*
         * NEVER trust a client-sent subtotal. The discount is priced from the
         * server-side cart only; Coupon::calculateDiscountForItems() also
         * restricts product-specific coupons to their eligible lines.
         */
        $cartItems = Cart::instance('shopping')->content();

        if ($code === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Coupon field is required',
            ]);
        }

        $coupon = Coupon::where('code', $code)->running()->first();
        if (!$coupon) {
            Session::forget('discount');
            Session::forget('coupon_code');
            Session::forget('coupon_id');
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired coupon',
            ]);
        }

        if (!$coupon->isUsageAvailable()) {
            Session::forget('discount');
            Session::forget('coupon_code');
            Session::forget('coupon_id');

            return response()->json([
                'status' => 'error',
                'message' => 'Coupon usage limit reached',
            ]);
        }

        $discount = $coupon->calculateDiscountForItems($cartItems);
        if ($discount <= 0) {
            Session::forget('discount');
            Session::forget('coupon_code');
            Session::forget('coupon_id');

            return response()->json([
                'status' => 'error',
                'message' => 'Coupon is not applicable for this cart',
            ]);
        }

        Session::put('discount', $discount);
        Session::put('coupon_code', $coupon->code);
        Session::put('coupon_id', $coupon->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Coupon applied successfully',
            'amount' => $discount,
            'coupon_code' => $coupon->code,
        ]);
    }

    public function remove(Request $request)
    {
        Session::forget('discount');
        Session::forget('coupon_code');
        Session::forget('coupon_id');

        return response()->json([
            'status' => 'success',
            'success' => 'Coupon removed',
        ]);
    }
}
