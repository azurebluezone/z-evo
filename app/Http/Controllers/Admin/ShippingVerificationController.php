<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courierapi;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Brian2694\Toastr\Facades\Toastr;

class ShippingVerificationController extends Controller
{
    private function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        $phone = preg_replace('/\s+/', '', $phone);
        $phone = preg_replace('/[^0-9\+]/', '', $phone);
        if (str_starts_with($phone, '88') && strlen($phone) > 11) {
            $phone = ltrim($phone, '88');
        }
        if (str_starts_with($phone, '+88')) {
            $phone = substr($phone, 3);
        }
        return $phone;
    }

    public function index(Request $request)
    {
        $invoiceId = $request->get('invoice_id');
        $phone = $request->get('phone');

        $order = null;
        $items = collect();
        $productsById = collect();

        if ((is_string($invoiceId) && $invoiceId !== '') || (is_string($phone) && $phone !== '')) {
            $orderQuery = Order::query()->with(['shipping', 'orderdetails']);

            if (is_string($invoiceId) && $invoiceId !== '') {
                $orderQuery->where('invoice_id', $invoiceId);
            }

            if (is_string($phone) && $phone !== '') {
                $norm = $this->normalizePhone($phone);
                $orderQuery->whereHas('shipping', function ($q) use ($norm) {
                    $q->where('phone', $norm)->orWhere('phone', 'like', '%' . $norm . '%');
                });
            }

            $order = $orderQuery->orderByDesc('id')->first();

            if ($order) {
                $items = $order->orderdetails;

                $productIds = $items->pluck('product_id')->filter()->unique()->values();
                if ($productIds->isNotEmpty()) {
                    $productsById = Product::query()
                        ->whereIn('id', $productIds)
                        ->get()
                        ->keyBy('id');
                }
            }
        }

        $courierQuery = Courierapi::query();

        if (Schema::hasColumn('courier_apis', 'status')) {
            $courierQuery->where('status', 1);
        }

        $select = ['id', 'type'];
        if (Schema::hasColumn('courier_apis', 'title')) {
            $select[] = 'title';
        }

        $enabledCouriers = $courierQuery
            ->select($select)
            ->orderBy('type')
            ->get();

        return view('backEnd.shipping.verification', compact('order', 'items', 'productsById', 'enabledCouriers'));
    }

    public function save(Request $request)
    {
        $request->validate([
            'order_id' => ['required', 'integer'],
            'manual_note' => ['nullable', 'string'],
            'total_qty' => ['nullable', 'numeric'],
            'total_weight_kg' => ['nullable', 'numeric'],
        ]);

        $order = Order::query()->with('shipping')->find($request->input('order_id'));
        if (!$order) {
            Toastr::error('Order not found', 'Error');
            return redirect()->back();
        }

        $note = trim((string) $request->input('manual_note', ''));
        $totalQty = (float) $request->input('total_qty', 0);
        $totalWeightKg = (float) $request->input('total_weight_kg', 0);

        $stamp = 'Shipping Verification: qty=' . $totalQty . ', weight_kg=' . $totalWeightKg;
        $payload = $note !== '' ? ($stamp . "\n" . $note) : $stamp;

        if (Schema::hasColumn('orders', 'admin_note')) {
            $existing = (string) ($order->admin_note ?? '');
            $prefix = $existing !== '' ? ($existing . "\n\n") : '';
            $order->admin_note = $prefix . $payload;
            $order->save();

            Toastr::success('Verification note saved', 'Success');
            return redirect()->route('admin.shipping.verification', [
                'invoice_id' => $order->invoice_id,
            ]);
        }

        Toastr::warning('Saved note is not available because orders.admin_note column is missing', 'Warning');
        return redirect()->back();
    }
}
