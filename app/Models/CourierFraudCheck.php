<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourierFraudCheck extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'raw_payload' => 'array',
        'checked_at' => 'datetime',
    ];
}
