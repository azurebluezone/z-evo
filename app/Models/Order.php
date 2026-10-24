<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * `risk_last_checked_at` is a timestamp column added by
     * 2026_03_02_000001_add_risk_fields_to_orders_table.php, but without a
     * cast Eloquent hands it back as a raw string. backEnd/order/_row.blade.php
     * calls ->format() on it, so a scored order crashed the DataTables feed
     * with "Call to a member function format() on string" and the Pending tab
     * showed DataTables warning tn/7 (ajax error).
     */
    protected $casts = [
        'risk_last_checked_at' => 'datetime',
        'risk_score_percent'   => 'integer',
    ];

    public function courierShipments()
    {
        return $this->hasMany(CourierShipment::class, 'order_id');
    }

    public function orderdetails()
    {
        return $this->hasMany(OrderDetails::class, 'order_id');
    }
    public function product()
    {
        return $this->belongsTo(OrderDetails::class, 'id', 'order_id')->select('id','order_id','product_id');
    }
    public function status()
    {
        return $this->belongsTo(OrderStatus::class, 'order_status');
    }
    public function shipping()
    {
        return $this->belongsTo(Shipping::class, 'id', 'order_id');
    }
    public function payment()
    {
        return $this->belongsTo(Payment::class, 'id', 'order_id');
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class,'customer_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
