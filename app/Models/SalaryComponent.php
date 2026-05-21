<?php

namespace App\Models;

use App\Enums\SalaryComponentTypeEnum;
use App\Enums\SalaryCalculationTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryComponent extends Model
{
    protected $table = 'salary_components';

    protected $fillable = [
        'organization_id', 'name', 'code', 'component_type', 'calculation_type',
        'default_value', 'is_taxable', 'is_mandatory', 'sort_order',
        'status', 'deleted', 'created_by',
    ];

    protected $casts = [
        'component_type'   => SalaryComponentTypeEnum::class,
        'calculation_type' => SalaryCalculationTypeEnum::class,
        'default_value'    => 'decimal:2',
        'is_taxable'       => 'integer',
        'is_mandatory'     => 'integer',
        'status'           => 'integer',
        'deleted'          => 'integer',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
