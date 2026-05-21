<?php

namespace App\Models;

use App\Enums\PayrollStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    protected $table = 'payrolls';

    protected $fillable = [
        'organization_id', 'user_id', 'pay_year', 'pay_month',
        'working_days', 'present_days', 'absent_days', 'half_days',
        'paid_leave_days', 'unpaid_leave_days', 'overtime_hours',
        'gross_salary', 'basic_salary', 'total_earnings', 'total_deductions',
        'advance_deduction', 'net_salary', 'status', 'remarks',
        'approved_by', 'approved_at', 'deleted', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'status'      => PayrollStatusEnum::class,
        'approved_at' => 'datetime',
        'deleted'     => 'integer',
        'gross_salary'      => 'decimal:2',
        'basic_salary'      => 'decimal:2',
        'total_earnings'    => 'decimal:2',
        'total_deductions'  => 'decimal:2',
        'advance_deduction' => 'decimal:2',
        'net_salary'        => 'decimal:2',
        'present_days'      => 'decimal:2',
        'absent_days'       => 'decimal:2',
        'half_days'         => 'decimal:2',
        'paid_leave_days'   => 'decimal:2',
        'unpaid_leave_days' => 'decimal:2',
        'overtime_hours'    => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class, 'payroll_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalaryPayment::class, 'payroll_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
