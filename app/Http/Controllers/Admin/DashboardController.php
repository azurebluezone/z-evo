<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\Customer;
use App\Models\OrderStatus;
use App\Models\Payment;
use App\Services\Admin\AdminDashboardService;
use Carbon\Carbon;
use Session;
use Toastr;
use Auth;
use DB;
class DashboardController extends Controller
{
    public function __construct()
    {
        // The constructor is intentionally empty. This controller is reached
        // through routes/web.php's own middleware, and locked()/unlocked() are
        // declared on the routes so they can drop LockAccount while staying
        // behind auth. Re-adding controller-level middleware here would put it
        // back in front of the lock screen and cause a redirect loop.
    }

    private function deliveredStatusId(): int
    {
        try {
            $status = OrderStatus::query()->where('slug', 'delivered')->first();
            if ($status && isset($status->id)) {
                return (int) $status->id;
            }
        } catch (\Throwable $e) {
            // no-op
        }

        return 5;
    }

    private function salesTrendForDays(int $days): array
    {
        $days = max(1, min(90, $days));
        $start = Carbon::today()->subDays($days - 1);

        $rows = Order::query()
            ->selectRaw('DATE(created_at) as date, SUM(amount) as amount')
            ->where('order_status', $this->deliveredStatusId())
            ->whereDate('created_at', '>=', $start)
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $byDate = [];
        foreach ($rows as $row) {
            $byDate[(string) $row->date] = (float) $row->amount;
        }

        $labels = [];
        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $d = (clone $start)->addDays($i);
            $key = $d->toDateString();
            $labels[] = $d->format('d-m-Y');
            $series[] = $byDate[$key] ?? 0;
        }

        return ['labels' => $labels, 'series' => $series];
    }

    public function dashboard(){
        /** @var AdminDashboardService $dash */
        $dash = app(AdminDashboardService::class);

        $total_order = $dash->getTotalOrders();
        $today_visitors = $dash->getTodayVisitors();
        $total_revenue = $dash->getTotalRevenueDelivered();
        $social_event_count_today = $dash->getSocialEventCountToday();
        $social_event_feed = $dash->getSocialEventFeed(10);

        $live_visitors_summary = $dash->getLiveVisitorsSummary(5);
        $live_visitors_trend = $dash->getLiveVisitorsTrend(12, 5);
        $weekly_sales_widget = $dash->getWeeklySalesWidgetData();

        $latest_order = Order::latest()->limit(5)->with('customer','product','product.image')->get();
        $latest_customer = Customer::latest()->limit(5)->get();

        $trend7 = $dash->getSalesTrendForDays(7);

        // New dashboard stats
        $today_sale = $dash->getTodaySale();
        $pending_orders = $dash->getPendingOrdersCount();
        $low_stock_alert = $dash->getLowStockAlertCount();
        $total_customers = $dash->getTotalCustomers();
        $recent_orders = $dash->getRecentOrders(5);
        $failed_login_attempts = $dash->getFailedLoginAttempts(60);
        $abnormal_orders = $dash->getAbnormalOrders(5, 3);

        return view('backEnd.admin.dashboard',compact(
            'total_order',
            'today_visitors',
            'total_revenue',
            'social_event_count_today',
            'social_event_feed',
            'live_visitors_summary',
            'live_visitors_trend',
            'weekly_sales_widget',
            'latest_order',
            'latest_customer',
            'trend7',
            'today_sale',
            'pending_orders',
            'low_stock_alert',
            'total_customers',
            'recent_orders',
            'failed_login_attempts',
            'abnormal_orders'
        ));
    }
    public function changepassword(){
        return view('backEnd.admin.changepassword');
    }
     public function newpassword(Request $request)
    {
        $this->validate($request, [
            'old_password'=>'required',
            'new_password'=>'required',
            'confirm_password' => 'required_with:new_password|same:new_password|'
        ]);

        $user = User::find(Auth::id());
        $hashPass = $user->password;

        if (Hash::check($request->old_password, $hashPass)) {

            $user->fill([
                'password' => Hash::make($request->new_password)
            ])->save();

            Toastr::success('Success', 'Password changed successfully!');
            return redirect()->route('dashboard');
        }else{
            Toastr::error('Failed', 'Old password not match!');
            return back();
        }
    }
    /**
     * Lock the admin screen.
     *
     * The route is a POST inside the authenticated group, so a link, a
     * prefetch or an <img> tag in an email cannot reach this any more. The
     * explicit guard below is deliberate redundancy: if the route is ever
     * moved back out of the group, the method itself still refuses.
     */
    public function locked()
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        Session::put('locked', true);

        return view('backEnd.auth.locked');
    }

    public function unlocked(Request $request)
    {
        if(!Auth::check())
            return redirect()->route('login');
        $password = $request->password;
        if(Hash::check($password,Auth::user()->password)){
            Session::forget('locked');
            Toastr::success('Success', 'You are logged in successfully!');
            return redirect()->route('dashboard');
        }
        Toastr::error('Failed', 'Your password not match!');
        return back();
    }
}
