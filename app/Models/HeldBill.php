<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class HeldBill extends Model
{
    protected $table = 'held_bills';
    protected $guarded = [];
    protected $casts = [
        'hold_date'       => 'datetime',
        'subtotal'        => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'grand_total'     => 'decimal:2',
        'status'          => 'integer',
        'deleted'         => 'integer',
    ];

    public function customer() { return $this->belongsTo(Customer::class); }
    public function heldBillItems() { return $this->hasMany(HeldBillItem::class); }
}
