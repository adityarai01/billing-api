<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class HeldBillItem extends Model
{
    protected $table = 'held_bill_items';
    protected $guarded = [];
    protected $casts = [
        'qty'             => 'decimal:3',
        'unit_price'      => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'gst_amount'      => 'decimal:2',
        'total_amount'    => 'decimal:2',
    ];

    public function heldBill() { return $this->belongsTo(HeldBill::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function productVariant() { return $this->belongsTo(ProductVariant::class); }
    public function productBatch() { return $this->belongsTo(ProductBatch::class, 'batch_id'); }
}
