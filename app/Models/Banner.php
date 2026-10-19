<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSeoMeta;

class Banner extends Model
{
    use HasFactory;
    use HasSeoMeta;
        /**
     * Allow-list instead of `$guarded = []`.
     * The controller fed a request-derived array into create()/update();
     * with an open guard any posted key - including `id` - reached a column.
     *
     * Columns: every writable one except id/created_at/updated_at.
     * Supersedes the narrower `$fillable` that used to sit alongside the
     * open `$guarded = []` (with both present, guard wins and nothing was
     * actually protected).
     */
    protected $fillable = [
        'category_id',
        'link',
        'image',
        'video',
        'title',
        'media_type',
        'status',
    ];
    
    public function category()
    {
        return $this->hasOne(BannerCategory::class,'id','category_id')->select('id','name');
    }
}
