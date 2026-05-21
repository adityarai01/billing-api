<?php

namespace App\Models;

use App\Enums\LeaveStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLeave extends Model
{
    protected $table = 'user_leaves';

    protected $fillable = [
        'organization_id', 'user_id', 'leave_type_id', 'from_date', 'to_date',
        'total_days', 'status', 'reason', 'rejection_reason',
        'approved_by', 'approved_at', 'deleted', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'from_date'   => 'date',
        'to_date'     => 'date',
        'approved_at' => 'datetime',
        'status'      => LeaveStatusEnum::class,
        'total_days'  => 'decimal:1',
        'deleted'     => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(HrLeaveType::class, 'leave_type_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
