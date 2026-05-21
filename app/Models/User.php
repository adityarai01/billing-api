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
        // HR fields
        'employee_code',
        'designation',
        'department',
        'employment_type',
        'joining_date',
        'leaving_date',
        'aadhar_no',
        'pan_no',
        'bank_name',
        'account_holder_name',
        'account_no',
        'ifsc_code',
        'login_enabled',
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
            'joining_date'       => 'date',
            'leaving_date'       => 'date',
            'password'           => 'hashed',
            'user_type'          => UserType::class,
            'gender'             => Gender::class,
            'status'             => UserStatus::class,
            'deleted'            => 'integer',
            'login_enabled'      => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function attendance()
    {
        return $this->hasMany(UserAttendance::class);
    }

    public function leaves()
    {
        return $this->hasMany(UserLeave::class);
    }

    public function salaryStructures()
    {
        return $this->hasMany(UserSalaryStructure::class);
    }

    public function currentSalaryStructure()
    {
        return $this->hasOne(UserSalaryStructure::class)->where('is_current', 1)->latest('effective_from');
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    public function advances()
    {
        return $this->hasMany(UserAdvance::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(UserActivityLog::class);
    }
}
