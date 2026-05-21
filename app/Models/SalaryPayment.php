<?php

namespace App\Models;

use App\Enums\SalaryPaymentModeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryPayment extends Model
{
    protected $table = 'salary_payments';

    protected $fillable = [
        'organization_id', 'payroll_id', 'user_id', 'pay_year', 'pay_month',
        'amount', 'payment_mode', 'reference_no', 'payment_date',
        'remarks', 'deleted', 'created_by',
    ];

    protected $casts = [
        'payment_mode' => SalaryPaymentModeEnum::class,
        'payment_date' => 'date',
        'amount'       => 'decimal:2',
        'deleted'      => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }
}
