<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SaleItemDiscount extends Model
{
    protected $table = 'sale_item_discounts';
    protected $guarded = [];
    protected $casts = [
        'discount_source' => 'integer',
        'discount_type'   => 'integer',
        'discount_value'  => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function sale() { return $this->belongsTo(Sale::class); }
    public function saleItem() { return $this->belongsTo(SaleItem::class); }
}
