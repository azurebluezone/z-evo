<?php

namespace App\Services\Admin;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * The four cards in the admin header.
 *
 * SAME TEMPLATE, DIFFERENT CONTENT
 * --------------------------------
 * The cards look identical on every page, but the numbers describe the page
 * the admin is on: /admin/coupon counts coupons, /admin/courier counts
 * courier accounts, /admin/customer counts customers. Copying the customer
 * trio onto every screen would be one template pretending to be data.
 *
 * The module map lives in config/admin_stats.php; this class only knows how
 * to turn a definition into numbers:
 *
 *   card 1  Total <module>
 *   card 2  Active <module>  (or Added today when the table has no status)
 *   card 3  Inactive <module> (or Added this month)
 *   card 4  Showing this page - rows the grid is displaying now
 *
 * Card 4 is refreshed by admin-datatable.js after every draw, because the
 * header is rendered before the table has fetched anything; the server value
 * is the honest initial page size, not a guess dressed as a total.
 */
class PageStats
{
    /** @var array<string,int|null> */
    private array $counts = [];

    /**
     * @return array{show:bool,cards:array<int,array{label:string,value:?int,icon:string,tone:string,hint:?string}>}
     */
    public function cards(?Request $request = null): array
    {
        $request = $request ?: request();

        if (! $this->shouldRender($request)) {
            return ['show' => false, 'cards' => []];
        }

        $definition = $this->definition($request);
        $tones = ['primary', 'success', 'warning'];

        if ($definition === null) {
            $cards = [];
            foreach (config('admin_stats.overview', []) as $index => $metric) {
                $cards[] = $this->card(
                    'Total ' . $metric['label'],
                    $metric['icon'],
                    $this->count($metric['table']),
                    $tones[$index] ?? 'primary'
                );
            }
            $cards[] = $this->pageCard();

            return ['show' => true, 'cards' => $cards];
        }

        $table = $definition['table'];

        /*
         * A page can describe a SUBSET of a table: recommended products,
         * pending reviews, blocked visitors, refunds that need money back.
         * where / where_all scope every number on the card, so the header
         * never counts rows the page is not showing.
         */
        $scopes = $this->scopes($definition);
        $total = $this->countScoped($table, $scopes);

        $cards = [
            $this->card('Total ' . $definition['label'], $definition['icon'], $total, 'primary'),
        ];

        if (! empty($definition['metrics'])) {
            foreach (array_slice($definition['metrics'], 0, 2) as $index => $metric) {
                $cards[] = $this->card(
                    $metric['label'],
                    $metric['icon'] ?? $definition['icon'],
                    $this->metricCount($metric, $scopes),
                    $tones[$index + 1] ?? 'success'
                );
            }
        } else {
            // Most tables use `status`. A handful have no status concept at
            // all and say so with no_status, so this does not guess a column
            // that does not exist.
            $statusColumn = empty($definition['no_status'])
                ? ($definition['status_column'] ?? 'status')
                : null;

            if ($statusColumn !== null) {
                $active = $this->countWhereInScoped(
                    $table,
                    $statusColumn,
                    config('admin_stats.active_values', ['1', 'active']),
                    $scopes
                );

                $cards[] = $this->card('Active ' . $definition['label'], 'ri-checkbox-circle-line', $active, 'success');
                $cards[] = $this->card(
                    'Inactive ' . $definition['label'],
                    'ri-close-circle-line',
                    $active === null ? null : max(0, $total - $active),
                    'warning'
                );
            } else {
                $cards[] = $this->card(
                    'Added Today',
                    'ri-calendar-check-line',
                    $this->countSinceScoped($table, Carbon::today(), $scopes),
                    'success'
                );
                $cards[] = $this->card(
                    'Added This Month',
                    'ri-calendar-line',
                    $this->countSinceScoped($table, Carbon::now()->startOfMonth(), $scopes),
                    'warning'
                );
            }
        }

        $cards[] = $this->pageCard($total, $this->hasList($request, $definition));

        return ['show' => true, 'cards' => $cards];
    }

