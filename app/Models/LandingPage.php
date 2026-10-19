<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Traits\HasSeoMeta;

class LandingPage extends Model
{
    use HasFactory;
    use HasSeoMeta;
    
        /**
     * Allow-list instead of `$guarded = []`.
     * This controller fed a request-derived array into create()/update();
     * with an open guard any posted key - including `id` - reached a column.
     *
     * Set from the live `LandingPage` schema: every writable column
     * except id/created_at/updated_at.
     */
    protected $fillable = [
        'title',
        'subtitle',
        'description',
        'cta_text',
        'cta_link',
        'image_url',
        'extra_fields',
        'template_id',
        'slug',
        'status',
        'sort_order',
    ];
    
    protected $casts = [
        'status' => 'boolean',
        'extra_fields' => 'array',
    ];

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    // Scope for active landing pages
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    // Auto-generate slug
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($landingPage) {
            if (empty($landingPage->slug)) {
                $landingPage->slug = Str::slug($landingPage->title);
            }
        });
    }
}
