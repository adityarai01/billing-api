<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\UserStatus;
use App\Enums\UserType;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // Legacy constants — kept for backwards compatibility
    public const TYPE_SUPER_ADMIN       = UserType::SuperAdmin->value;
    public const TYPE_SHOP_OWNER        = UserType::ShopOwner->value;
    public const TYPE_CASHIER           = UserType::Cashier->value;
    public const TYPE_INVENTORY_MANAGER = UserType::InventoryManager->value;
    public const TYPE_ACCOUNTANT        = UserType::Accountant->value;
    public const TYPE_STAFF             = UserType::Staff->value;

    public const GENDER_MALE   = Gender::Male->value;
    public const GENDER_FEMALE = Gender::Female->value;
    public const GENDER_OTHER  = Gender::Other->value;

    public const STATUS_ACTIVE   = UserStatus::Active->value;
    public const STATUS_INACTIVE = UserStatus::Inactive->value;

    protected $fillable = [
        'organization_id',
        'name',
        'email',
        'mobile_no',
        'password',
        'user_type',
        'role_id',
        'profile_image',
        'gender',
        'dob',
        'address',
        'city',
        'state',
        'pincode',
        'last_login_at',
        'last_login_ip',
        'email_verified_at',
        'mobile_verified_at',
        'status',
        'deleted',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'mobile_verified_at' => 'datetime',
            'last_login_at'      => 'datetime',
            'dob'                => 'date',
            'password'           => 'hashed',
            'user_type'          => UserType::class,
            'gender'             => Gender::class,
            'status'             => UserStatus::class,
            'deleted'            => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
