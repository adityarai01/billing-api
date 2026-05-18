<?php

namespace App\Models;

use App\Enums\OrganizationType;
use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Organization extends Model
{
    // Legacy constants — kept for backwards compatibility
    public const SHOP_TYPE_MEDICAL  = OrganizationType::Medical->value;
    public const SHOP_TYPE_CLOTH    = OrganizationType::Cloth->value;
    public const SHOP_TYPE_GROCERY  = OrganizationType::Grocery->value;
    public const SHOP_TYPE_GENERAL  = OrganizationType::General->value;

    public const SUBSCRIPTION_TRIAL     = SubscriptionStatus::Trial->value;
    public const SUBSCRIPTION_ACTIVE    = SubscriptionStatus::Active->value;
    public const SUBSCRIPTION_EXPIRED   = SubscriptionStatus::Expired->value;
    public const SUBSCRIPTION_SUSPENDED = SubscriptionStatus::Suspended->value;

    public const STATUS_ACTIVE   = 1;
    public const STATUS_INACTIVE = 0;

    protected $fillable = [
        'shop_name',
        'business_name',
        'owner_name',
        'shop_type',
        'mobile_no',
        'alternate_mobile_no',
        'email',
        'gstin',
        'pan_no',
        'address',
        'city',
        'state',
        'state_code',
        'pincode',
        'country',
        'logo',
        'signature',
        'invoice_prefix',
        'invoice_start_no',
        'currency',
        'timezone',
        'subscription_status',
        'trial_start_date',
        'trial_end_date',
        'status',
        'deleted',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'shop_type'           => OrganizationType::class,
            'invoice_start_no'    => 'integer',
            'subscription_status' => SubscriptionStatus::class,
            'status'              => 'integer',
            'deleted'             => 'integer',
            'trial_start_date'    => 'date',
            'trial_end_date'      => 'date',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function shopkeeper(): HasOne
    {
        return $this->hasOne(User::class)->where('user_type', User::TYPE_SHOP_OWNER);
    }

    public function invoiceSetting(): HasOne
    {
        return $this->hasOne(OrganizationInvoiceSetting::class);
    }
}
