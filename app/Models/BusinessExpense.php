<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One row per taka that leaves the business.
 *
 * spent_at is a date, not a timestamp: an expense belongs to the day it was
 * spent. The report service sums this table by that date, so an entry made
 * today changes the dashboard Expense and Estimated Net Profit immediately.
 */
class BusinessExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_category_id',
        'amount',
        'spent_at',
        'payment_method',
        'note',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'spent_at' => 'date',
    ];

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
