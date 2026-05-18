<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class StockLedger extends Model
{
    protected $table = 'stock_ledgers';
    protected $guarded = [];
    protected $casts = [
        'in_qty' => 'decimal:3',
        'out_qty' => 'decimal:3',
        'balance_qty' => 'decimal:3',
        'rate' => 'decimal:2',
        'stock_value' => 'decimal:2',
        'transaction_type' => 'integer',
    ];

    public function product() { return $this->belongsTo(Product::class); }
    public function productVariant() { return $this->belongsTo(ProductVariant::class); }
    public function productBatch() { return $this->belongsTo(ProductBatch::class, 'batch_id'); }
}
