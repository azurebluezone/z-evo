<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row = one customer wishlisted one product.
 *
 * Written by the add-to-wishlist/remove-from-wishlist route closures in
 * routes/web.php when the `customer` guard is logged in; guests keep the
 * session fallback. Read by Admin\WishlistController for admin/wishlist.
 */
class Wishlist extends Model
{
    /**
     * Allow-list instead of `$guarded = []` - the route closures feed a
     * request-derived array into firstOrCreate().
     */
    protected $fillable = [
        'customer_id',
        'product_id',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
