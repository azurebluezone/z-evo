<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row of report configuration.
 *
 * `current()` is the only way the app reads it: it creates the singleton row
 * with the documented defaults the first time, so a missing row can never
 * make a page or a scheduled job fail.
 */
class ReportSetting extends Model
{
    protected $fillable = [
        'stock_warning_threshold',
        'stock_remaining_threshold',
        'stock_alert_dismissed_signature',
        'report_email',
        'auto_send_enabled',
        'send_time',
        'last_sent_on',
    ];

    protected $casts = [
        'auto_send_enabled' => 'boolean',
        'last_sent_on' => 'date',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'stock_warning_threshold' => 0,
            'stock_remaining_threshold' => 2,
            'auto_send_enabled' => false,
            'send_time' => '09:00:00',
        ]);
    }

    public function sendTimeHm(): string
    {
        return substr((string) $this->send_time, 0, 5);
    }
}
