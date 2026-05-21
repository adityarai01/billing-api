<?php
namespace App\Listeners;

use App\Services\PromotionUsageService;

class UpdatePromotionUsageCount
{
    public function __construct(private PromotionUsageService $usageService) {}

    public function handle(object $event): void
    {
        if (isset($event->appliedPromotions)) {
            foreach ($event->appliedPromotions as $promo) {
                $this->usageService->updateUsedCount(
                    $promo['promotion_id'],
                    $promo['coupon_id'] ?? null
                );
            }
        }
    }
}
