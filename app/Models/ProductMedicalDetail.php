<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductMedicalDetail extends Model
{
    protected $fillable = [
        'organization_id',
        'product_id',
        'generic_name',
        'salt_composition',
        'manufacturer',
        'medicine_type',
        'dosage_form',
        'prescription_required',
        'storage_instruction',
        'status',
        'deleted',
    ];

    protected $casts = [
        'prescription_required' => 'integer',
        'status'                => 'integer',
        'deleted'               => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
