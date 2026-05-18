<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'status',
        'deleted',
    ];

    protected $casts = [
        'status'  => 'integer',
        'deleted' => 'integer',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class);
    }

    public function variantAttributeValues(): HasMany
    {
        return $this->hasMany(ProductVariantAttributeValue::class);
    }
}
