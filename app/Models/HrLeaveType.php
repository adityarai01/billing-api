<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrLeaveType extends Model
{
    protected $table = 'hr_leave_types';

    protected $fillable = [
        'organization_id', 'name', 'code', 'allowed_days_per_year',
        'is_paid', 'carry_forward', 'max_carry_forward_days',
        'status', 'deleted', 'created_by',
    ];

    protected $casts = [
        'is_paid'       => 'integer',
        'carry_forward' => 'integer',
        'status'        => 'integer',
        'deleted'       => 'integer',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(UserLeave::class, 'leave_type_id');
    }
}
