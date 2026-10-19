<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Courier API credentials, one row per courier.
 *
 * ---------------------------------------------------------------------------
 * WHY $table IS DECLARED EXPLICITLY
 * ---------------------------------------------------------------------------
 * Laravel derives a table name from the class by snake_casing it and
 * pluralising: Courierapi -> "courierapi" -> "courierapis".
 *
 * That name does not exist, and it never did. There was no migration for this
 * table at all, so every screen that reads courier credentials died on its
 * first query. Worse, the two controllers that probe the schema defensively
 * asked for the wrong name too:
 *
 *     OrderController                Schema::hasColumn('courierapis', 'status')   x7
 *     ShippingVerificationController Schema::hasColumn('courierapis', 'status')
 *                                     Schema::hasColumn('courierapis', 'title')
 *
 * Because those are wrapped in hasColumn(), they returned false and the code
 * silently carried on without filtering to enabled couriers - a quiet bug
 * rather than a visible one. They have been corrected to 'courier_apis'.
 *
 * The table name is therefore stated here rather than inferred, so the model,
 * the migration and the raw Schema::hasColumn() calls can never drift apart
 * again.
 *
 * Migration: 2026_10_18_000005_create_courier_apis_table
 */
class Courierapi extends Model
{
    use HasFactory;

    protected $table = 'courier_apis';

        /**
     * Allow-list instead of `$guarded = []`.
     * This controller fed a request-derived array into create()/update();
     * with an open guard any posted key - including `id` - reached a column.
     *
     * Set from the live `Courierapi` schema: every writable column
     * except id/created_at/updated_at and ['webhook_token'].
     */
    protected $fillable = [
        'type',
        'title',
        'url',
        'api_key',
        'secret_key',
        'client_id',
        'client_secret',
        'username',
        'password',
        'token',
        'status',
    ];

    /**
     * The bearer token our webhook endpoint requires for this courier.
     *
     * Read through an accessor rather than exposing it in toArray()/toJson():
     * these rows are the sort of thing that ends up in a debug dump or an API
     * response, and a webhook secret leaking that way is a silent way to lose
     * the protection. Access it explicitly ->webhook_token.
     */
    protected $hidden = ['webhook_token'];

    /** Credentials that must all be present before a courier can be enabled. */
    public function isConfigured(): bool
    {
        return match ($this->type) {
            'steadfast' => filled($this->url) && filled($this->api_key) && filled($this->secret_key),

            'pathao' => filled($this->url)
                && filled($this->client_id)
                && filled($this->client_secret)
                && filled($this->username)
                && filled($this->password),

            default => false,
        };
    }

    /**
     * Ready to send AND switched on.
     *
     * Status alone is not enough: a courier can be ticked on before its
     * credentials are filled in, and every API call would then fail with a
     * confusing upstream error.
     */
    public function isReady(): bool
    {
        return (bool) $this->status && $this->isConfigured();
    }

    /** Label for admin dropdowns, falling back to the slug. */
    public function label(): string
    {
        return (string) ($this->title ?: ucfirst((string) $this->type));
    }
}
