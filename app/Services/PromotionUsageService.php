<?php
namespace App\Services;

use App\Models\Promotion;
use App\Models\PromotionCoupon;
use App\Models\PromotionUsage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PromotionUsageService
{
    public function createUsage(array $data): PromotionUsage
    {
        return PromotionUsage::create(array_merge($data, [
            'used_at' => $data['used_at'] ?? Carbon::now(),
        ]));
    }

    public function createUsagesForSale(int $saleId, int $organizationId, array $appliedPromotions, ?int $customerId = null, ?int $userId = null): void
    {
        foreach ($appliedPromotions as $promo) {
            $this->createUsage([
                'organization_id'    => $organizationId,
                'promotion_id'       => $promo['promotion_id'],
                'promotion_coupon_id' => $promo['coupon_id'] ?? null,
                'sale_id'            => $saleId,
                'customer_id'        => $customerId,
                'coupon_code'        => $promo['coupon_code'] ?? null,
                'discount_level'     => $promo['discount_level'],
                'discount_amount'    => $promo['discount_amount'] ?? 0,
                'free_item_qty'      => $promo['free_item_qty'] ?? 0,
                'created_by'         => $userId,
            ]);

            $this->updateUsedCount($promo['promotion_id'], $promo['coupon_id'] ?? null);
        }
    }

    public function updateUsedCount(int $promotionId, ?int $couponId = null): void
    {
        Promotion::where('id', $promotionId)->increment('used_count');

        if ($couponId) {
            PromotionCoupon::where('id', $couponId)->increment('used_count');
        }
    }

    public function searchUsage(array $filters): array
    {
        $orgId = $filters['organization_id'] ?? null;
        $query = PromotionUsage::where('organization_id', $orgId)
            ->with(['promotion', 'coupon', 'sale', 'customer']);

        if (!empty($filters['promotion_id'])) {
            $query->where('promotion_id', $filters['promotion_id']);
        }
        if (!empty($filters['coupon_code'])) {
            $query->where('coupon_code', $filters['coupon_code']);
        }
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }
        if (!empty($filters['from_date'])) {
            $query->whereDate('used_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('used_at', '<=', $filters['to_date']);
        }

        $perPage = $filters['per_page'] ?? 20;
        $result  = $query->orderByDesc('used_at')->paginate($perPage);

        return [
            'record' => $result->items(),
            'total'  => $result->total(),
            'pages'  => $result->lastPage(),
        ];
    }

    public function usageSummary(array $filters): array
    {
        $orgId = $filters['organization_id'] ?? null;

        return PromotionUsage::where('organization_id', $orgId)
            ->when(!empty($filters['from_date']), fn($q) => $q->whereDate('used_at', '>=', $filters['from_date']))
            ->when(!empty($filters['to_date']), fn($q) => $q->whereDate('used_at', '<=', $filters['to_date']))
            ->select(
                'promotion_id',
                DB::raw('COUNT(*) as usage_count'),
                DB::raw('SUM(discount_amount) as total_discount'),
                DB::raw('SUM(free_item_qty) as total_free_qty')
            )
            ->with('promotion:id,name,promotion_type')
            ->groupBy('promotion_id')
            ->orderByDesc('usage_count')
            ->get()
            ->toArray();
    }
}
