<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class StockAdjustmentItem extends Model
{
    protected $table = 'stock_adjustment_items';
    protected $guarded = [];
    protected $casts = [
        'old_qty' => 'decimal:3',
        'adjustment_qty' => 'decimal:3',
        'new_qty' => 'decimal:3',
        'rate' => 'decimal:2',
        'stock_value' => 'decimal:2',
    ];

    public function stockAdjustment() { return $this->belongsTo(StockAdjustment::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function productVariant() { return $this->belongsTo(ProductVariant::class); }
    public function productBatch() { return $this->belongsTo(ProductBatch::class, 'batch_id'); }
}
