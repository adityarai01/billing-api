<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'display_name',
        'description',
        'is_default',
        'deleted',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_default' => 'integer',
        'deleted'    => 'integer',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Return flat array of "module.action" strings for this role */
    public function permissionKeys(): array
    {
        return $this->permissions->map(fn($p) => "{$p->module}.{$p->action}")->toArray();
    }
}
