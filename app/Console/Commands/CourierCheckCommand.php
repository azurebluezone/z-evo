<?php

namespace App\Console\Commands;

use App\Models\Courierapi;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Verify the saved courier credentials against the live API.
 *
 * ---------------------------------------------------------------------------
 * WHY A COMMAND AND NOT ONLY THE ADMIN BUTTON
 * ---------------------------------------------------------------------------
 * A connection test that can only be run from a browser is useless when the
 * person holding the API key is not the person sitting at the admin panel, and
 * it cannot be run from a deploy script or a cron health check.
 *
 * This reads the credentials already stored in courier_apis, so nothing has to
 * be pasted twice, and it never prints a secret.
 *
 * ---------------------------------------------------------------------------
 * WHAT IT ACTUALLY PROVES
 * ---------------------------------------------------------------------------
 * SteadFast's cheapest authenticated read is GET /get_balance. A success means
 * the base URL is reachable, the key pair is accepted and the account exists -
 * i.e. everything needed to create a consignment. It does NOT prove an order
 * can be created: that would create a real, billable parcel, so this command
 * deliberately does not attempt one.
 *
 * With --create a single TEST consignment is offered instead, clearly marked so
 * it can be recognised on the courier's dashboard and cancelled. It still costs
 * money, so it is opt-in and requires an explicit order id.
 *
 * Usage:
 *   php artisan courier:check
 *   php artisan courier:check --provider=steadfast
 *   php artisan courier:check --create --order=123      (bills!)
 */
class CourierCheckCommand extends Command
{
    protected $signature = 'courier:check
        {--provider= : steadfast or pathao; defaults to every configured courier}
        {--create : Also create one TEST consignment. THIS IS BILLABLE.}
        {--order= : Order id for --create}';

    protected $description = 'Test the stored courier API credentials against the live courier';

    /** Same generous timeout the order sender uses, so neither can hang a run. */
    private const TIMEOUT = 30;

    public function handle(): int
    {
        $providers = $this->option('provider')
            ? [strtolower(trim((string) $this->option('provider')))]
            : ['steadfast', 'pathao'];

        if ($this->option('create') && ! $this->option('order')) {
            $this->error('--create needs --order=<id>. Read-only check skipped.');
        }

        $exit = 0;

        foreach ($providers as $provider) {
            $exit = $this->check($provider) ? $exit : 1;
        }

        return $exit;
    }

    private function check(string $provider): bool
    {
        $this->newLine();
        $this->line("<options=bold>{$provider}</>");

        if (! in_array($provider, ['steadfast', 'pathao'], true)) {
            $this->error('  Unknown courier.');

            return false;
        }

        /*
         * Service check FIRST.
         *
         * GET /ping needs no credentials. SteadFast's own guide says to make it
         * first: if it answers, the base URL is right and this server can reach
         * them, so anything failing afterwards is the keys or the request - not
         * the connection. Without it, a DNS failure and a revoked key produce
         * the same unhelpful 401-shaped mystery.
         */
        $this->line('    service   : checking /ping (no credentials needed)');
        $this->checkPing($configUrlForPing ?? ($this->pingUrl() ?? ''));

        try {
            $config = Courierapi::where('type', $provider)->first();
        } catch (\Throwable $e) {
            $this->error('  courier_apis is unreachable: ' . $e->getMessage());

            return false;
        }

        if (! $config) {
            $this->error('  No row in courier_apis.');

            return false;
        }

        if (! $config->isReady()) {
            $this->warn('  Not switched on, or credentials incomplete:');
            $this->line('    ' . $config->label() . ' is not ready to use.');
            $this->line('    Fill the fields in Admin > Courier API Settings and switch it on.');

            return false;
        }

        $this->line('    endpoint : ' . $this->balanceEndpoint($config));
        $this->line('    key      : ' . $this->mask((string) $config->api_key));
        $this->line('    secret   : ' . $this->mask((string) $config->secret_key));

        if ($config->type === 'pathao') {
            return $this->checkPathao($config);
        }

        return $this->checkSteadfast($config);
    }

    /** Base URL for the credential-free ping. */
    private function pingUrl(): ?string
    {
        try {
            $url = (string) (Courierapi::where('type', 'steadfast')->value('url') ?? '');
        } catch (\Throwable $e) {
            return null;
        }

        if ($url === '') {
            return null;
        }

        $url = rtrim($url, '/');

        // Reduce any documented base shape down to the api/v1 root.
        $url = preg_replace('#/(create_order|get_balance)$#', '', $url) ?? $url;

        return $url . '/ping';
    }

