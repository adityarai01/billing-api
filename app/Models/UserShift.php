<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserShift extends Model
{
    protected $table = 'user_shifts';

    protected $fillable = [
        'organization_id', 'user_id', 'shift_id',
        'effective_from', 'effective_to', 'deleted', 'created_by',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to'   => 'date',
        'deleted'        => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(HrShift::class, 'shift_id');
    }
}
