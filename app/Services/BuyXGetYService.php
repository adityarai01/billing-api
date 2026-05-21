<?php
namespace App\Services;

use App\Models\Promotion;
use App\Models\ProductVariant;

class BuyXGetYService
{
    public function evaluateBuyGetOffer(Promotion $promotion, array $cartItems): array
    {
        $freeItems = [];

        foreach ($promotion->buyGetRules as $rule) {
            $eligiblePaidItems = $this->getEligiblePaidItems($rule, $cartItems);
            if (empty($eligiblePaidItems)) continue;

            $totalBoughtQty = array_sum(array_column($eligiblePaidItems, 'qty'));
            if ($totalBoughtQty < $rule->buy_qty) continue;

            $setsEligible = floor($totalBoughtQty / $rule->buy_qty);
            $freeQty      = $setsEligible * $rule->get_qty;

            if ($rule->max_free_qty) {
                $freeQty = min($freeQty, $rule->max_free_qty);
            }

            if ($rule->is_same_product) {
                $sourceItem = $eligiblePaidItems[0];
                $freeItems[] = [
                    'product_id'         => $sourceItem['product_id'],
                    'product_variant_id' => $sourceItem['product_variant_id'],
                    'product_name'       => $sourceItem['product_name'] ?? $sourceItem['name'] ?? '',
                    'qty'                => $freeQty,
                    'unit_price'         => 0,
                    'is_free_item'       => 1,
                    'promotion_id'       => $promotion->id,
                    'auto_added'         => (bool) $rule->auto_add_free_item,
                ];
            } else {
                if ($rule->allow_cashier_select && $promotion->freeItems->isNotEmpty()) {
                    // Return all promotion_free_items as selectable options for cashier
                    foreach ($promotion->freeItems as $fi) {
                        $variant = ProductVariant::with('product')->find($fi->product_variant_id);
                        if (!$variant) continue;
                        $freeItems[] = [
                            'product_id'         => $fi->product_id ?? $variant->product_id,
                            'product_variant_id' => $fi->product_variant_id,
                            'product_name'       => $variant->product->name ?? '',
                            'qty'                => $freeQty,
                            'unit_price'         => 0,
                            'is_free_item'       => 1,
                            'promotion_id'       => $promotion->id,
                            'auto_added'         => false,
                            'cashier_select'     => true,
                        ];
                    }
                } else {
                    $eligibleFreeItems = $this->getEligibleFreeItems($rule, $cartItems);
                    foreach ($eligibleFreeItems as $freeVariant) {
                        $freeItems[] = [
                            'product_id'         => $freeVariant['product_id'],
                            'product_variant_id' => $freeVariant['id'],
                            'product_name'       => $freeVariant['product_name'] ?? '',
                            'qty'                => $freeQty,
                            'unit_price'         => 0,
                            'is_free_item'       => 1,
                            'promotion_id'       => $promotion->id,
                            'auto_added'         => (bool) $rule->auto_add_free_item,
                            'cashier_select'     => (bool) $rule->allow_cashier_select,
                        ];
                    }
                }
            }
        }

        return ['free_items' => $freeItems];
    }

    public function getEligiblePaidItems(object $rule, array $cartItems): array
    {
        return array_filter($cartItems, function ($item) use ($rule) {
            return match ($rule->buy_target_type) {
                1 => $rule->buy_target_id === null || ($item['product_id'] ?? null) == $rule->buy_target_id,
                2 => $rule->buy_target_id === null || ($item['product_variant_id'] ?? null) == $rule->buy_target_id,
                3 => $rule->buy_target_id === null || ($item['category_id'] ?? null) == $rule->buy_target_id,
                4 => $rule->buy_target_id === null || ($item['brand_id'] ?? null) == $rule->buy_target_id,
                default => false,
            };
        });
    }

    public function getEligibleFreeItems(object $rule, array $cartItems): array
    {
        if ($rule->get_target_type == 1) {
            return $cartItems;
        }

        if ($rule->get_target_id) {
            $variant = ProductVariant::with('product')->find($rule->get_target_id);
            if ($variant) {
                return [[
                    'id'           => $variant->id,
                    'product_id'   => $variant->product_id,
                    'product_name' => $variant->product->name ?? '',
                ]];
            }
        }

        return [];
    }

    public function validateFreeItemStock(array $freeItems): bool
    {
        foreach ($freeItems as $item) {
            $variant = ProductVariant::find($item['product_variant_id'] ?? null);
            if (!$variant) return false;
            if (($variant->available_stock ?? 0) < $item['qty']) return false;
        }
        return true;
    }

    public function buildFreeItemSaleRows(array $freeItems): array
    {
        return array_map(fn($item) => array_merge($item, [
            'is_free_item'       => 1,
            'unit_price'         => 0,
            'discount_type'      => null,
            'discount_value'     => 0,
        ]), $freeItems);
    }
}
