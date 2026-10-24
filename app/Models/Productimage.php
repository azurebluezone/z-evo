<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Productimage extends Model
{
    use HasFactory;

    /**
     * Allow-list instead of `$guarded = []`.
     *
     * The admin uploader assigns these properties directly, but the gallery
     * seeder (products:gallery) uses create(). Keeping the list explicit means
     * a request-derived array can never reach product_id or id by accident.
     */
    protected $fillable = [
        'image',
        'title',
        'product_id',
    ];

    // Product এর সাথে সম্পর্ক তৈরি করুন
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
