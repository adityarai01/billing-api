<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'customers';
    protected $guarded = [];
    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'loyalty_points'  => 'decimal:2',
        'balance_type'    => 'integer',
        'status'          => 'integer',
        'deleted'         => 'integer',
    ];

    public function sales() { return $this->hasMany(Sale::class); }
    public function salePayments() { return $this->hasMany(SalePayment::class); }
    public function customerLedgers() { return $this->hasMany(CustomerLedger::class); }
    public function heldBills() { return $this->hasMany(HeldBill::class); }
}
