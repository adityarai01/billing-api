<?php

namespace App\Models;

use App\Enums\SalaryComponentTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    protected $table = 'payroll_items';

    protected $fillable = [
        'organization_id', 'payroll_id', 'component_id', 'component_name',
        'component_type', 'value', 'amount',
    ];

    protected $casts = [
        'component_type' => SalaryComponentTypeEnum::class,
        'value'          => 'decimal:2',
        'amount'         => 'decimal:2',
    ];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class, 'component_id');
    }
}
