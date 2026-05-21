<?php
namespace App\Services;

use App\Models\Promotion;
use App\Models\PromotionCoupon;
use Illuminate\Support\Facades\Cache;

class PromotionCacheService
{
    private int $ttlPromotions = 600;
    private int $ttlCoupon     = 600;
    private int $ttlDetails    = 600;
    private int $ttlSummary    = 300;

    public function clearPromotionCache(int $organizationId): void
    {
        $keys = [
            "org:{$organizationId}:promotions:active",
        ];
        foreach (range(1, 11) as $type) {
            $keys[] = "org:{$organizationId}:promotions:type:{$type}";
        }
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    public function rememberActivePromotions(int $organizationId): array
    {
        return Cache::remember(
            "org:{$organizationId}:promotions:active",
            $this->ttlPromotions,
            fn() => Promotion::where('organization_id', $organizationId)
                ->where('status', 1)
                ->where('deleted', 0)
                ->with(['targets', 'buyGetRules', 'comboItems', 'freeItems', 'conditions'])
                ->orderByDesc('priority')
                ->get()
                ->toArray()
        );
    }

    public function rememberCoupon(int $organizationId, string $couponCode): ?array
    {
        return Cache::remember(
            "org:{$organizationId}:coupon:{$couponCode}",
            $this->ttlCoupon,
            fn() => PromotionCoupon::where('organization_id', $organizationId)
                ->where('coupon_code', $couponCode)
                ->where('status', 1)
                ->where('deleted', 0)
                ->with('promotion')
                ->first()
                ?->toArray()
        );
    }

    public function rememberPromotionDetails(int $organizationId, int $promotionId): ?array
    {
        return Cache::remember(
            "org:{$organizationId}:promotion:{$promotionId}:details",
            $this->ttlDetails,
            fn() => Promotion::where('organization_id', $organizationId)
                ->where('deleted', 0)
                ->with(['targets', 'coupons', 'buyGetRules', 'comboItems', 'freeItems', 'conditions'])
                ->find($promotionId)
                ?->toArray()
        );
    }

    public function forgetCouponCache(int $organizationId, string $couponCode): void
    {
        Cache::forget("org:{$organizationId}:coupon:{$couponCode}");
    }

    public function forgetPromotionDetailsCache(int $organizationId, int $promotionId): void
    {
        Cache::forget("org:{$organizationId}:promotion:{$promotionId}:details");
    }

    public function rebuildPromotionCache(int $organizationId): void
    {
        $this->clearPromotionCache($organizationId);
        $this->rememberActivePromotions($organizationId);
    }
}
