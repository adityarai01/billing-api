<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $table = 'purchase_items';
    protected $guarded = [];
    protected $casts = [
        'mfg_date' => 'date',
        'expiry_date' => 'date',
        'qty' => 'decimal:3',
        'free_qty' => 'decimal:3',
        'total_qty' => 'decimal:3',
        'purchase_price' => 'decimal:2',
        'mrp' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'gst_percent' => 'decimal:2',
        'cgst_percent' => 'decimal:2',
        'sgst_percent' => 'decimal:2',
        'igst_percent' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'discount_type' => 'integer',
        'status' => 'integer',
        'deleted' => 'integer',
    ];

    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function productVariant() { return $this->belongsTo(ProductVariant::class); }
    public function productBatch() { return $this->belongsTo(ProductBatch::class, 'batch_id'); }
}
