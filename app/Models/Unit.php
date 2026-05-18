<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'short_name',
        'status',
        'deleted',
    ];

    protected $casts = [
        'status'  => 'integer',
        'deleted' => 'integer',
    ];

    public function productVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }
}
