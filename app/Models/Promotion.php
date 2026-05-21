<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $table = 'promotions';
    protected $guarded = [];

    protected $casts = [
        'promotion_type'      => 'integer',
        'discount_level'      => 'integer',
        'apply_type'          => 'integer',
        'discount_type'       => 'integer',
        'discount_value'      => 'decimal:2',
        'min_bill_amount'     => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'start_date'          => 'datetime',
        'end_date'            => 'datetime',
        'usage_limit'         => 'integer',
        'used_count'          => 'integer',
        'per_customer_limit'  => 'integer',
        'priority'            => 'integer',
        'allow_multiple'      => 'integer',
        'stackable'           => 'integer',
        'auto_apply'          => 'integer',
        'requires_coupon'     => 'integer',
        'status'              => 'integer',
        'deleted'             => 'integer',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function coupons()
    {
        return $this->hasMany(PromotionCoupon::class);
    }

    public function targets()
    {
        return $this->hasMany(PromotionTarget::class);
    }

    public function buyGetRules()
    {
        return $this->hasMany(PromotionBuyGetRule::class);
    }

    public function comboItems()
    {
        return $this->hasMany(PromotionComboItem::class);
    }

    public function freeItems()
    {
        return $this->hasMany(PromotionFreeItem::class);
    }

    public function conditions()
    {
        return $this->hasMany(PromotionCondition::class);
    }

    public function usages()
    {
        return $this->hasMany(PromotionUsage::class);
    }

    public function isActive(): bool
    {
        return $this->status == 1 && $this->deleted == 0;
    }

    public function isExpired(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    public function isValid(): bool
    {
        if (!$this->isActive()) return false;
        if ($this->isExpired()) return false;
        if ($this->start_date && $this->start_date->isFuture()) return false;
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) return false;
        return true;
    }
}
