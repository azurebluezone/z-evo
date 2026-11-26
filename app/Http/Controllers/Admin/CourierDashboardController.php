<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CourierDashboardService;
use Illuminate\Http\Request;

/**
 * The courier dashboard.
 *
 * The screen that answers, in business language:
 *
 *   - what is my balance at the courier,
 *   - how many parcels did I book, and where are they,
 *   - how much cash is still out for collection,
 *   - which pickup point will a Pathao parcel use.
 *
 * The data assembly (live courier counters + our own shipment rows) lives in
 * CourierDashboardService; this controller only hands it to the view.
 */
class CourierDashboardController extends Controller
{
    public function index(Request $request, CourierDashboardService $service)
    {
        return view('backEnd.courier.dashboard', $service->dashboard());
    }
}
