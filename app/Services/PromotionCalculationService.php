<?php
namespace App\Services;

use App\Models\Promotion;

class PromotionCalculationService
{
    public function __construct(
        private PromotionValidationService $validationService,
        private BuyXGetYService            $buyGetService,
        private ComboOfferService          $comboService,
    ) {}

    public function calculateCartPromotions(array $cartData): array
    {
        $promotions = Promotion::where('organization_id', $cartData['organization_id'])
            ->where('status', 1)
            ->where('deleted', 0)
            ->with(['targets', 'buyGetRules', 'comboItems', 'freeItems', 'conditions'])
            ->orderByDesc('priority')
            ->get();

        $cartData['subtotal'] = collect($cartData['items'])->sum(fn($i) => ($i['unit_price'] ?? 0) * ($i['qty'] ?? 1));

        $items             = $cartData['items'];
        $appliedPromotions = [];
        $freeItems         = [];
        $hasNonStackable   = false;

        foreach ($promotions as $promotion) {
            if ($hasNonStackable && !$promotion->stackable) continue;
            if (!$this->validationService->validatePromotion($promotion, $cartData)) continue;
            if ($promotion->requires_coupon && empty($cartData['coupon_code'])) continue;
            if ($promotion->apply_type == 2) continue;

            if ($promotion->discount_level == 1) {
                $result = $this->applyItemLevelOffer($promotion, $items, $cartData);
                if ($result['applied']) {
                    $items               = $result['items'];
                    $appliedPromotions[] = $result['summary'];
                    $freeItems           = array_merge($freeItems, $result['free_items'] ?? []);
                    if (!$promotion->stackable) $hasNonStackable = true;
                }
            }
        }

        $subtotalAfterItemDiscount = collect($items)->sum(fn($i) => ($i['final_amount'] ?? ($i['unit_price'] ?? 0) * ($i['qty'] ?? 1)));
        $invoiceDiscountAmt        = 0;
        $appliedInvoicePromotion   = null;

        foreach ($promotions as $promotion) {
            if ($promotion->discount_level != 2) continue;
            if ($hasNonStackable && !$promotion->stackable) continue;
            if (!$this->validationService->validatePromotion($promotion, array_merge($cartData, ['subtotal' => $subtotalAfterItemDiscount]))) continue;
            if ($promotion->requires_coupon && empty($cartData['coupon_code'])) continue;
            if ($promotion->apply_type == 2) continue;

            $disc = $this->calculateDiscountAmount(
                $promotion->discount_type,
                (float) $promotion->discount_value,
                $subtotalAfterItemDiscount,
                $promotion->max_discount_amount ? (float) $promotion->max_discount_amount : null
            );

            if ($disc > 0) {
                $invoiceDiscountAmt      = $disc;
                $appliedInvoicePromotion = [
                    'promotion_id'   => $promotion->id,
                    'promotion_name' => $promotion->name,
                    'discount_level' => 2,
                    'discount_type'  => $promotion->discount_type,
                    'discount_value' => $promotion->discount_value,
                    'discount_amount' => $disc,
                ];
                if (!$promotion->stackable) break;
            }
        }

        if ($appliedInvoicePromotion) {
            $appliedPromotions[] = $appliedInvoicePromotion;
        }

        $couponDiscount = 0;
        if (!empty($cartData['coupon_code']) && !empty($cartData['coupon'])) {
            $coupon = $cartData['coupon'];
            $couponDiscount = $this->calculateDiscountAmount(
                $coupon['promotion']['discount_type'] ?? 2,
                (float) ($coupon['promotion']['discount_value'] ?? 0),
                $subtotalAfterItemDiscount - $invoiceDiscountAmt,
                $coupon['max_discount_amount'] ? (float) $coupon['max_discount_amount'] : null
            );
        }

        return [
            'items'              => $items,
            'free_items'         => $freeItems,
            'applied_promotions' => $appliedPromotions,
            'item_discount_total' => collect($items)->sum('item_promotion_discount') ?? 0,
            'invoice_discount'   => $invoiceDiscountAmt,
            'coupon_discount'    => $couponDiscount,
        ];
    }

