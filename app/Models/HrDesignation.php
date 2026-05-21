<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrDesignation extends Model
{
    protected $table = 'hr_designations';

    protected $fillable = [
        'organization_id', 'department_id', 'name', 'description',
        'status', 'deleted', 'created_by', 'updated_by',
    ];

    protected $casts = ['status' => 'integer', 'deleted' => 'integer'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(HrDepartment::class, 'department_id');
    }
}
