<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBatch extends Model
{
    protected $fillable = [
        'organization_id',
        'product_id',
        'product_variant_id',
        'supplier_id',
        'batch_no',
        'mfg_date',
        'expiry_date',
        'purchase_price',
        'mrp',
        'selling_price',
        'opening_qty',
        'available_qty',
        'status',
        'deleted',
    ];

    protected $casts = [
        'mfg_date'       => 'date',
        'expiry_date'    => 'date',
        'purchase_price' => 'decimal:4',
        'mrp'            => 'decimal:4',
        'selling_price'  => 'decimal:4',
        'opening_qty'    => 'decimal:4',
        'available_qty'  => 'decimal:4',
        'status'         => 'integer',
        'deleted'        => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Batches expiring within a given number of days.
     */
    public function scopeExpiringWithin($query, int $days)
    {
        return $query->whereNotNull('expiry_date')
                     ->where('expiry_date', '<=', now()->addDays($days))
                     ->where('expiry_date', '>=', now());
    }

    /**
     * Batches that are already expired.
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
                     ->where('expiry_date', '<', now()->toDateString());
    }
}
