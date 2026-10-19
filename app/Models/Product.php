<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSeoMeta;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Product extends Model
{
    use HasFactory;
    use HasSeoMeta;

    /**
     * Explicit allow-list instead of `$guarded = []`.
     *
     * WHY THIS MATTERS HERE SPECIFICALLY
     * ----------------------------------
     * `$guarded = []` turns off Eloquent's mass-assignment protection entirely:
     * every key in the array handed to create()/update() is written to a column.
     * Both write paths in ProductController build that array by BLACKLIST:
     *
     *   store()  : $input = $request->except([...13 posted arrays/files...]);
     *   update() : $input = $request->except([...9 posted arrays/files...]);
     *
     * A blacklist is only as good as the author's memory of what to exclude. Any
     * field that was not thought of - including `id`, or any future column - is
     * accepted straight from the request body. With an allow-list, a field that
     * is not listed here is simply ignored, which is the safe direction to fail.
     *
     * The list below is the `products` table's own columns (verified against the
     * live schema), minus:
     *   - `id`              : never assignable from input
     *   - created_at/updated_at : managed by Eloquent
     *
     * `document` is included because the admin form genuinely uploads one; the
     * column was missing and is added by migration 2026_10_18_000008.
     */
    protected $fillable = [
        'name',
        'slug',
        'category_id',
        'subcategory_id',
        'childcategory_id',
        'brand_id',
        'product_code',
        'purchase_price',
        'old_price',
        'new_price',
        'stock',
        'pro_unit',
        'pro_video',
        'meta_description',
        'description',
        'document',
        'topsale',
        'feature_product',
        'is_recommended',
        'recommended_sort_order',
        'campaign_id',
        'status',
    ];

    protected $appends = [
        'thumbnail_image',
        'price',
        'brand_name',
        'sku',
    ];

    public function getRouteKeyName() {
        return 'slug';
    }

    public function image() {
        return $this->hasOne(Productimage::class, 'product_id')->select('id','image','product_id');
    }

    public function images() {
        return $this->hasMany(Productimage::class, 'product_id')->select('id','image','product_id','title');
    }

    public function reviews() {
        return $this->hasMany(Review::class, 'product_id')->select('id');
    }

    public function category() {
        return $this->hasOne(Category::class,'id','category_id')->select('id','name','slug');
    }

    public function subcategory() {
        return $this->hasOne(Subcategory::class,'id','subcategory_id')->select('id','subcategoryName','slug');
    }

    public function childcategory() {
        return $this->hasOne(Childcategory::class,'id','childcategory_id')->select('id','childcategoryName','slug');
    }

    public function brand() {
        return $this->hasOne(Brand::class,'id','brand_id')->select('id','name','slug');
    }

    public function sizes() {
        return $this->belongsToMany('App\Models\Size','productsizes')->withTimestamps();
    }

    public function colors() {
        return $this->belongsToMany('App\Models\Color','productcolors')->withTimestamps();
    }

    public function coupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class, 'coupon_products')->withTimestamps();
    }

    public function prosizes() {
        return $this->hasMany('App\Models\Productsize');
    }

    public function procolors() {
        return $this->hasMany('App\Models\Productcolor');
    }

    public function prosize() {
        return $this->hasOne(Productsize::class, 'product_id');
    }

    public function procolor() {
        return $this->hasOne(Productcolor::class, 'product_id');
    }

    // Check if product is in stock
    public function isInStock() {
        return $this->displayStockQuantity() > 0;
    }

    public function displayStockQuantity(): int
    {
        $productStock = (int) ($this->stock ?? 0);
        $productId = (int) ($this->id ?? 0);
        if ($productId <= 0) {
            return $productStock;
        }

        $hasVariants = DB::table('product_variants')
            ->where('product_id', $productId)
            ->exists();

        if (!$hasVariants) {
            return $productStock;
        }

        $productCode = (string) ($this->product_code ?? '');
        $defaultSku = ($productCode !== '' ? $productCode : ('P' . $productId)) . '-DEFAULT';

        $defaultVariantId = DB::table('product_variants')
            ->where('product_id', $productId)
            ->where('sku', $defaultSku)
            ->value('id');

        $variantId = $defaultVariantId;
        if (!$variantId) {
            $variantId = DB::table('product_variants')
                ->where('product_id', $productId)
                ->orderBy('id', 'asc')
                ->value('id');
        }

        if (!$variantId) {
            return $productStock;
        }

        $available = DB::table('variant_stocks')
            ->where('variant_id', (int) $variantId)
            ->value('available');

        if ($available === null) {
            return $productStock;
        }

        $reserved = 0;
        if (Schema::hasTable('inventory_reservations')) {
            $reserved = (int) DB::table('inventory_reservations')
                ->where('variant_id', (int) $variantId)
                ->where('expires_at', '>', now())
                ->sum('qty');
        }

        return (int) max(0, (int) $available - (int) $reserved);
    }

    public function getThumbnailImageAttribute()
    {
        $img = $this->relationLoaded('image') ? ($this->image?->image ?? null) : ($this->image()->first()?->image ?? null);
        $img = preg_replace('~^public/~', '', (string) $img);
        $img = ltrim($img, '/');
        return $img !== '' ? $img : 'assets/images/default.png';
    }

    public function getPriceAttribute()
    {
        return (string) ($this->new_price ?? '');
    }

    public function getBrandNameAttribute()
    {
        $name = $this->relationLoaded('brand') ? ($this->brand?->name ?? null) : ($this->brand()->first()?->name ?? null);
        return $name ?: 'Non Brand';
    }

    public function getSkuAttribute()
    {
        return (string) ($this->product_code ?? '');
    }
}
