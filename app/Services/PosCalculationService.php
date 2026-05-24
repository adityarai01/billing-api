<?php
namespace App\Services;

use App\Models\ProductVariant;
use App\Models\ProductBatch;

class PosCalculationService
{
    public function calculateCart(array $data): array
    {
        $items = [];
        foreach ($data['items'] as $item) {
            $items[] = $this->calculateItem($item, $data);
        }

        $subtotal          = array_sum(array_column($items, 'gross_amount'));
        $itemDiscountTotal = array_sum(array_column($items, 'total_discount_amount'));

        $invoiceDiscountAmount = $this->calculateInvoiceDiscount([
            'subtotal'                => $subtotal,
            'item_discount_amount'    => $itemDiscountTotal,
            'invoice_discount_type'   => $data['invoice_discount_type'] ?? null,
            'invoice_discount_value'  => $data['invoice_discount_value'] ?? 0,
        ]);

        $couponDiscountAmount = 0;
        // Coupon validation handled in SaleDiscountService; calculation passed from front-end here

        $totalDiscountAmount = $itemDiscountTotal + $invoiceDiscountAmount + $couponDiscountAmount;

        // Prices are GST-inclusive — apply invoice/coupon discount ratio to item-level GST amounts
        $afterItemDisc = $subtotal - $itemDiscountTotal;
        $ratio = $afterItemDisc > 0 ? ($afterItemDisc - $invoiceDiscountAmount - $couponDiscountAmount) / $afterItemDisc : 1;

        $totalCgst = round(array_sum(array_column($items, 'cgst_amount')) * $ratio, 2);
        $totalSgst = round(array_sum(array_column($items, 'sgst_amount')) * $ratio, 2);
        $totalIgst = 0;
        $totalGst  = $totalCgst + $totalSgst;

        // taxable_amount = GST-exclusive cart total (sum of item taxable_amounts * ratio)
        $taxableAmount = round(array_sum(array_column($items, 'taxable_amount')) * $ratio, 2);

        // grand_total = inclusive net after all discounts (prices already include GST — no extra addition)
        $inclusiveNet  = $subtotal - $totalDiscountAmount;
        $otherCharges  = (float) ($data['other_charges'] ?? 0);
        $roundOff      = (float) ($data['round_off'] ?? 0);
        $grandTotal    = round($inclusiveNet + $otherCharges + $roundOff, 2);

        return [
            'items'                      => $items,
            'subtotal'                   => round($subtotal, 2),
            'item_discount_amount'       => round($itemDiscountTotal, 2),
            'invoice_discount_type'      => $data['invoice_discount_type'] ?? null,
            'invoice_discount_value'     => $data['invoice_discount_value'] ?? 0,
            'invoice_discount_amount'    => round($invoiceDiscountAmount, 2),
            'coupon_discount_amount'     => round($couponDiscountAmount, 2),
            'promotion_discount_amount'  => 0,
            'total_discount_amount'      => round($totalDiscountAmount, 2),
            'taxable_amount'             => $taxableAmount,
            'cgst_amount'                => $totalCgst,
            'sgst_amount'                => $totalSgst,
            'igst_amount'                => $totalIgst,
            'gst_amount'                 => $totalGst,
            'other_charges'              => $otherCharges,
            'round_off'                  => $roundOff,
            'grand_total'                => $grandTotal,
        ];
    }

