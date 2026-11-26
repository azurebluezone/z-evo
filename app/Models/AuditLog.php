<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One audited action.
 *
 * The table is append-only by convention: the app never updates or deletes a
 * row, so this model has no update path either.
 */
class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'actor_name',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'note',
        'before',
        'after',
        'source',
        'ip',
    ];

    protected $casts = [
        'user_id'   => 'integer',
        'before'    => 'array',
        'after'     => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Everything that happened to one thing (an order, a variant, a courier). */
    public function scopeForSubject($query, string $type, $id)
    {
        return $query->where('subject_type', $type)->where('subject_id', (string) $id);
    }

    public function scopeOfAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * The action without its namespace, for grouping: order.note.updated ->
     * order.
     */
    public function scopeGrouped($query)
    {
        return $query->selectRaw('SUBSTRING_INDEX(action, ".", 1) as group_name, COUNT(*) as total')
            ->groupBy('group_name');
    }
}
