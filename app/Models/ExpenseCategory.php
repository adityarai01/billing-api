<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    protected $table = 'expense_categories';
    protected $guarded = [];
    protected $casts = [
        'status' => 'integer',
        'deleted' => 'integer',
    ];

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'category_id');
    }
}
