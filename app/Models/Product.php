<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasSeoMeta;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Product extends Model
{
    use HasFactory;
    use HasSeoMeta;

    /**
     * Soft-deleted products stay in `products` so order history, returns and
     * inventory rows keep resolving their product_id. Eloquent excludes them
     * from normal queries; use withTrashed() when history needs them.
     */
    use SoftDeletes;

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
        // Storefront listings can batch this value onto the model once for a
        // whole page (FrontendController::index), so a grid does not run one
        // stock query per card.
        if (array_key_exists('available_to_promise', $this->attributes)) {
            return (int) $this->attributes['available_to_promise'] > 0;
        }

        return $this->displayStockQuantity() > 0;
    }

    /**
     * Does this product have at least one active variant?
     *
     * A variant product must be opened on its detail page so the shopper can
     * pick a size/colour; a direct card-level add would silently buy whichever
     * variant happens to be the default.
     */
    public function hasVariants(): bool
    {
        if (array_key_exists('has_variants', $this->attributes)) {
            return (bool) $this->attributes['has_variants'];
        }

        $productId = (int) ($this->id ?? 0);
        if ($productId <= 0) {
            return false;
        }

        return DB::table('product_variants')
            ->where('product_id', $productId)
            ->where('status', 1)
            ->exists();
    }

    /**
     * Sellable units across EVERY active variant.
     *
     * The old implementation read only the DEFAULT variant. A product whose
     * default (first) variant was sold out was therefore reported out of stock
     * on the detail page even when its other sizes were on the shelf, and the
     * add-to-cart block disappeared. Availability is the sum of what any
     * variant can sell, minus the live checkout holds.
     */
    public function displayStockQuantity(): int
    {
        $productId = (int) ($this->id ?? 0);
        if ($productId <= 0) {
            return 0;
        }

        if (array_key_exists('available_to_promise', $this->attributes)) {
            return max(0, (int) $this->attributes['available_to_promise']);
        }

        $row = DB::table('product_variants as v')
            ->leftJoin('variant_stocks as s', 's.variant_id', '=', 'v.id')
            ->where('v.product_id', $productId)
            ->where('v.status', 1)
            ->selectRaw('COUNT(*) as variant_count')
            ->selectRaw('COALESCE(SUM(GREATEST(COALESCE(s.available, 0) - COALESCE(s.reserved, 0), 0)), 0) as qty')
            ->first();

        if (!$row || (int) $row->variant_count === 0) {
            return 0;
        }

        $available = (int) $row->qty;

        if (Schema::hasTable('inventory_reservations')) {
            $variantIds = DB::table('product_variants')
                ->where('product_id', $productId)
                ->where('status', 1)
                ->select('id');

            $reserved = (int) DB::table('inventory_reservations')
                ->whereIn('variant_id', $variantIds)
                ->where('expires_at', '>', now())
                ->sum('qty');

            $available -= $reserved;
        }

        return max(0, $available);
    }

    /**
     * Physical units on hand across active variants.
     *
     * This is the admin-facing figure: on_hand counts every unit in the
     * warehouse, including damaged ones, and ignores checkout holds. It is the
     * replacement for the dropped legacy product stock column.
     */
    public function stockOnHand(): int
    {
        $productId = (int) ($this->id ?? 0);
        if ($productId <= 0) {
            return 0;
        }

        if (array_key_exists('stock_on_hand', $this->attributes)) {
            return max(0, (int) $this->attributes['stock_on_hand']);
        }

        $sum = DB::table('product_variants as v')
            ->join('variant_stocks as s', 's.variant_id', '=', 'v.id')
            ->where('v.product_id', $productId)
            ->where('v.status', 1)
            ->sum('s.on_hand');

        return max(0, (int) $sum);
    }

    public function getThumbnailImageAttribute()
    {
        $img = $this->relationLoaded('image') ? ($this->image?->image ?? null) : ($this->image()->first()?->image ?? null);
        $img = preg_replace('~^public/~', '', (string) $img);
        $img = ltrim($img, '/');
        return $img !== '' ? $img : \App\Support\MediaPath::fallbackPath();
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
