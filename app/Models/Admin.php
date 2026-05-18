<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'mobile_no',
        'password',
        'profile_image',
        'last_login_at',
        'last_login_ip',
        'status',
        'deleted',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'      => 'hashed',
            'last_login_at' => 'datetime',
            'status'        => 'integer',
            'deleted'       => 'integer',
        ];
    }
}