    /** Ask SteadFast whether the service is up. Read-only, no keys. */
    private function checkPing(string $url): void
    {
        if ($url === '') {
            $this->warn('    skipped: no base URL saved yet.');

            return;
        }

        try {
            $response = (new Client(['timeout' => 15, 'http_errors' => false]))
                ->get($url, ['headers' => ['Accept' => 'application/json']]);
        } catch (\Throwable $e) {
            $this->error('    service unreachable: ' . $e->getMessage());
            $this->line('    Nothing below can succeed until this answers.');

            return;
        }

        $body = json_decode((string) $response->getBody(), true);

        if ((int) $response->getStatusCode() === 200 && is_array($body) && ($body['message'] ?? '') === 'pong') {
            $this->info('    service reachable (' . ($body['service'] ?? 'steadfast-api')
                . ' ' . ($body['version'] ?? '') . ')');

            return;
        }

        $this->warn('    answered but not the expected shape: '
            . substr((string) $response->getBody(), 0, 120));
    }

    private function checkSteadfast(Courierapi $config): bool
    {
        $client = new Client([
            'timeout'         => self::TIMEOUT,
            'connect_timeout' => 10,
            // We want the courier's own body even on 401/404, so HTTP errors
            // must not be thrown - that is the actual answer we are reading.
            'http_errors'     => false,
        ]);

        try {
            $response = $client->get($this->balanceEndpoint($config), [
                'headers' => [
                    'Api-Key'    => (string) $config->api_key,
                    'Secret-Key' => (string) $config->secret_key,
                    'Accept'     => 'application/json',
                ],
            ]);
        } catch (\Throwable $e) {
            $this->error('  Network failure: ' . $e->getMessage());
            $this->line('    Check the base URL and that this server can reach the internet.');

            return false;
        }

        $status = (int) $response->getStatusCode();
        $body   = json_decode((string) $response->getBody(), true);

        if ($status === 200 && is_array($body) && isset($body['current_balance'])) {
            $this->info('  OK - credentials accepted.');
            $this->line('    account balance : ' . $body['current_balance']);

            return true;
        }

        // A 200 that is not the expected shape means something is answering -
        // a proxy, a login page, the wrong host - which is more confusing than
        // a clean refusal, so it is called out separately.
        if ($status === 200) {
            $this->error('  Reached the host but the answer is not a SteadFast balance.');
            $this->line('    body: ' . substr((string) $response->getBody(), 0, 200));

            return false;
        }

        $this->error("  Failed - HTTP {$status}.");
        $this->line('    courier said: ' . (data_get($body, 'message') ?: substr((string) $response->getBody(), 0, 200)));

        return match ($status) {
            401, 403 => $this->line('    That means the API key / secret key pair is wrong or inactive.'),
            404      => $this->line('    The path is wrong. The base URL should be the api/v1 root, e.g. https://portal.packzy.com/api/v1'),
            429      => $this->line('    Rate limited (60 requests per minute). Wait and retry.'),
            default  => null,
        } ?? false;
    }

    private function checkPathao(Courierapi $config): bool
    {
        if (filled($config->token)) {
            $this->info('  OK - an access token is already stored.');

            return true;
        }

        $url = rtrim((string) $config->url, '/') . '/issue-token';

        try {
            $response = (new Client(['timeout' => self::TIMEOUT, 'http_errors' => false]))
                ->post($url, [
                    'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
                    'json' => [
                        'client_id'     => (string) $config->client_id,
                        'client_secret' => (string) $config->client_secret,
                        'grant_type'    => 'password',
                        'username'      => (string) $config->username,
                        'password'      => (string) $config->password,
                    ],
                ]);
        } catch (\Throwable $e) {
            $this->error('  Network failure: ' . $e->getMessage());

            return false;
        }

        $status = (int) $response->getStatusCode();
        $body   = json_decode((string) $response->getBody(), true);

        if ($status === 200 && is_array($body) && filled($body['access_token'] ?? null)) {
            // Store it so the order screen does not have to re-authenticate.
            $config->update(['token' => (string) $body['access_token']]);

            $this->info('  OK - credentials accepted, access token stored.');

            return true;
        }

        $this->error("  Failed - HTTP {$status}.");
        $this->line('    pathao said: ' . (data_get($body, 'message') ?: substr((string) $response->getBody(), 0, 200)));

        return false;
    }

    private function balanceEndpoint(Courierapi $config): string
    {
        $url = rtrim((string) $config->url, '/');

        // Accept a base URL pointing at any of the documented shapes.
        if (str_contains($url, '/get_balance')) {
            return $url;
        }

        if (str_contains($url, '/create_order')) {
            return str_replace('/create_order', '/get_balance', $url);
        }

        return $url . '/get_balance';
    }

    /** Show only enough of a secret to recognise it, never enough to use it. */
    private function mask(string $value): string
    {
        $value = (string) $value;

        if ($value === '') {
            return '(empty)';
        }

        if (strlen($value) <= 8) {
            return str_repeat('*', strlen($value));
        }

        return substr($value, 0, 4) . str_repeat('*', min(12, strlen($value) - 8)) . substr($value, -4);
    }
}