<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $table = 'purchases';
    protected $guarded = [];
    protected $casts = [
        'purchase_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'round_off' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'discount_type' => 'integer',
        'payment_status' => 'integer',
        'purchase_status' => 'integer',
        'status' => 'integer',
        'deleted' => 'integer',
    ];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function purchaseItems() { return $this->hasMany(PurchaseItem::class); }
    public function purchasePayments() { return $this->hasMany(PurchasePayment::class); }
    public function purchaseReturns() { return $this->hasMany(PurchaseReturn::class); }
}
