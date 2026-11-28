<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * A named filter set for one admin report page.
 *
 * The row stores the report page key plus the query string that page already
 * understands, so restoring a view is a plain link and the report keeps a
 * single source of truth: the URL. A view is private to its owner unless it is
 * marked shared; a shared view survives its owner because user_id is nullable
 * and set to null on delete.
 */
class ReportView extends Model
{
    /**
     * The report pages that accept a saved view, key => route name.
     */
    public const ROUTES = [
        'overview' => 'admin.reports.overview',
        'payments' => 'admin.reports.payments',
        'couriers' => 'admin.reports.couriers',
        'returns' => 'admin.reports.returns',
        'coupons' => 'admin.reports.coupons',
    ];

    protected $fillable = [
        'user_id',
        'view_key',
        'name',
        'filters',
        'is_shared',
    ];

    protected $casts = [
        'filters' => 'array',
        'is_shared' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Route name this view restores.
     */
    public function routeName(): string
    {
        return self::ROUTES[$this->view_key] ?? self::ROUTES['overview'];
    }

    /**
     * The filters as a query array in a fixed order.
     *
     * MySQL stores a JSON object with its keys reordered, so reading filters
     * back would otherwise build a different query string than the one that was
     * saved. Restoring a view has to reproduce the same URL, so the order is
     * normalised here and every caller uses this helper.
     *
     * @return array<string, string>
     */
    public function filterQuery(): array
    {
        $filters = is_array($this->filters) ? $this->filters : [];

        return array_filter([
            'start_date' => $filters['start_date'] ?? null,
            'end_date' => $filters['end_date'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * The views a report reader may pick from: every shared view plus their
     * own, in name order. Nothing else leaks across accounts.
     *
     * @return Collection<int, self>
     */
    public static function forPage(string $viewKey, ?int $userId): Collection
    {
        return static::query()
            ->where('view_key', $viewKey)
            ->where(function ($query) use ($userId) {
                $query->where('is_shared', true);

                if ($userId !== null) {
                    $query->orWhere('user_id', $userId);
                }
            })
            ->orderBy('name')
            ->get();
    }
}
