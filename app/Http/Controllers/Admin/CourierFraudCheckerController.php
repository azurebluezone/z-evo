<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourierFraudCheck;
use App\Services\CourierFraudService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CourierFraudCheckerController extends Controller
{
    public function index(Request $request)
    {
        $phone = (string) $request->get('phone', '');

        $checks = CourierFraudCheck::query()
            ->when($phone !== '', fn ($q) => $q->where('phone', $phone))
            ->orderByDesc('checked_at')
            ->limit(50)
            ->get();

        return view('backEnd.courier.fraud_checker', compact('phone', 'checks'));
    }

    public function check(Request $request, CourierFraudService $service)
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:32'],
        ]);

        $service->checkPhone($request->phone);

        return redirect()->route('admin.courier.fraud-checker', ['phone' => $request->phone]);
    }

    public function override(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'risk_label' => ['required', 'in:normal,risky,high_risk,unknown'],
            'success_rate_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        CourierFraudCheck::query()->create([
            'phone' => $request->phone,
            'provider' => 'manual',
            'success_rate_percent' => $request->success_rate_percent,
            'risk_label' => $request->risk_label,
            'raw_payload' => [
                'source' => 'manual_override',
                'note' => 'Admin override',
            ],
            'checked_at' => Carbon::now(),
            'checked_by' => Auth::check() ? (int) Auth::id() : null,
        ]);

        return redirect()->route('admin.courier.fraud-checker', ['phone' => $request->phone]);
    }
}
