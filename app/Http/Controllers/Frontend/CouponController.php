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
        $subtotal = (float) $request->input('subtotal', 0);

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
            return response()->json([
                'status' => 'error',
                'message' => 'Coupon usage limit reached',
            ]);
        }

        $eligibleSubtotal = $subtotal;
        if ($coupon->isRestrictedToProducts()) {
            $allowedIds = $coupon->products()->pluck('products.id')->all();
            $eligibleSubtotal = 0.0;

            foreach (Cart::instance('shopping')->content() as $item) {
                if (in_array((int) $item->id, $allowedIds, true)) {
                    $eligibleSubtotal += ((float) $item->price) * ((float) $item->qty);
                }
            }
        }

        $discount = $coupon->calculateDiscount((float) $eligibleSubtotal);
        if ($discount <= 0) {
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
