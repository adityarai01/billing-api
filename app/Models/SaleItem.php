<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $table = 'sale_items';
    protected $guarded = [];
    protected $casts = [
        'qty'                      => 'decimal:3',
        'mrp'                      => 'decimal:2',
        'unit_price'               => 'decimal:2',
        'gross_amount'             => 'decimal:2',
        'discount_type'            => 'integer',
        'discount_value'           => 'decimal:2',
        'discount_amount'          => 'decimal:2',
        'promotion_discount_amount'=> 'decimal:2',
        'total_discount_amount'    => 'decimal:2',
        'taxable_amount'           => 'decimal:2',
        'gst_percent'              => 'decimal:2',
        'cgst_percent'             => 'decimal:2',
        'sgst_percent'             => 'decimal:2',
        'igst_percent'             => 'decimal:2',
        'cgst_amount'              => 'decimal:2',
        'sgst_amount'              => 'decimal:2',
        'igst_amount'              => 'decimal:2',
        'gst_amount'               => 'decimal:2',
        'total_amount'             => 'decimal:2',
        'purchase_price'           => 'decimal:2',
        'profit_amount'            => 'decimal:2',
        'returned_qty'             => 'decimal:3',
        'is_free_item'             => 'integer',
        'status'                   => 'integer',
        'deleted'                  => 'integer',
    ];

    public function sale() { return $this->belongsTo(Sale::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function productVariant() { return $this->belongsTo(ProductVariant::class); }
    public function productBatch() { return $this->belongsTo(ProductBatch::class, 'batch_id'); }
    public function saleItemDiscounts() { return $this->hasMany(SaleItemDiscount::class); }
}
