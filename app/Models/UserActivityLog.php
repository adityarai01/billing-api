<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivityLog extends Model
{
    public $timestamps = false;

    protected $table = 'user_activity_logs';

    protected $fillable = [
        'organization_id', 'user_id', 'module', 'action',
        'description', 'reference_id', 'reference_type',
        'ip_address', 'meta', 'logged_at',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
        'meta'      => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
