<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashRegisterTransaction extends Model
{
    protected $table = 'cash_register_transactions';
    protected $guarded = [];

    protected $casts = [
        'amount'           => 'decimal:2',
        'transaction_type' => 'integer',
        'source_type'      => 'integer',
        'payment_mode'     => 'integer',
    ];

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
