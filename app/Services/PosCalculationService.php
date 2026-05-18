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

        $totalCgst = array_sum(array_column($items, 'cgst_amount'));
        $totalSgst = array_sum(array_column($items, 'sgst_amount'));
        $totalIgst = array_sum(array_column($items, 'igst_amount'));
        $totalGst  = array_sum(array_column($items, 'gst_amount'));

        $taxableAmount = $subtotal - $totalDiscountAmount;
        $otherCharges  = (float) ($data['other_charges'] ?? 0);
        $roundOff      = (float) ($data['round_off'] ?? 0);
        $grandTotal    = round($taxableAmount + $totalGst + $otherCharges + $roundOff, 2);

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
            'taxable_amount'             => round($taxableAmount, 2),
            'cgst_amount'                => round($totalCgst, 2),
            'sgst_amount'                => round($totalSgst, 2),
            'igst_amount'                => round($totalIgst, 2),
            'gst_amount'                 => round($totalGst, 2),
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

        $taxableAmount = $grossAmount - $totalDiscount;

        $gstPct  = (float) ($item['gst_percent'] ?? 0);
        $cgstPct = round($gstPct / 2, 2);
        $sgstPct = round($gstPct / 2, 2);
        $igstPct = 0;
        $cgstAmt = round($taxableAmount * $cgstPct / 100, 2);
        $sgstAmt = round($taxableAmount * $sgstPct / 100, 2);
        $igstAmt = 0;
        $gstAmt  = $cgstAmt + $sgstAmt;
        $totalAmt = round($taxableAmount + $gstAmt, 2);

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
            $variantId = $item['product_variant_id'];
            $batchId   = $item['batch_id'] ?? null;
            $qty       = (float) ($item['qty'] ?? 0);

            if ($batchId) {
                $batch = ProductBatch::find($batchId);
                if (!$batch || $batch->available_qty < $qty) {
                    $errors[] = "Insufficient stock for batch: {$item['batch_no']}";
                }
                if ($batch && $batch->expiry_date && $batch->expiry_date < now()->toDateString()) {
                    $errors[] = "Batch {$item['batch_no']} is expired.";
                }
            } else {
                $variant = ProductVariant::find($variantId);
                if (!$variant || $variant->stock_qty < $qty) {
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
