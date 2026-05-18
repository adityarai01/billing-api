<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CustomerLedger extends Model
{
    protected $table = 'customer_ledgers';
    protected $guarded = [];
    protected $casts = [
        'transaction_date' => 'datetime',
        'transaction_type' => 'integer',
        'debit_amount'     => 'decimal:2',
        'credit_amount'    => 'decimal:2',
        'balance_amount'   => 'decimal:2',
    ];

    public function customer() { return $this->belongsTo(Customer::class); }
}