    public function calculateItem(array $item, array $context = []): array
    {
        $qty       = (float) ($item['qty'] ?? 0);
        $unitPrice = (float) ($item['unit_price'] ?? 0);

        if ($unitPrice === 0.0 && !empty($item['product_variant_id'])) {
            $price     = $this->getProductVariantPrice($item['product_variant_id'], $item['batch_id'] ?? null);
            $unitPrice = $price['selling_price'];
        }

        $grossAmount    = round($qty * $unitPrice, 2);
        $discountAmount = $this->calculateItemDiscount($item, $grossAmount);
        $totalDiscount  = $discountAmount;

        $inclusiveAmount = $grossAmount - $totalDiscount; // unit_price is GST-inclusive

        $gstPct  = (float) ($item['gst_percent'] ?? 0);
        $cgstPct = round($gstPct / 2, 2);
        $sgstPct = round($gstPct / 2, 2);
        $igstPct = 0;
        $divisor = 100 + $gstPct;
        // Extract GST from inclusive amount (do not add on top)
        $cgstAmt = $divisor > 0 ? round($inclusiveAmount * $cgstPct / $divisor, 2) : 0;
        $sgstAmt = $divisor > 0 ? round($inclusiveAmount * $sgstPct / $divisor, 2) : 0;
        $igstAmt = 0;
        $gstAmt  = $cgstAmt + $sgstAmt;
        // taxable_amount = GST-exclusive base (for GST invoice display)
        $taxableAmount = round($inclusiveAmount - $gstAmt, 2);
        $totalAmt = $inclusiveAmount; // total = inclusive amount (no extra addition)

        $purchasePrice = (float) ($item['purchase_price'] ?? 0);
        $profitAmount  = round($taxableAmount - ($purchasePrice * $qty), 2);

        return array_merge($item, [
            'unit_price'               => round($unitPrice, 2),
            'gross_amount'             => $grossAmount,
            'discount_amount'          => $discountAmount,
            'promotion_discount_amount'=> 0,
            'total_discount_amount'    => $totalDiscount,
            'taxable_amount'           => $taxableAmount,
            'gst_percent'              => $gstPct,
            'cgst_percent'             => $cgstPct,
            'sgst_percent'             => $sgstPct,
            'igst_percent'             => $igstPct,
            'cgst_amount'              => $cgstAmt,
            'sgst_amount'              => $sgstAmt,
            'igst_amount'              => $igstAmt,
            'gst_amount'               => $gstAmt,
            'total_amount'             => $totalAmt,
            'purchase_price'           => $purchasePrice,
            'profit_amount'            => $profitAmount,
        ]);
    }

    public function calculateItemDiscount(array $item, float $grossAmount): float
    {
        if (empty($item['discount_type']) || empty($item['discount_value'])) return 0;
        $amount = $item['discount_type'] == 1
            ? round($grossAmount * $item['discount_value'] / 100, 2)
            : round((float) $item['discount_value'], 2);
        return min($amount, $grossAmount);
    }

    public function calculateInvoiceDiscount(array $cart): float
    {
        if (empty($cart['invoice_discount_type']) || empty($cart['invoice_discount_value'])) return 0;
        $base = $cart['subtotal'] - ($cart['item_discount_amount'] ?? 0);
        $amount = $cart['invoice_discount_type'] == 1
            ? round($base * $cart['invoice_discount_value'] / 100, 2)
            : round((float) $cart['invoice_discount_value'], 2);
        return min($amount, $base);
    }

    public function validateStock(array $items): array
    {
        $errors = [];
        foreach ($items as $item) {
            $variantId     = $item['product_variant_id'];
            $batchId       = $item['batch_id'] ?? null;
            $qty           = (float) ($item['qty'] ?? 0);
            $conversionQty = (float) ($item['conversion_qty'] ?? 1);
            // base_qty is what will be deducted from stock
            $baseQty = isset($item['base_qty']) && (float) $item['base_qty'] > 0
                ? (float) $item['base_qty']
                : round($qty * $conversionQty, 3);

            if ($batchId) {
                $batch = ProductBatch::find($batchId);
                if (!$batch || (float) $batch->available_qty < $baseQty) {
                    $errors[] = "Insufficient stock for batch: {$item['batch_no']}";
                }
                if ($batch && $batch->expiry_date && $batch->expiry_date < now()->toDateString()) {
                    $errors[] = "Batch {$item['batch_no']} is expired.";
                }
            } else {
                $variant = ProductVariant::find($variantId);
                $availableBase = $variant
                    ? max((float) $variant->available_stock_base_qty, (float) $variant->stock_qty)
                    : 0.0;
                if (!$variant || $availableBase < $baseQty) {
                    $errors[] = "Insufficient stock for: {$item['product_name']} - {$item['variant_name']}";
                }
            }
        }
        return $errors;
    }

    public function selectBatchForSale(int $productVariantId, float $qty): ?ProductBatch
    {
        return ProductBatch::where('product_variant_id', $productVariantId)
            ->where('available_qty', '>=', $qty)
            ->where(function ($q) { $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', now()->toDateString()); })
            ->orderBy('expiry_date')
            ->first();
    }

    public function getProductVariantPrice(int $productVariantId, ?int $batchId = null): array
    {
        if ($batchId) {
            $batch = ProductBatch::find($batchId);
            if ($batch) {
                return ['selling_price' => $batch->selling_price ?? $batch->mrp, 'mrp' => $batch->mrp];
            }
        }
        $variant = ProductVariant::find($productVariantId);
        return ['selling_price' => $variant?->selling_price ?? 0, 'mrp' => $variant?->mrp ?? 0];
    }
}
