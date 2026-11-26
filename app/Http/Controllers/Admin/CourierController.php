<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courierapi;
use App\Models\CourierShipment;
use App\Services\CourierConnectionTester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The courier module: an overview of every courier account, its credentials, a
 * live connection test, and the shipments the webhooks have written.
 *
 * This is the read/manage half of the integration. Sending a single order to a
 * courier stays in OrderCourierController (preview -> confirm), because it acts
 * on one order rather than on the courier account.
 */
class CourierController extends Controller
{
    /** The two providers this shop integrates with. */
    private const PROVIDERS = ['steadfast', 'pathao'];

    /**
     * Every courier account, with how many shipments each has recorded.
     */
    public function index()
    {
        $couriers = Courierapi::query()
            ->orderByRaw("FIELD(type, 'steadfast', 'pathao')")
            ->orderBy('type')
            ->get();

        // One grouped query rather than a count per courier row.
        $shipmentStats = CourierShipment::query()
            ->select('provider', DB::raw('COUNT(*) as total'), DB::raw('MAX(last_synced_at) as last_synced_at'))
            ->groupBy('provider')
            ->get()
            ->keyBy('provider');

        return view('backEnd.courier.index', compact('couriers', 'shipmentStats'));
    }

    /**
     * Legacy per-courier credentials URL.
     *
     * The form used to live here as a third copy of the same fields. It now
     * redirects to the one canonical screen, deep-linked to the provider card,
     * so an old bookmark cannot reopen a second editor.
     */
    public function edit(string $type)
    {
        $type = $this->guardType($type);

        return redirect()->to(route('admin.courier.settings') . '#courier-' . $type);
    }

    /**
     * Save credentials. Which fields are required depends on the provider: a
     * SteadFast account has no notion of a client id, and Pathao has no API key.
     */
    public function update(Request $request, string $type, CourierConnectionTester $tester)
    {
        $type = $this->guardType($type);

        $courier = Courierapi::query()->firstOrNew(['type' => $type]);

        /*
         * A blank secret field means "keep the saved value".
         *
         * The form no longer prints stored secrets back into the HTML (it shows
         * a mask), so a normal save submits empty strings for them. Required
         * only when nothing is stored yet, otherwise the validator would refuse
         * every edit that does not retype the password.
         */
        $keepIfBlank = [
            'api_key'       => $courier->api_key,
            'secret_key'    => $courier->secret_key,
            'client_secret' => $courier->client_secret,
            'password'      => $courier->password,
        ];

        $secretRule = static fn (?string $current): string => filled($current)
            ? 'nullable|string|max:255'
            : 'required|string|max:255';

        $rules = [
            'title'  => 'nullable|string|max:155',
            'url'    => 'required|url|max:255',
            'status' => 'nullable|boolean',
        ];

        if ($type === 'steadfast') {
            $rules['api_key']    = $secretRule($courier->api_key);
            $rules['secret_key'] = $secretRule($courier->secret_key);
        } else {
            $rules['client_id']     = 'required|string|max:255';
            $rules['client_secret'] = $secretRule($courier->client_secret);
            $rules['username']      = 'required|email|max:255';
            $rules['password']      = $secretRule($courier->password);
        }

        $validated = $request->validate($rules);

        $courier->fill($validated);
        $courier->type = $type;

        foreach ($keepIfBlank as $field => $current) {
            if (blank($request->input($field)) && filled($current)) {
                $courier->{$field} = $current;
            }
        }

        // A webhook token is only ever generated, never typed: the endpoint
        // refuses every request whose bearer token does not match, so a blank
        // token would silently disable the whole webhook.
        if (blank($courier->webhook_token)) {
            $courier->webhook_token = bin2hex(random_bytes(48));   // 96 hex chars
        }

        // Never switch a courier on while it is obviously unusable: every send
        // would then fail with an upstream error nobody can explain from the UI.
        $courier->status = $request->boolean('status') && $courier->isConfigured() ? 1 : 0;

        $courier->save();

        // The audit trail: credentials and the switch state are worth a line.
        app(\App\Services\AuditLogger::class)->courier(
            'courier.credentials.saved',
            $type,
            [
                'status'    => (int) $courier->status,
                'url'       => (string) $courier->url,
                'configured' => (bool) $courier->isConfigured(),
            ],
            'Courier settings saved for ' . ucfirst($type)
        );

        $result = $tester->test($courier);

        return redirect()
            ->to(route('admin.courier.settings') . '#courier-' . $type)
            ->with('success', 'Settings saved for ' . ucfirst($type) . '.')
            ->with('connection_test', $result);
    }

