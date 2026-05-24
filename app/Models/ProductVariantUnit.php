<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariantUnit extends Model
{
    protected $fillable = [
        'organization_id',
        'product_id',
        'product_variant_id',
        'unit_id',
        'unit_name_snapshot',
        'conversion_qty',
        'purchase_price',
        'mrp',
        'selling_price',
        'wholesale_price',
        'barcode',
        'is_base_unit',
        'is_default_purchase_unit',
        'is_default_sale_unit',
        'status',
        'deleted',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'conversion_qty'          => 'decimal:3',
        'purchase_price'          => 'decimal:2',
        'mrp'                     => 'decimal:2',
        'selling_price'           => 'decimal:2',
        'wholesale_price'         => 'decimal:2',
        'is_base_unit'            => 'integer',
        'is_default_purchase_unit'=> 'integer',
        'is_default_sale_unit'    => 'integer',
        'status'                  => 'integer',
        'deleted'                 => 'integer',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
