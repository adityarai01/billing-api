<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionCoupon extends Model
{
    protected $table = 'promotion_coupons';
    protected $guarded = [];

    protected $casts = [
        'usage_limit'         => 'integer',
        'used_count'          => 'integer',
        'per_customer_limit'  => 'integer',
        'start_date'          => 'datetime',
        'end_date'            => 'datetime',
        'min_bill_amount'     => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'status'              => 'integer',
        'deleted'             => 'integer',
    ];

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function usages()
    {
        return $this->hasMany(PromotionUsage::class, 'promotion_coupon_id');
    }

    public function isValid(): bool
    {
        if ($this->status != 1 || $this->deleted != 0) return false;
        if ($this->end_date && $this->end_date->isPast()) return false;
        if ($this->start_date && $this->start_date->isFuture()) return false;
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) return false;
        return true;
    }
}
