<?php
namespace App\Events;

use App\Models\PromotionCoupon;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CouponUsed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PromotionCoupon $coupon,
        public int             $saleId,
        public ?int            $customerId = null,
    ) {}
}
