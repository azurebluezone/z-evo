<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomepageCollection extends Model
{
    use HasFactory;

        /**
     * Allow-list instead of `$guarded = []`.
     * This controller fed a request-derived array into create()/update();
     * with an open guard any posted key - including `id` - reached a column.
     *
     * Set from the live `HomepageCollection` schema: every writable column
     * except id/created_at/updated_at.
     */
    protected $fillable = [
        'title',
        'category_id',
        'banner_image',
        'product_limit',
        'sort_order',
        'status',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
