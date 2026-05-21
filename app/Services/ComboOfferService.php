<?php
namespace App\Services;

use App\Models\Promotion;

class ComboOfferService
{
    public function evaluateComboOffer(Promotion $promotion, array $cartItems): array
    {
        if (!$this->checkComboItemsAvailable($promotion, $cartItems)) {
            return ['applicable' => false, 'items' => $cartItems, 'total_discount' => 0];
        }

        return $this->calculateComboDiscount($promotion, $cartItems);
    }

    public function checkComboItemsAvailable(Promotion $promotion, array $cartItems): bool
    {
        foreach ($promotion->comboItems as $comboItem) {
            $found = false;
            $foundQty = 0;

            foreach ($cartItems as $cartItem) {
                if ($comboItem->product_variant_id && ($cartItem['product_variant_id'] ?? null) == $comboItem->product_variant_id) {
                    $foundQty += $cartItem['qty'] ?? 0;
                    $found = true;
                } elseif ($comboItem->product_id && ($cartItem['product_id'] ?? null) == $comboItem->product_id) {
                    $foundQty += $cartItem['qty'] ?? 0;
                    $found = true;
                } elseif ($comboItem->category_id && ($cartItem['category_id'] ?? null) == $comboItem->category_id) {
                    $foundQty += $cartItem['qty'] ?? 0;
                    $found = true;
                }
            }

            if (!$found || $foundQty < $comboItem->required_qty) {
                return false;
            }
        }

        return !$promotion->comboItems->isEmpty();
    }

    public function calculateComboDiscount(Promotion $promotion, array $cartItems): array
    {
        $comboItems   = $promotion->comboItems->toArray();
        $totalComboAmount = 0;
        $matchedItemIndices = [];

        foreach ($comboItems as $comboItem) {
            foreach ($cartItems as $idx => $cartItem) {
                $matches = false;
                if ($comboItem['product_variant_id'] && ($cartItem['product_variant_id'] ?? null) == $comboItem['product_variant_id']) {
                    $matches = true;
                } elseif ($comboItem['product_id'] && ($cartItem['product_id'] ?? null) == $comboItem['product_id']) {
                    $matches = true;
                }

                if ($matches) {
                    $totalComboAmount += ($cartItem['unit_price'] ?? 0) * min($cartItem['qty'] ?? 0, $comboItem['required_qty']);
                    $matchedItemIndices[] = $idx;
                    break;
                }
            }
        }

        $discountAmount = 0;
        if ($promotion->discount_type == 4) {
            $discountAmount = max(0, $totalComboAmount - $promotion->discount_value);
        } elseif ($promotion->discount_type == 1) {
            $discountAmount = $totalComboAmount * $promotion->discount_value / 100;
        } elseif ($promotion->discount_type == 2) {
            $discountAmount = min($promotion->discount_value, $totalComboAmount);
        }

        $distributed = $this->distributeComboDiscount(
            array_intersect_key($cartItems, array_flip($matchedItemIndices)),
            $discountAmount
        );

        foreach ($distributed as $idx => $disc) {
            if (isset($cartItems[$idx])) {
                $cartItems[$idx]['item_promotion_discount'] = ($cartItems[$idx]['item_promotion_discount'] ?? 0) + $disc;
                $lineAmount = ($cartItems[$idx]['unit_price'] ?? 0) * ($cartItems[$idx]['qty'] ?? 1);
                $cartItems[$idx]['final_amount'] = max(0, $lineAmount - ($cartItems[$idx]['item_promotion_discount'] ?? 0));
                $cartItems[$idx]['applied_promotions'][] = ['id' => $promotion->id, 'name' => $promotion->name, 'discount' => $disc];
            }
        }

        return [
            'applicable'     => true,
            'items'          => $cartItems,
            'total_discount' => $discountAmount,
        ];
    }

    public function distributeComboDiscount(array $comboItems, float $discountAmount): array
    {
        $totalAmount = array_sum(array_map(
            fn($item) => ($item['unit_price'] ?? 0) * ($item['qty'] ?? 1),
            $comboItems
        ));

        $distributed = [];
        foreach ($comboItems as $idx => $item) {
            $lineAmount = ($item['unit_price'] ?? 0) * ($item['qty'] ?? 1);
            $ratio      = $totalAmount > 0 ? $lineAmount / $totalAmount : 0;
            $distributed[$idx] = round($discountAmount * $ratio, 2);
        }

        return $distributed;
    }
}