    /**
     * Enable / disable a courier, refusing to enable an unconfigured one.
     */
    public function toggle(string $type)
    {
        $type = $this->guardType($type);

        $courier = Courierapi::query()->where('type', $type)->first();

        if (! $courier) {
            return redirect()->route('admin.courier.index')
                ->with('error', 'No ' . ucfirst($type) . ' account exists yet.');
        }

        if (! $courier->status && ! $courier->isConfigured()) {
            return redirect()->route('admin.courier.index')
                ->with('error', 'Fill in the credentials for ' . ucfirst($type) . ' before enabling it.');
        }

        $courier->status = $courier->status ? 0 : 1;
        $courier->save();

        app(\App\Services\AuditLogger::class)->courier(
            'courier.toggled',
            $type,
            ['status' => (int) $courier->status],
            ucfirst($type) . ' is now ' . ($courier->status ? 'enabled' : 'disabled')
        );

        return redirect()->route('admin.courier.index')->with(
            'success',
            ucfirst($type) . ' is now ' . ($courier->status ? 'enabled' : 'disabled') . '.'
        );
    }

    /**
     * Live connection test, answered as JSON so the screen can show the steps.
     */
    public function test(string $type, CourierConnectionTester $tester)
    {
        $type = $this->guardType($type);

        $courier = Courierapi::query()->firstOrNew(['type' => $type]);

        return response()->json($tester->test($courier));
    }

    /**
     * Rotate the webhook token.
     *
     * Rotation is the only recovery when a token leaks, so it has to be one
     * click - but it also takes effect immediately, which means the courier's
     * configured URL starts being refused until the new token is copied across.
     * The screen says so.
     */
    public function rotateToken(string $type)
    {
        $type = $this->guardType($type);

        $courier = Courierapi::query()->where('type', $type)->first();

        if (! $courier) {
            return redirect()->route('admin.courier.index')->with('error', 'No such courier account.');
        }

        $courier->webhook_token = bin2hex(random_bytes(48));
        $courier->save();

        Log::info('Courier webhook token rotated', ['type' => $type]);

        // The audit trail: the old token stops working the moment this lands.
        app(\App\Services\AuditLogger::class)->courier(
            'courier.webhook_token.rotated',
            $type,
            ['rotated_at' => now()->toDateTimeString()],
            'Webhook token rotated for ' . ucfirst($type)
        );

        return redirect()
            ->to(route('admin.courier.settings') . '#courier-' . $type)
            ->with(
                'success',
                'A new webhook token was generated for ' . ucfirst($type)
                    . '. Copy it to the courier portal now - the old token stops working immediately.'
            );
    }

    /**
     * Return the stored webhook token on demand.
     *
     * The settings page used to carry the token in a hidden input so the Copy
     * button could read it - which put the secret in View Source and DevTools.
     * The page now prints only the mask and asks for the value at copy time,
     * through this authenticated admin endpoint.
     */
    public function webhookToken(string $type)
    {
        $type = $this->guardType($type);

        $courier = Courierapi::query()->where('type', $type)->first();

        if (! $courier || blank($courier->webhook_token)) {
            return response()->json(
                ['message' => 'No webhook token has been generated yet.'],
                404
            );
        }

        return response()->json(['token' => $courier->webhook_token]);
    }

    /**
     * Shipments recorded by the webhooks.
     */
    public function shipments(Request $request)
    {
        $provider = trim((string) $request->query('provider', ''));
        $status   = trim((string) $request->query('status', ''));
        $search   = trim((string) $request->query('q', ''));

        $shipments = CourierShipment::query()
            ->with('order')
            ->when(in_array($provider, self::PROVIDERS, true), fn ($q) => $q->where('provider', $provider))
            ->when($status !== '', fn ($q) => $q->where('current_status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('consignment_id', 'like', '%' . $search . '%')
                        ->orWhere('tracking_code', 'like', '%' . $search . '%')
                        ->orWhere('merchant_order_id', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        // Only offer statuses that actually occur, so the filter cannot dead-end.
        $statuses = CourierShipment::query()
            ->select('current_status')
            ->whereNotNull('current_status')
            ->distinct()
            ->orderBy('current_status')
            ->pluck('current_status');

        return view('backEnd.courier.shipments', compact('shipments', 'statuses', 'provider', 'status', 'search'));
    }

    /**
     * One shipment, including the raw webhook body it was built from.
     */
    public function shipment(CourierShipment $shipment)
    {
        $shipment->load('order');

        return view('backEnd.courier.shipment', compact('shipment'));
    }

    /**
     * Refuse an unknown provider rather than letting it reach a query, where it
     * would either match nothing (a silent empty page) or create a junk row.
     */
    private function guardType(string $type): string
    {
        $type = strtolower(trim($type));

        abort_unless(in_array($type, self::PROVIDERS, true), 404, 'Unknown courier.');

        return $type;
    }
}
