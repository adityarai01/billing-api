<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrDepartment extends Model
{
    protected $table = 'hr_departments';

    protected $fillable = [
        'organization_id', 'name', 'code', 'description', 'head_user_id',
        'status', 'deleted', 'created_by', 'updated_by',
    ];

    protected $casts = ['status' => 'integer', 'deleted' => 'integer'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_user_id');
    }

    public function designations(): HasMany
    {
        return $this->hasMany(HrDesignation::class, 'department_id');
    }
}
