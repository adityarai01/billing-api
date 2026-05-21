<?php
namespace App\Services;

use App\Models\Promotion;
use App\Models\PromotionUsage;
use Carbon\Carbon;

class PromotionValidationService
{
    public function validatePromotion(Promotion $promotion, array $cartData): bool
    {
        if (!$this->validateDateRange($promotion)) return false;
        if (!$this->validateUsageLimit($promotion)) return false;
        if (!$this->validateMinBillAmount($promotion, $cartData['subtotal'] ?? 0)) return false;
        if (!$this->validateTargets($promotion, $cartData['items'] ?? [])) return false;
        if (!$this->validateConditions($promotion, $cartData)) return false;
        return true;
    }

    public function validateDateRange(Promotion $promotion): bool
    {
        $now = Carbon::now();
        if ($promotion->start_date && $now->lt($promotion->start_date)) return false;
        if ($promotion->end_date && $now->gt($promotion->end_date)) return false;
        return true;
    }

    public function validateUsageLimit(Promotion $promotion): bool
    {
        if ($promotion->usage_limit && $promotion->used_count >= $promotion->usage_limit) {
            return false;
        }
        return true;
    }

    public function validateCustomerLimit(Promotion $promotion, ?int $customerId): bool
    {
        if (!$promotion->per_customer_limit || !$customerId) return true;

        $count = PromotionUsage::where('promotion_id', $promotion->id)
            ->where('customer_id', $customerId)
            ->count();

        return $count < $promotion->per_customer_limit;
    }

    public function validateMinBillAmount(Promotion $promotion, float $billAmount): bool
    {
        return $billAmount >= $promotion->min_bill_amount;
    }

    public function validateTargets(Promotion $promotion, array $cartItems): bool
    {
        if ($promotion->targets->isEmpty()) return true;

        foreach ($promotion->targets as $target) {
            foreach ($cartItems as $item) {
                if ($this->itemMatchesTarget($item, $target->target_type, $target->target_id)) {
                    return true;
                }
            }
        }
        return false;
    }

    public function validateConditions(Promotion $promotion, array $cartData): bool
    {
        foreach ($promotion->conditions as $condition) {
            if (!$this->evaluateCondition($condition, $cartData)) return false;
        }
        return true;
    }

    private function itemMatchesTarget(array $item, int $targetType, ?int $targetId): bool
    {
        return match ($targetType) {
            1 => $targetId === null || ($item['product_id'] ?? null) == $targetId,
            2 => $targetId === null || ($item['product_variant_id'] ?? null) == $targetId,
            3 => $targetId === null || ($item['category_id'] ?? null) == $targetId,
            4 => $targetId === null || ($item['brand_id'] ?? null) == $targetId,
            5 => true,
            default => false,
        };
    }

    private function evaluateCondition(object $condition, array $cartData): bool
    {
        return match ($condition->condition_type) {
            1 => ($cartData['subtotal'] ?? 0) >= (float) $condition->condition_value,
            2 => ($cartData['total_qty'] ?? 0) >= (float) $condition->condition_value,
            4 => $cartData['payment_mode'] == $condition->condition_value,
            7 => $this->checkTimeRange($condition->condition_value),
            8 => $this->checkDayOfWeek($condition->condition_value),
            default => true,
        };
    }

    private function checkTimeRange(?string $range): bool
    {
        if (!$range) return true;
        $parts = explode(',', $range);
        if (count($parts) < 2) return true;
        $now = Carbon::now()->format('H:i');
        return $now >= trim($parts[0]) && $now <= trim($parts[1]);
    }

    private function checkDayOfWeek(?string $days): bool
    {
        if (!$days) return true;
        $allowedDays = array_map('trim', explode(',', $days));
        $today = strtolower(Carbon::now()->englishDayOfWeek);
        return in_array($today, array_map('strtolower', $allowedDays));
    }
}
