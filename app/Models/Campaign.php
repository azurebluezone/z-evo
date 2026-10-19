<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSeoMeta;

class Campaign extends Model
{
    use HasFactory;
    use HasSeoMeta;
        /**
     * Allow-list instead of `$guarded = []`.
     * This controller fed a request-derived array into create()/update();
     * with an open guard any posted key - including `id` - reached a column.
     *
     * Set from the live `Campaign` schema: every writable column
     * except id/created_at/updated_at.
     */
    protected $fillable = [
        'name',
        'slug',
        'date',
        'short_description',
        'review',
        'description',
        'hero_title',
        'hero_subtitle',
        'hero_button_text',
        'hero_button_link',
        'features_title',
        'features_subtitle',
        'benefits_title',
        'benefits_subtitle',
        'testimonials_title',
        'testimonials_subtitle',
        'faq_title',
        'faq_subtitle',
        'cta_title',
        'cta_subtitle',
        'cta_button_text',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'custom_css',
        'custom_js',
        'show_features',
        'show_benefits',
        'show_testimonials',
        'show_faq',
        'show_cta',
        'campaign_type',
        'start_date',
        'end_date',
        'is_featured',
        'sort_order',
        'image_one',
        'image_two',
        'image_three',
        'status',
        'template_id',
    ];

    protected $casts = [
        'show_features' => 'boolean',
        'show_benefits' => 'boolean',
        'show_testimonials' => 'boolean',
        'show_faq' => 'boolean',
        'show_cta' => 'boolean',
        'is_featured' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id')->select('id', 'name', 'slug', 'old_price', 'new_price', 'status', 'purchase_price');
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }
    public function images()
    {
        return $this->hasMany(CampaignReview::class, 'campaign_id')->select('id', 'image', 'campaign_id');
    }

    // Scope for active campaigns
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    // Scope for featured campaigns
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // Scope for campaigns within date range
    public function scopeWithinDateRange($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('start_date')
                ->orWhere('start_date', '<=', now());
        })->where(function ($q) {
            $q->whereNull('end_date')
                ->orWhere('end_date', '>=', now());
        });
    }

    // Get campaign status
    public function getStatusTextAttribute()
    {
        if ($this->status == 1) {
            if ($this->start_date && $this->start_date > now()) {
                return 'Upcoming';
            } elseif ($this->end_date && $this->end_date < now()) {
                return 'Expired';
            } else {
                return 'Active';
            }
        }
        return 'Inactive';
    }

    // Get campaign type text
    public function getTypeTextAttribute()
    {
        return ucfirst($this->campaign_type);
    }
}
