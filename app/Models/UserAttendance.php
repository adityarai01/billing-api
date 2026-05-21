<?php

namespace App\Models;

use App\Enums\AttendanceStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAttendance extends Model
{
    protected $table = 'user_attendance';

    protected $fillable = [
        'organization_id', 'user_id', 'attendance_date', 'status',
        'check_in', 'check_out', 'working_hours', 'overtime_hours',
        'remarks', 'approved_by', 'deleted', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'status'          => AttendanceStatusEnum::class,
        'working_hours'   => 'decimal:2',
        'overtime_hours'  => 'decimal:2',
        'deleted'         => 'integer',
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
