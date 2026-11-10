<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PhoneBlock;
use Illuminate\Http\Request;

class PhoneBlockController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('phone'));
        $normalized = PhoneBlock::normalize($search);

        $customers = collect();
        $orders = collect();

        if ($normalized !== null) {
            $customers = Customer::query()
                ->where('phone', 'like', '%' . $normalized . '%')
                ->orderByDesc('id')
                ->limit(20)
                ->get();

            $orders = Order::query()
                ->with(['shipping', 'customer'])
                ->whereHas('shipping', function ($query) use ($normalized) {
                    $query->where('phone', 'like', '%' . $normalized . '%');
                })
                ->orderByDesc('id')
                ->limit(20)
                ->get();
        }

        $blocks = PhoneBlock::query()
            ->with('blockedBy')
            ->when($search !== '', function ($query) use ($search, $normalized) {
                $needle = $normalized ?? $search;
                $query->where(function ($sub) use ($needle) {
                    $sub->where('phone', 'like', '%' . $needle . '%')
                        ->orWhere('original_phone', 'like', '%' . $needle . '%');
                });
            })
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->paginate(20)
            ->appends($request->query());

        $currentBlock = $normalized !== null
            ? PhoneBlock::where('phone', $normalized)->first()
            : null;

        return view('backEnd.visitors.phone-blocks', compact(
            'search',
            'normalized',
            'customers',
            'orders',
            'blocks',
            'currentBlock'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $normalized = PhoneBlock::normalize($validated['phone']);

        if ($normalized === null) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'That does not look like a phone number. Use 01XXXXXXXXX or +8801XXXXXXXXX.');
        }

        PhoneBlock::updateOrCreate(
            ['phone' => $normalized],
            [
                'original_phone' => $validated['phone'],
                'reason' => $validated['reason'] ?? null,
                'is_active' => true,
                'blocked_by' => $request->user()?->id,
            ]
        );

        return redirect()
            ->route('admin.visitors.phone-blocks', ['phone' => $normalized])
            ->with('success', 'Phone ' . $normalized . ' is blocked on every frontend order form.');
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
        ]);

        $normalized = PhoneBlock::normalize($validated['phone']);

        if ($normalized === null) {
            return redirect()->back()->with('error', 'That phone number is not valid.');
        }

        $updated = PhoneBlock::where('phone', $normalized)->update(['is_active' => false]);

        return redirect()
            ->route('admin.visitors.phone-blocks', ['phone' => $normalized])
            ->with(
                $updated > 0 ? 'success' : 'error',
                $updated > 0
                    ? 'Phone ' . $normalized . ' is unblocked.'
                    : 'No block found for ' . $normalized . '.'
            );
    }
}
