<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One bucket for business expenses (ads, packing, salary, rent, ...).
 *
 * The category is a label, not a ledger: deleting one is blocked while it
 * still has expenses, so a historical spend can never lose its meaning.
 */
class ExpenseCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function expenses()
    {
        return $this->hasMany(BusinessExpense::class);
    }
}
