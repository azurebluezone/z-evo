<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Productimage extends Model
{
    use HasFactory;

    // Product এর সাথে সম্পর্ক তৈরি করুন
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
