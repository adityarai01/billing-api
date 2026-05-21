<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionUsage extends Model
{
    protected $table = 'promotion_usages';
    protected $guarded = [];

    protected $casts = [
        'promotion_coupon_id' => 'integer',
        'sale_id'             => 'integer',
        'customer_id'         => 'integer',
        'discount_level'      => 'integer',
        'discount_amount'     => 'decimal:2',
        'free_item_qty'       => 'decimal:3',
        'used_at'             => 'datetime',
    ];

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function coupon()
    {
        return $this->belongsTo(PromotionCoupon::class, 'promotion_coupon_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