    public function shouldRender(Request $request): bool
    {
        $name = optional($request->route())->getName();

        if ($name !== null && in_array($name, config('admin_stats.exclude', []), true)) {
            return false;
        }

        // Only the admin area gets this header; the storefront has its own
        // layout and must not acquire admin queries on every page view.
        return $request->segment(1) === 'admin';
    }

    /**
     * @return array<string,mixed>|null
     */
    private function definition(Request $request): ?array
    {
        return $this->definitionFor(optional($request->route())->getName(), $request->segment(2));
    }

    /**
     * Public so the audit command can ask the same question the renderer asks.
     * One resolver, no second guess at which table a page describes.
     *
     * @return array<string,mixed>|null
     */
    public function definitionFor(?string $name, ?string $segment): ?array
    {
        /*
         * Route names contain dots ("admin.courier.shipments"), so they can
         * never be fetched as config('admin_stats.routes.'.$name): dot
         * notation would walk into nested arrays. Read the map as an array
         * and look the name up literally.
         */
        $routes = config('admin_stats.routes', []);
        $definition = (is_array($routes) && $name !== null ? ($routes[$name] ?? null) : null)
            ?? config('admin_stats.modules.' . $segment);

        return is_array($definition) && ! empty($definition['table']) ? $definition : null;
    }

    /**
     * @param  array<string,mixed>  $definition
     * @return array<int,array{0:string,1:mixed}>
     */
    public function scopes(array $definition): array
    {
        $scopes = [];

        if (! empty($definition['where'])) {
            $scopes[] = $definition['where'];
        }

        foreach ($definition['where_all'] ?? [] as $scope) {
            $scopes[] = $scope;
        }

        return $scopes;
    }

    /**
     * One metric can be scoped to the page's own subset as well, so
     * "Refunded" on the refunds page does not count refunds on other pages.
     *
     * @param  array<string,mixed>  $metric
     * @param  array<int,array{0:string,1:mixed}>  $pageScopes
     */
    private function metricCount(array $metric, array $pageScopes = []): ?int
    {
        $table = $metric['table'];

        if (! empty($metric['where'])) {
            [$column, $value] = $metric['where'];

            return $this->countScoped($table, array_merge($pageScopes, [[$column, $value]]));
        }

        if (! empty($metric['where_in'])) {
            [$column, $values] = $metric['where_in'];

            return $this->countWhereInScoped($table, $column, $values, $pageScopes);
        }

        if (! empty($metric['where_op'])) {
            [$column, $operator, $value] = $metric['where_op'];

            return $this->countWhereOp($table, $column, $operator, $value, $pageScopes);
        }

        if (! empty($metric['where_null'])) {
            return $this->countWhereNullScoped($table, $metric['where_null'], $pageScopes);
        }

        return $this->countScoped($table, $pageScopes);
    }

    /**
     * @return array{label:string,value:?int,icon:string,tone:string,hint:?string}
     */
    private function card(string $label, string $icon, ?int $value, string $tone, ?string $hint = null): array
    {
        return compact('label', 'value', 'icon', 'tone', 'hint');
    }

    /**
     * Whether this screen is a list. "Showing This Page" is only honest where
     * a table is actually displaying rows; on a create/edit form the number
     * would describe a list that is not on the screen.
     *
     * @param  array<string,mixed>|null  $definition
     */
    public function hasList(Request $request, ?array $definition = null): bool
    {
        if (isset($definition['has_list'])) {
            return (bool) $definition['has_list'];
        }

        $name = optional($request->route())->getName();

        if ($name !== null && in_array($name, config('admin_stats.no_list_routes', []), true)) {
            return false;
        }

        $method = strtolower((string) optional($request->route())->getActionMethod());

        // A form is not a list. Anything else is treated as a list by default:
        // admin controllers name their list actions freely (index, manage,
        // shipments, pending, blocked, order_report), so listing the non-list
        // actions is both shorter and less likely to miss one.
        return ! in_array($method, config('admin_stats.non_list_methods', []), true);
    }

