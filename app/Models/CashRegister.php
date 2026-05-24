<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    protected $table = 'cash_registers';
    protected $guarded = [];

    protected $casts = [
        'opening_cash'        => 'decimal:2',
        'cash_sales'          => 'decimal:2',
        'upi_sales'           => 'decimal:2',
        'card_sales'          => 'decimal:2',
        'bank_transfer_sales' => 'decimal:2',
        'credit_sales'        => 'decimal:2',
        'cash_refunds'        => 'decimal:2',
        'cash_in'             => 'decimal:2',
        'cash_out'            => 'decimal:2',
        'expenses'            => 'decimal:2',
        'expected_cash'       => 'decimal:2',
        'actual_cash'         => 'decimal:2',
        'difference_amount'   => 'decimal:2',
        'register_status'     => 'integer',
        'opened_at'           => 'datetime',
        'closed_at'           => 'datetime',
    ];

    public function cashier()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function transactions()
    {
        return $this->hasMany(CashRegisterTransaction::class);
    }
}
