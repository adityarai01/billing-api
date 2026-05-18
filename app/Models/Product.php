<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    protected $fillable = [
        'organization_id',
        'category_id',
        'brand_id',
        'name',
        'slug',
        'product_type',
        'description',
        'image',
        'hsn_code',
        'gst_percent',
        'status',
        'deleted',
    ];

    protected $casts = [
        'gst_percent' => 'decimal:2',
        'status'      => 'integer',
        'deleted'     => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function medicalDetail(): HasOne
    {
        return $this->hasOne(ProductMedicalDetail::class);
    }

    public function variantAttributeValues(): HasMany
    {
        return $this->hasMany(ProductVariantAttributeValue::class);
    }
}
