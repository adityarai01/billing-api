<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    protected $table = 'purchase_returns';
    protected $guarded = [];
    protected $casts = [
        'return_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'round_off' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'debit_note_amount' => 'decimal:2',
        'received_amount' => 'decimal:2',
        'settlement_type' => 'integer',
        'return_status' => 'integer',
        'status' => 'integer',
        'deleted' => 'integer',
    ];

    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function purchaseReturnItems() { return $this->hasMany(PurchaseReturnItem::class); }
    public function debitNote() { return $this->hasOne(DebitNote::class, 'purchase_return_id'); }
}
