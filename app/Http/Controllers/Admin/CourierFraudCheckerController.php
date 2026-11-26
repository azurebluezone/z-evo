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
    public function index(Request $request, CourierFraudService $service)
    {
        $phone = (string) $request->get('phone', '');

        $base = CourierFraudCheck::query()
            ->when($phone !== '', fn ($q) => $q->where('phone', $phone))
            ->orderByDesc('checked_at');

        /*
         * Paginated, not limit(50).
         *
         * A long-running shop accumulates one row per lookup; a bare limit
         * showed a wall of rows and silently hid everything older than the
         * newest 50. 20 per page keeps the screen short and every old row
         * reachable. withQueryString() keeps ?phone= on the page links.
         */
        $checks = (clone $base)->paginate(20)->withQueryString();

        /*
         * The newest saved row for the searched number. The screen uses it to
         * show the result immediately (risk, success rate, when it was checked)
         * and to pre-fill the override form, so an admin can add or correct the
         * success rate without reading the table first.
         */
        $latest = $phone !== '' ? (clone $base)->first() : null;

        /*
         * Ask each courier, live, whether it can check THIS number. A saved
         * "unknown + internal" row alone hides why no courier score came back;
         * these rows carry the provider's own HTTP status and message. Only
         * runs when a number is being viewed, and the service caches it for
         * ten minutes, so opening the screen stays fast.
         */
        $providerStatus = $phone !== '' ? $service->providerStatus($phone) : [];

        return view('backEnd.courier.fraud_checker', compact('phone', 'checks', 'latest', 'providerStatus'));
    }

    public function check(Request $request, CourierFraudService $service)
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:32'],
        ]);

        $check = $service->checkPhone($request->phone);

        // The audit trail: a risk lookup is a decision an admin made, and it
        // is worth knowing later who looked up which number.
        app(\App\Services\AuditLogger::class)->record('courier.fraud_check.run', [
            'subject_type' => 'customer',
            'subject_id'   => $request->phone,
            'description'  => 'Courier risk check for ' . $request->phone,
            'after'        => [
                'risk_label'          => $check->risk_label,
                'success_rate_percent' => $check->success_rate_percent,
                'provider'            => $check->provider,
            ],
        ]);

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
