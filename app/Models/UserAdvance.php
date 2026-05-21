<?php

namespace App\Models;

use App\Enums\StaffAdvanceStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAdvance extends Model
{
    protected $table = 'user_advances';

    protected $fillable = [
        'organization_id', 'user_id', 'amount', 'recovered_amount',
        'advance_date', 'status', 'reason', 'rejection_reason',
        'recover_from_salary', 'recovery_months',
        'approved_by', 'approved_at', 'deleted', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'advance_date'      => 'date',
        'approved_at'       => 'datetime',
        'status'            => StaffAdvanceStatusEnum::class,
        'amount'            => 'decimal:2',
        'recovered_amount'  => 'decimal:2',
        'recover_from_salary' => 'integer',
        'deleted'           => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