    public function calculateDiscountAmount(int|string|null $discountType, float $value, float $baseAmount, ?float $maxDiscount = null): float
    {
        $disc = match ((int) $discountType) {
            1 => $baseAmount * $value / 100,
            2 => min($value, $baseAmount),
            default => 0,
        };

        if ($maxDiscount !== null) {
            $disc = min($disc, $maxDiscount);
        }

        return max(0, min($disc, $baseAmount));
    }

    public function resolveBestPromotion(array $eligiblePromotions): ?Promotion
    {
        if (empty($eligiblePromotions)) return null;
        usort($eligiblePromotions, fn($a, $b) => $b['discount_amount'] <=> $a['discount_amount']);
        return Promotion::find($eligiblePromotions[0]['promotion_id'] ?? null);
    }

    private function applyItemLevelOffer(Promotion $promotion, array $items, array $cartData): array
    {
        $applied   = false;
        $freeItems = [];

        if ($promotion->promotion_type == 5) {
            $result = $this->buyGetService->evaluateBuyGetOffer($promotion, $items);
            if (!empty($result['free_items'])) {
                $freeItems = $result['free_items'];
                $applied   = true;
            }
            return [
                'applied'   => $applied,
                'items'     => $items,
                'free_items' => $freeItems,
                'summary'   => [
                    'promotion_id'   => $promotion->id,
                    'promotion_name' => $promotion->name,
                    'discount_level' => 1,
                    'discount_type'  => 3,
                    'discount_amount' => 0,
                    'free_items'     => $freeItems,
                ],
            ];
        }

        if ($promotion->promotion_type == 6) {
            $result = $this->comboService->evaluateComboOffer($promotion, $items);
            if ($result['applicable']) {
                return [
                    'applied'   => true,
                    'items'     => $result['items'],
                    'free_items' => [],
                    'summary'   => [
                        'promotion_id'   => $promotion->id,
                        'promotion_name' => $promotion->name,
                        'discount_level' => 1,
                        'discount_type'  => $promotion->discount_type,
                        'discount_amount' => $result['total_discount'],
                    ],
                ];
            }
            return ['applied' => false, 'items' => $items, 'free_items' => [], 'summary' => []];
        }

        $totalDisc = 0;
        foreach ($items as &$item) {
            if (!$this->itemEligibleForPromotion($item, $promotion)) continue;

            $lineAmount = ($item['unit_price'] ?? 0) * ($item['qty'] ?? 1);
            $disc       = $this->calculateDiscountAmount(
                $promotion->discount_type,
                (float) $promotion->discount_value,
                $lineAmount,
                $promotion->max_discount_amount ? (float) $promotion->max_discount_amount : null
            );

            if ($disc > 0) {
                $item['item_promotion_discount'] = ($item['item_promotion_discount'] ?? 0) + $disc;
                $item['final_amount']            = max(0, $lineAmount - ($item['item_promotion_discount'] ?? 0));
                $item['applied_promotions'][]     = ['id' => $promotion->id, 'name' => $promotion->name, 'discount' => $disc];
                $totalDisc += $disc;
                $applied    = true;
            }
        }
        unset($item);

        return [
            'applied'   => $applied,
            'items'     => $items,
            'free_items' => [],
            'summary'   => [
                'promotion_id'   => $promotion->id,
                'promotion_name' => $promotion->name,
                'discount_level' => 1,
                'discount_type'  => $promotion->discount_type,
                'discount_amount' => $totalDisc,
            ],
        ];
    }

    private function itemEligibleForPromotion(array $item, Promotion $promotion): bool
    {
        if ($promotion->targets->isEmpty()) return true;

        foreach ($promotion->targets as $target) {
            $matches = match ($target->target_type) {
                1 => $target->target_id === null || ($item['product_id'] ?? null) == $target->target_id,
                2 => $target->target_id === null || ($item['product_variant_id'] ?? null) == $target->target_id,
                3 => $target->target_id === null || ($item['category_id'] ?? null) == $target->target_id,
                4 => $target->target_id === null || ($item['brand_id'] ?? null) == $target->target_id,
                default => false,
            };
            if ($matches) return true;
        }
        return false;
    }
}
