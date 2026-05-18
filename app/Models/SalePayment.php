<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SalePayment extends Model
{
    protected $table = 'sale_payments';
    protected $guarded = [];
    protected $casts = [
        'payment_mode' => 'integer',
        'amount'       => 'decimal:2',
        'payment_date' => 'datetime',
        'status'       => 'integer',
        'deleted'      => 'integer',
    ];

    public function sale() { return $this->belongsTo(Sale::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
}
