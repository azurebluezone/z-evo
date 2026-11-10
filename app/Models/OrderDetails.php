<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetails extends Model
{
    use HasFactory;

    protected $casts = [
        'campaign_id' => 'integer',
        'regular_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    public function image()
    {
        return $this->belongsTo(Productimage::class, 'product_id', 'product_id')->select('id','product_id','image');
    }
    public function shipping(){
        return $this->belongsTo(Shipping::class, 'order_id','order_id')->select('id','order_id','name','phone','address');
    }
    public function order(){
        return $this->belongsTo(Order::class, 'order_id')->select('id','invoice_id');
    }
}
