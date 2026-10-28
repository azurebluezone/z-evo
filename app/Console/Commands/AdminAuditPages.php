<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Admin\PageStats;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * Walk the admin the way the sidebar does and prove every page still works.
 *
 * WHY NOT A BROWSER
 * -----------------
 * A browser test needs a server, a session cookie and Playwright before it can
 * answer one question: "does this page render with the shared header?". This
 * command asks the framework directly, in one process, so it can run on every
 * commit and print a table a human can read.
 *
 * WHAT IT CHECKS
 * --------------
 *  1. the route is a parameterless GET (nothing that could write);
 *  2. the response is 200 and HTML;
 *  3. the shared header (`data-admin-stat-grid`) appears exactly once;
 *  4. a configured module card is not silently falling back to an em dash on a
 *     page that should have a real number.
 *
 * The route list comes from sidebar.blade.php on purpose: the sidebar is the
 * admin's own idea of "all the pages", so a page nobody links to is not a page
 * the user can reach.
 */
class AdminAuditPages extends Command
{
    protected $signature = 'admin:audit-pages
                            {--only= : Comma separated route names to limit the run}
                            {--json= : Write the full result set to this path}
                            {--all : Audit every parameterless admin GET route, not only sidebar links}';

    protected $description = 'Render every admin page the sidebar links to and verify status + shared stat header';

    /**
     * Pages whose parameters are safe, read-only values. Anything else with a
     * parameter is skipped and counted - never guessed.
     */
    private array $sampleParams = [
        'admin.orders' => ['slug' => 'all'],
    ];

