<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    protected $table = 'purchase_return_items';
    protected $guarded = [];
    protected $casts = [
        'purchased_qty' => 'decimal:3',
        'already_returned_qty' => 'decimal:3',
        'return_qty' => 'decimal:3',
        'purchase_price' => 'decimal:2',
        'mrp' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'gst_percent' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'status' => 'integer',
        'deleted' => 'integer',
    ];

    public function purchaseReturn() { return $this->belongsTo(PurchaseReturn::class); }
    public function purchaseItem() { return $this->belongsTo(PurchaseItem::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function productVariant() { return $this->belongsTo(ProductVariant::class); }
    public function productBatch() { return $this->belongsTo(ProductBatch::class, 'batch_id'); }
}
