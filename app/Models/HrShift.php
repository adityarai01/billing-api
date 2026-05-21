<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrShift extends Model
{
    protected $table = 'hr_shifts';

    protected $fillable = [
        'organization_id', 'name', 'start_time', 'end_time',
        'grace_minutes', 'working_hours', 'working_days',
        'status', 'deleted', 'created_by', 'updated_by',
    ];

    protected $casts = ['status' => 'integer', 'deleted' => 'integer'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function userShifts(): HasMany
    {
        return $this->hasMany(UserShift::class, 'shift_id');
    }
}
