<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The single row behind the public cookie page.
 *
 * `body` is admin-authored HTML and is rendered unescaped, exactly like
 * CreatePage::description. The retention sentence is NOT a column here; it is
 * App\Support\Consent::RETENTION_NOTICE and every render path prints it from
 * code, so a database edit can never remove it.
 */
class CookiePage extends Model
{
    protected $fillable = [
        'title',
        'intro',
        'body',
        'updated_by',
    ];

    /**
     * Defaults used on the first visit and by the admin screen when no row
     * exists yet. They match the copy the page shipped with, so enabling the
     * editor does not silently blank the page.
     *
     * @return array{title: string, intro: string, body: string}
     */
    public static function defaults(): array
    {
        return [
            'title' => 'Cookies, in plain language',
            'intro' => 'Necessary cookies keep the cart, the session and your login working. Everything else is your call, and changing it takes one click.',
            'body' => '<p>This page explains the cookies this shop sets and what each one is for. The categories below are tied to the code that actually runs: necessary storage keeps the cart and login working, analytics forwards funnel steps only after you allow it, and marketing loads the Meta and TikTok pixels only after you allow it.</p>',
        ];
    }

    /**
     * The row the public page reads. Never writes on a GET request: an empty
     * table simply falls back to the defaults, so a fresh install still renders
     * a complete page before an admin saves anything.
     */
    public static function current(): self
    {
        return static::query()->first() ?? new static(static::defaults());
    }

    /**
     * The row the admin screen edits, created on first open so saving works.
     */
    public static function forEditing(): self
    {
        return static::query()->firstOrCreate([], static::defaults());
    }
}