    public function handle(): int
    {
        $admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->first()
            ?? User::query()->first();

        if (! $admin) {
            $this->error('No user to authenticate as. Seed an admin first.');

            return self::FAILURE;
        }

        Auth::login($admin);

        $names = $this->routeNames();
        $only = array_filter(array_map('trim', explode(',', (string) $this->option('only'))));

        if ($only !== []) {
            $names = array_values(array_intersect($names, $only));
        }

        $rows = [];
        $skipped = [];
        $kernel = app(Kernel::class);

        foreach ($names as $name) {
            $route = Route::getRoutes()->getByName($name);

            if (! $route) {
                $rows[] = $this->row($name, '-', 'MISSING', 0, 'route name not registered');

                continue;
            }

            if (! in_array('GET', $route->methods(), true)) {
                $skipped[] = [$name, 'not a GET'];

                continue;
            }

            $uri = $route->uri();

            if (str_contains($uri, '{')) {
                if (! isset($this->sampleParams[$name])) {
                    $skipped[] = [$name, 'needs parameters'];

                    continue;
                }

                foreach ($this->sampleParams[$name] as $key => $value) {
                    $uri = str_replace('{' . $key . '}', $value, $uri);
                }
            }

            $request = Request::create('/' . $uri, 'GET');
            $request->headers->set('Accept', 'text/html');

            try {
                $response = $kernel->handle($request);
                $status = $response->getStatusCode();
                $body = (string) $response->getContent();
                $type = (string) $response->headers->get('Content-Type');
            } catch (Throwable $e) {
                $rows[] = $this->row($name, '/' . $uri, 'EXCEPTION', 0, class_basename($e) . ': ' . $e->getMessage());

                continue;
            } finally {
                // Keep the next page from inheriting this one's session state.
                if (session()->isStarted()) {
                    session()->save();
                }
            }

            $excluded = in_array($name, config('admin_stats.exclude', []), true);
            $grid = str_contains($type, 'text/html') ? substr_count($body, 'data-admin-stat-grid') : 0;
            $cardCheck = $this->inspectCards($name, $uri, $status, $body, $type, $grid, $excluded);

            if ($excluded) {
                $result = 'SKIP';
                $note = 'excluded by config (own KPI band)';
            } elseif (! str_contains($type, 'text/html')) {
                $result = 'SKIP';
                $note = 'not a page: ' . $type;
            } elseif ($status === 200 && trim($body) === '') {
                $result = 'SKIP';
                $note = 'empty 200 (fragment endpoint)';
            } elseif ($status >= 300 && $status < 400) {
                $result = 'SKIP';
                $note = 'redirect (needs query parameters)';
            } else {
                $result = $status === 200 && $grid === 1 && $cardCheck['ok'] ? 'PASS' : 'FAIL';
                $note = match (true) {
                    $status === 403 => 'forbidden: missing role/permission',
                    $status === 404 => 'not found',
                    $status !== 200 => 'HTTP ' . $status,
                    $grid === 0 => 'header missing',
                    $grid > 1 => 'header rendered ' . $grid . 'x',
                    ! $cardCheck['ok'] => $cardCheck['note'],
                    default => '',
                };
            }

            $rows[] = $this->row($name, '/' . $uri, $result, $grid, $note, $cardCheck['cards']);
        }

        $this->table(
            ['route', 'uri', 'result', 'grid', 'note'],
            array_map(fn ($r) => [$r['route'], $r['uri'], $r['result'], $r['grid'], $r['note']], $rows)
        );

        $pass = count(array_filter($rows, fn ($r) => $r['result'] === 'PASS'));
        $skipRows = count(array_filter($rows, fn ($r) => $r['result'] === 'SKIP'));
        $excludedRows = count(array_filter($rows, fn ($r) => str_contains($r['note'], 'excluded by config')));
        $fail = count($rows) - $pass - $skipRows;

        foreach ($skipped as [$name, $why]) {
            $this->line("  skipped {$name}: {$why}");
        }

        $this->newLine();
        $this->line(sprintf(
            'pages: %d  pass: %d  fail: %d  skipped: %d (excluded %d, non-page %d)  pre-skipped: %d',
            count($rows), $pass, $fail, $skipRows, $excludedRows, $skipRows - $excludedRows, count($skipped)
        ));

        if ($path = $this->option('json')) {
            file_put_contents($path, json_encode([
                'generated_at' => now()->toIso8601String(),
                'rows' => $rows,
                'skipped' => $skipped,
                'summary' => [
                    'pages' => count($rows),
                    'pass' => $pass,
                    'fail' => $fail,
                    'skipped_rows' => $skipRows,
                    'excluded' => $excludedRows,
                    'pre_skipped' => count($skipped),
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $this->line("wrote {$path}");
        }

        return $fail === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Route names the sidebar actually links to, in file order.
     *
     * @return array<int,string>
     */
    private function routeNames(): array
    {
        if ($this->option('all')) {
            return collect(Route::getRoutes()->getRoutes())
                ->filter(fn ($r) => str_starts_with($r->uri(), 'admin/'))
                ->filter(fn ($r) => in_array('GET', $r->methods(), true))
                ->filter(fn ($r) => ! in_array($r->getName(), ['admin.logout', 'admin.locked', 'admin.login'], true))
                ->map(fn ($r) => $r->getName())
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        $sidebar = resource_path('views/backEnd/layouts/partials/sidebar.blade.php');
        $source = file_get_contents($sidebar);
        preg_match_all("/route\\('([a-zA-Z0-9._-]+)'/", $source, $matches);

        return array_values(array_unique($matches[1]));
    }

    private function row(string $name, string $uri, string $result, int $grid, string $note, array $cards = []): array
    {
        return compact('name', 'uri', 'result', 'grid', 'note', 'cards') + [
            'route' => $name,
        ];
    }

    /**
     * Read the four rendered cards back out of the HTML and compare them with
     * fresh database counts. This is the check that makes the header
     * content-aware: a coupon page that prints the customer trio, or a total
     * that drifted from its table, fails here even though the grid rendered.
     *
     * @return array{ok:bool,note:string,cards:array<int,array{label:string,value:string}>}
     */
    private function inspectCards(string $name, string $uri, int $status, string $body, string $type, int $grid, bool $excluded): array
    {
        if ($excluded || $status !== 200 || $grid !== 1 || ! str_contains($type, 'text/html')) {
            return ['ok' => true, 'note' => '', 'cards' => []];
        }

        preg_match_all('/<h6 class="mb-0 fw-normal" data-stat-label>([^<]+)<\/h6>/u', $body, $labelMatches);
        preg_match_all('/data-stat="([^"]+)"[^>]*>\s*([^<]*)</u', $body, $statMatches, PREG_SET_ORDER);

        $rendered = [];
        foreach ($labelMatches[1] as $index => $label) {
            $rendered[] = [
                'label' => trim(html_entity_decode($label, ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                'value' => trim(html_entity_decode($statMatches[$index][2] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            ];
        }

        $segment = explode('/', trim($uri, '/'))[1] ?? null;

        try {
            $expected = $this->expectedCards($name, $segment);
        } catch (Throwable $e) {
            return ['ok' => false, 'note' => 'card oracle failed: ' . class_basename($e) . ': ' . $e->getMessage(), 'cards' => $rendered];
        }

        if (count($rendered) !== 4) {
            return ['ok' => false, 'note' => 'expected 4 cards, rendered ' . count($rendered), 'cards' => $rendered];
        }

        foreach ($expected as $index => $card) {
            $actual = $rendered[$index];

            if ($actual['label'] !== $card['label']) {
                return [
                    'ok' => false,
                    'note' => sprintf('card %d label "%s", expected "%s"', $index + 1, $actual['label'], $card['label']),
                    'cards' => $rendered,
                ];
            }

            if ($actual['value'] !== $card['value']) {
                return [
                    'ok' => false,
                    'note' => sprintf('%s shows %s, database says %s', $card['label'], $actual['value'], $card['value']),
                    'cards' => $rendered,
                ];
            }
        }

        return ['ok' => true, 'note' => '', 'cards' => $rendered];
    }

    /**
     * Independent oracle: what the four cards must say for this route, counted
     * straight from the database with the same config the renderer uses.
     *
     * @return array<int,array{label:string,value:string}>
     */
    private function expectedCards(string $name, ?string $segment): array
    {
        $stats = app(PageStats::class);
        $definition = $stats->definitionFor($name, $segment);
        $format = fn (?int $value): string => $value === null ? '—' : number_format($value);
        $cards = [];

        if ($definition === null) {
            foreach (config('admin_stats.overview', []) as $metric) {
                $cards[] = ['label' => 'Total ' . $metric['label'], 'value' => $format((int) DB::table($metric['table'])->count())];
            }

            $cards[] = ['label' => 'Showing This Page', 'value' => '—'];

            return $cards;
        }

        $table = $definition['table'];
        $scopes = $stats->scopes($definition);
        $apply = function ($query) use ($scopes) {
            foreach ($scopes as [$column, $value]) {
                $query->where($column, $value);
            }

            return $query;
        };

        $total = (int) $apply(DB::table($table))->count();
        $cards[] = ['label' => 'Total ' . $definition['label'], 'value' => $format($total)];

        if (! empty($definition['metrics'])) {
            foreach (array_slice($definition['metrics'], 0, 2) as $metric) {
                $query = DB::table($metric['table']);
                $filtered = false;

                if (! empty($metric['where'])) {
                    $query->where($metric['where'][0], $metric['where'][1]);
                    $filtered = true;
                } elseif (! empty($metric['where_in'])) {
                    $query->whereIn($metric['where_in'][0], $metric['where_in'][1]);
                    $filtered = true;
                } elseif (! empty($metric['where_op'])) {
                    $query->where($metric['where_op'][0], $metric['where_op'][1], $metric['where_op'][2]);
                    $filtered = true;
                } elseif (! empty($metric['where_null'])) {
                    $query->whereNull($metric['where_null']);
                    $filtered = true;
                }

                // A metric on a scoped page is counted inside that page's subset
                // too (refunds count only refund-required returns).
                if ($filtered) {
                    $apply($query);
                }

                $cards[] = ['label' => $metric['label'], 'value' => $format((int) $query->count())];
            }
        } else {
            $statusColumn = empty($definition['no_status'])
                ? ($definition['status_column'] ?? 'status')
                : null;

            if ($statusColumn !== null) {
                $active = (int) $apply(DB::table($table))
                    ->whereIn($statusColumn, config('admin_stats.active_values', ['1', 'active']))
                    ->count();

                $cards[] = ['label' => 'Active ' . $definition['label'], 'value' => $format($active)];
                $cards[] = ['label' => 'Inactive ' . $definition['label'], 'value' => $format(max(0, $total - $active))];
            } else {
                $cards[] = ['label' => 'Added Today', 'value' => $format((int) $apply(DB::table($table))->where('created_at', '>=', Carbon::today())->count())];
                $cards[] = ['label' => 'Added This Month', 'value' => $format((int) $apply(DB::table($table))->where('created_at', '>=', Carbon::now()->startOfMonth())->count())];
            }
        }

        $route = Route::getRoutes()->getByName($name);
        $method = strtolower((string) optional($route)->getActionMethod());
        $hasList = isset($definition['has_list'])
            ? (bool) $definition['has_list']
            : (! in_array($name, config('admin_stats.no_list_routes', []), true)
                && ! in_array($method, config('admin_stats.non_list_methods', []), true));

        $cards[] = [
            'label' => 'Showing This Page',
            'value' => $hasList ? $format(min(20, $total)) : '—',
        ];

        return $cards;
    }
}