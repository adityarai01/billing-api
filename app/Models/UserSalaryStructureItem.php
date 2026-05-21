<?php

namespace App\Models;

use App\Enums\SalaryCalculationTypeEnum;
use App\Enums\SalaryComponentTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSalaryStructureItem extends Model
{
    protected $table = 'user_salary_structure_items';

    protected $fillable = [
        'organization_id', 'salary_structure_id', 'component_id',
        'component_type', 'calculation_type', 'value', 'calculated_amount',
    ];

    protected $casts = [
        'component_type'    => SalaryComponentTypeEnum::class,
        'calculation_type'  => SalaryCalculationTypeEnum::class,
        'value'             => 'decimal:2',
        'calculated_amount' => 'decimal:2',
    ];

    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(UserSalaryStructure::class, 'salary_structure_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class, 'component_id');
    }
}
