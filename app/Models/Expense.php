<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $table = 'expenses';
    protected $guarded = [];
    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
        'payment_mode' => 'integer',
        'status' => 'integer',
        'deleted' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }
}
