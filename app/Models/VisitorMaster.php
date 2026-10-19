<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorMaster extends Model
{
    use HasFactory;

    protected $table = 'visitors_master';

    protected $guarded = [];
}