    /**
     * The fourth card. `null` means "this page has no list", which the partial
     * renders as an em dash - never as zero.
     *
     * @return array{label:string,value:?int,icon:string,tone:string,hint:?string}
     */
    private function pageCard(?int $moduleTotal = null, bool $hasList = true): array
    {
        $value = (! $hasList) ? null : ($moduleTotal === null ? null : min(20, $moduleTotal));

        return $this->card(
            'Showing This Page',
            'ri-file-list-line',
            $value,
            'info',
            $hasList
                ? 'Rows the table is displaying. The list refreshes this after every search.'
                : 'This page has no list, so there is no page count to show.'
        );
    }

    public function count(string $table): ?int
    {
        return $this->memo($table . '|all', function () use ($table) {
            return (int) DB::table($table)->count();
        });
    }

    /**
     * @param  array<int,array{0:string,1:mixed}>  $scopes
     */
    private function countScoped(string $table, array $scopes): ?int
    {
        return $this->memo($table . '|scoped|' . $this->scopeKey($scopes), function () use ($table, $scopes) {
            return (int) $this->applyScopes(DB::table($table), $scopes)->count();
        });
    }

    /**
     * @param  array<int,mixed>  $values
     * @param  array<int,array{0:string,1:mixed}>  $scopes
     */
    private function countWhereInScoped(string $table, string $column, array $values, array $scopes = []): ?int
    {
        $key = $table . '|' . $column . '|in|' . implode(',', $values) . '|' . $this->scopeKey($scopes);

        return $this->memo($key, function () use ($table, $column, $values, $scopes) {
            return (int) $this->applyScopes(DB::table($table), $scopes)
                ->whereIn($column, $values)
                ->count();
        });
    }

    /**
     * @param  array<int,array{0:string,1:mixed}>  $scopes
     */
    private function countWhereOp(string $table, string $column, string $operator, mixed $value, array $scopes = []): ?int
    {
        $key = $table . '|' . $column . '|' . $operator . '|' . (string) $value . '|' . $this->scopeKey($scopes);

        return $this->memo($key, function () use ($table, $column, $operator, $value, $scopes) {
            return (int) $this->applyScopes(DB::table($table), $scopes)
                ->where($column, $operator, $value)
                ->count();
        });
    }

    /**
     * @param  array<int,array{0:string,1:mixed}>  $scopes
     */
    private function countWhereNullScoped(string $table, string $column, array $scopes = []): ?int
    {
        $key = $table . '|' . $column . '|null|' . $this->scopeKey($scopes);

        return $this->memo($key, function () use ($table, $column, $scopes) {
            return (int) $this->applyScopes(DB::table($table), $scopes)
                ->whereNull($column)
                ->count();
        });
    }

    /**
     * @param  array<int,array{0:string,1:mixed}>  $scopes
     */
    private function countSinceScoped(string $table, Carbon $since, array $scopes = []): ?int
    {
        $key = $table . '|since|' . $since->toDateString() . '|' . $this->scopeKey($scopes);

        return $this->memo($key, function () use ($table, $since, $scopes) {
            return (int) $this->applyScopes(DB::table($table), $scopes)
                ->where('created_at', '>=', $since)
                ->count();
        });
    }

    /**
     * @param  array<int,array{0:string,1:mixed}>  $scopes
     */
    private function applyScopes(Builder $query, array $scopes): Builder
    {
        foreach ($scopes as [$column, $value]) {
            $query->where($column, $value);
        }

        return $query;
    }

    /**
     * @param  array<int,array{0:string,1:mixed}>  $scopes
     */
    private function scopeKey(array $scopes): string
    {
        return implode('&', array_map(
            static fn (array $scope): string => $scope[0] . '=' . (string) $scope[1],
            $scopes
        ));
    }

    /**
     * One query per distinct number per request. A missing table or column is
     * reported as "no count" (null) - the partial prints an em dash, which is
     * a different statement from "zero rows".
     */
    private function memo(string $key, callable $query): ?int
    {
        if (! array_key_exists($key, $this->counts)) {
            try {
                $this->counts[$key] = $query();
            } catch (Throwable $e) {
                $this->counts[$key] = null;
            }
        }

        return $this->counts[$key];
    }
}
