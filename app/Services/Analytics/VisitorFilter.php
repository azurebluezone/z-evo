<?php

namespace App\Services\Analytics;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * The visitor filter contract shared by the analytics page and the visitor list.
 *
 * One date input means "that exact day". Two inputs mean a range. The order of
 * the two inputs is irrelevant - a reversed range is swapped instead of
 * returning zero rows, which is the mistake the old `>= from` / `<= to` pair
 * made when only `from` was filled in.
 */
class VisitorFilter
{
    /**
     * Columns the admin can search one by one.
     *
     * `agent` is accepted as an alias for `user_agent` because that is the word
     * the visitor grid uses for the browser string.
     *
     * @var array<int, string>
     */
    public const TEXT_COLUMNS = [
        'visitor_id',
        'ip',
        'country',
        'city',
        'division',
        'district',
        'isp',
        'user_agent',
        'browser',
        'device_type',
        'os',
        'channel',
    ];

    /**
     * @param  Builder<\App\Models\VisitorMaster>  $query
     * @return Builder<\App\Models\VisitorMaster>
     */
    public function apply(Builder $query, Request $request, bool $withDates = true): Builder
    {
        foreach (self::TEXT_COLUMNS as $column) {
            $value = $request->query($column);

            if ($column === 'user_agent' && (!is_string($value) || trim($value) === '')) {
                $value = $request->query('agent');
            }

            if (is_string($value) && trim($value) !== '') {
                $query->where($column, 'like', '%' . trim($value) . '%');
            }
        }

        $status = $request->query('status');
        if ($status === '1' || $status === '0') {
            $query->where('status', (int) $status);
        }

        if (!$withDates) {
            return $query;
        }

        [$from, $to] = $this->dates($request);

        if ($from !== null && $to !== null) {
            if ($from->isSameDay($to)) {
                $query->whereDate('created_at', $from->toDateString());
            } else {
                $query->whereDate('created_at', '>=', $from->toDateString())
                    ->whereDate('created_at', '<=', $to->toDateString());
            }
        } elseif ($from !== null) {
            $query->whereDate('created_at', $from->toDateString());
        } elseif ($to !== null) {
            $query->whereDate('created_at', $to->toDateString());
        }

        return $query;
    }

    /**
     * @return array{0:?Carbon,1:?Carbon}
     */
    public function dates(Request $request): array
    {
        $from = $this->date($request->query('from'));
        $to = $this->date($request->query('to'));

        if ($from !== null && $to !== null && $from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    private function date($value): ?Carbon
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable $e) {
            // A syntactically shaped but impossible date (2026-02-31) must be
            // ignored, not turned into a 500 on the admin page.
            return null;
        }

        return $date !== null && $date->format('Y-m-d') === $value ? $date : null;
    }
}
