<?php
namespace App\Services;

use App\Models\Promotion;
use App\Models\PromotionCoupon;
use App\Models\PromotionUsage;
use Carbon\Carbon;

class PromotionCouponService
{
    public function __construct(
        private PromotionCacheService       $cacheService,
        private PromotionCalculationService $calculationService,
    ) {}

    public function create(array $data): PromotionCoupon
    {
        $data['coupon_code'] = strtoupper(trim($data['coupon_code']));

        // Restore a soft-deleted coupon instead of hitting the unique constraint
        $deleted = PromotionCoupon::where('organization_id', $data['organization_id'])
            ->where('coupon_code', $data['coupon_code'])
            ->where('deleted', 1)
            ->first();

        if ($deleted) {
            $deleted->update(array_merge($data, ['deleted' => 0, 'used_count' => 0]));
            $this->cacheService->forgetCouponCache($data['organization_id'], $data['coupon_code']);
            return $deleted->fresh();
        }

        $coupon = PromotionCoupon::create($data);
        $this->cacheService->forgetCouponCache($data['organization_id'], $data['coupon_code']);
        return $coupon;
    }

    public function bulkCreate(array $data): array
    {
        $created = [];
        $prefix  = $data['prefix'] ?? 'COUP';
        $count   = $data['count'] ?? 1;

        for ($i = 0; $i < $count; $i++) {
            $code = strtoupper($prefix . '-' . substr(md5(uniqid()), 0, 8));
            $created[] = PromotionCoupon::create(array_merge($data, ['coupon_code' => $code]));
        }

        return $created;
    }

    public function update(int $id, array $data): PromotionCoupon
    {
        $coupon = PromotionCoupon::findOrFail($id);
        $coupon->update($data);
        $this->cacheService->forgetCouponCache($coupon->organization_id, $coupon->coupon_code);
        return $coupon->fresh();
    }

    public function delete(int $id): void
    {
        $coupon = PromotionCoupon::findOrFail($id);
        $coupon->update(['deleted' => 1]);
        $this->cacheService->forgetCouponCache($coupon->organization_id, $coupon->coupon_code);
    }

    public function search(array $filters): array
    {
        $query = PromotionCoupon::where('organization_id', $filters['organization_id'])
            ->where('deleted', 0)
            ->with('promotion:id,name,promotion_type,discount_type,discount_value');

        if (!empty($filters['keyword'])) {
            $query->where('coupon_code', 'like', '%' . $filters['keyword'] . '%');
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['promotion_id'])) {
            $query->where('promotion_id', $filters['promotion_id']);
        }

        $perPage = $filters['per_page'] ?? 20;
        $result  = $query->orderByDesc('id')->paginate($perPage);

        return [
            'record' => $result->items(),
            'total'  => $result->total(),
            'pages'  => $result->lastPage(),
        ];
    }

    public function details(int $id): ?PromotionCoupon
    {
        return PromotionCoupon::with('promotion')->findOrFail($id);
    }

    public function validateCoupon(string $couponCode, int $organizationId, array $cartData): array
    {
        $coupon = PromotionCoupon::where('organization_id', $organizationId)
            ->where('coupon_code', strtoupper($couponCode))
            ->where('status', 1)
            ->where('deleted', 0)
            ->with('promotion')
            ->first();

        if (!$coupon) {
            return ['valid' => false, 'message' => 'Invalid coupon code.'];
        }

        if (!$coupon->isValid()) {
            return ['valid' => false, 'message' => 'Coupon is expired or usage limit reached.'];
        }

        if ($coupon->min_bill_amount > 0 && ($cartData['cart_total'] ?? 0) < $coupon->min_bill_amount) {
            return ['valid' => false, 'message' => "Minimum order ₹{$coupon->min_bill_amount} required to use this coupon."];
        }

        if (!empty($cartData['customer_id']) && $coupon->per_customer_limit) {
            $customerUsage = PromotionUsage::where('promotion_coupon_id', $coupon->id)
                ->where('customer_id', $cartData['customer_id'])
                ->count();
            if ($customerUsage >= $coupon->per_customer_limit) {
                return ['valid' => false, 'message' => 'You have already used this coupon the maximum number of times.'];
            }
        }

        $promotion = $coupon->promotion;
        if (!$promotion || !$promotion->isValid()) {
            return ['valid' => false, 'message' => 'This coupon is no longer active.'];
        }

        $discountAmount = $this->calculationService->calculateDiscountAmount(
            $promotion->discount_type,
            (float) $promotion->discount_value,
            $cartData['cart_total'] ?? 0,
            $coupon->max_discount_amount ? (float) $coupon->max_discount_amount : null
        );

        return [
            'valid'           => true,
            'message'         => 'Coupon applied successfully!',
            'coupon'          => $coupon,
            'discount_amount' => $discountAmount,
        ];
    }

    public function markCouponUsed(int $couponId, int $saleId, ?int $customerId): void
    {
        PromotionCoupon::where('id', $couponId)->increment('used_count');
    }
}
