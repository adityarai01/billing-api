<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PurchasePayment extends Model
{
    protected $table = 'purchase_payments';
    protected $guarded = [];
    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'payment_mode' => 'integer',
        'status' => 'integer',
        'deleted' => 'integer',
    ];

    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
}
