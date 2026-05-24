<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'organization_id',
        'product_id',
        'unit_id',
        'base_unit_id',
        'base_unit_name',
        'sku',
        'barcode',
        'variant_name',
        'purchase_price',
        'selling_price',
        'wholesale_price',
        'mrp',
        'stock_qty',
        'available_stock_base_qty',
        'opening_stock_base_qty',
        'low_stock_alert',
        'image',
        'status',
        'deleted',
    ];

    protected $casts = [
        'purchase_price'          => 'decimal:4',
        'selling_price'           => 'decimal:4',
        'wholesale_price'         => 'decimal:4',
        'mrp'                     => 'decimal:4',
        'stock_qty'               => 'decimal:4',
        'available_stock_base_qty'=> 'decimal:3',
        'opening_stock_base_qty'  => 'decimal:3',
        'low_stock_alert'         => 'decimal:4',
        'status'                  => 'integer',
        'deleted'                 => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductVariantAttributeValue::class);
    }

    public function variantUnits(): HasMany
    {
        return $this->hasMany(ProductVariantUnit::class);
    }

    public function activeVariantUnits(): HasMany
    {
        return $this->hasMany(ProductVariantUnit::class)->where('status', 1)->where('deleted', 0)->orderBy('is_base_unit', 'desc');
    }

    public function baseVariantUnit(): HasMany
    {
        return $this->hasMany(ProductVariantUnit::class)->where('is_base_unit', 1)->where('deleted', 0);
    }

    /**
     * Variants whose stock_qty is at or below low_stock_alert threshold.
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_qty', '<=', 'low_stock_alert')
                     ->where('low_stock_alert', '>', 0);
    }
}
