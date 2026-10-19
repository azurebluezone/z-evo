<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;
        /**
     * Allow-list instead of `$guarded = []`.
     * This controller fed a request-derived array into create()/update();
     * with an open guard any posted key - including `id` - reached a column.
     *
     * Set from the live `Review` schema: every writable column
     * except id/created_at/updated_at.
     */
    protected $fillable = [
        'name',
        'email',
        'ratting',
        'review',
        'product_id',
        'customer_id',
        'status',
    ];

    /**
     * The product the review belongs to.
     *
     * The home page testimonials query eager loads "product", so this
     * relation must exist (same reason as customer() above).
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * The customer account behind the review.
     *
     * The product page eager loads "reviews.customer", so this relation must
     * exist: without it the first product that carries an active review
     * throws a RelationNotFoundException. name/email on the row itself are
     * legacy copies kept for display only.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
