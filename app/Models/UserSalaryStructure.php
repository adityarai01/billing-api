<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserSalaryStructure extends Model
{
    protected $table = 'user_salary_structures';

    protected $fillable = [
        'organization_id', 'user_id', 'gross_salary', 'basic_salary',
        'effective_from', 'effective_to', 'is_current', 'remarks',
        'deleted', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to'   => 'date',
        'gross_salary'   => 'decimal:2',
        'basic_salary'   => 'decimal:2',
        'is_current'     => 'integer',
        'deleted'        => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(UserSalaryStructureItem::class, 'salary_structure_id');
    }
}
