<?php
namespace App\Services;

use App\Models\SaleItemDiscount;
use App\Models\SaleInvoiceDiscount;
use App\Models\SaleCreditNoteAdjustment;
use App\Models\SupplierCreditNote;
use App\Enums\ItemDiscountSourceEnum;
use App\Enums\InvoiceDiscountSourceEnum;

class SaleDiscountService
{
    public function createItemDiscountRows(int $organizationId, int $saleId, array $items, ?int $createdBy = null): void
    {
        foreach ($items as $item) {
            if (empty($item['sale_item_id']) || empty($item['discount_type']) || ($item['discount_amount'] ?? 0) <= 0) continue;

            SaleItemDiscount::create([
                'organization_id' => $organizationId,
                'sale_id'         => $saleId,
                'sale_item_id'    => $item['sale_item_id'],
                'discount_source' => ItemDiscountSourceEnum::Manual->value,
                'promotion_id'    => null,
                'discount_type'   => $item['discount_type'],
                'discount_value'  => $item['discount_value'] ?? 0,
                'discount_amount' => $item['discount_amount'],
                'remarks'         => 'Manual item discount',
                'created_by'      => $createdBy,
            ]);
        }
    }

    public function createInvoiceDiscountRows(int $organizationId, int $saleId, array $saleData, ?int $createdBy = null): void
    {
        if (!empty($saleData['invoice_discount_amount']) && $saleData['invoice_discount_amount'] > 0) {
            SaleInvoiceDiscount::create([
                'organization_id' => $organizationId,
                'sale_id'         => $saleId,
                'discount_source' => InvoiceDiscountSourceEnum::ManualInvoiceDiscount->value,
                'discount_type'   => $saleData['invoice_discount_type'],
                'discount_value'  => $saleData['invoice_discount_value'] ?? 0,
                'discount_amount' => $saleData['invoice_discount_amount'],
                'remarks'         => 'Manual invoice discount',
                'created_by'      => $createdBy,
            ]);
        }

        if (!empty($saleData['coupon_discount_amount']) && $saleData['coupon_discount_amount'] > 0) {
            SaleInvoiceDiscount::create([
                'organization_id' => $organizationId,
                'sale_id'         => $saleId,
                'discount_source' => InvoiceDiscountSourceEnum::Coupon->value,
                'coupon_code'     => $saleData['coupon_code'] ?? null,
                'discount_type'   => 2,
                'discount_value'  => $saleData['coupon_discount_amount'],
                'discount_amount' => $saleData['coupon_discount_amount'],
                'remarks'         => 'Coupon discount',
                'created_by'      => $createdBy,
            ]);
        }
    }

    public function applyCreditNoteAdjustment(int $organizationId, int $saleId, array $creditNotes, ?int $customerId = null, ?int $createdBy = null): float
    {
        $totalAdjusted = 0;
        foreach ($creditNotes as $cn) {
            $creditNote = SupplierCreditNote::find($cn['credit_note_id']);
            if (!$creditNote) continue;

            $amount = min((float) $cn['adjusted_amount'], (float) ($creditNote->balance_amount ?? 0));
            if ($amount <= 0) continue;

            SaleCreditNoteAdjustment::create([
                'organization_id' => $organizationId,
                'sale_id'         => $saleId,
                'customer_id'     => $customerId,
                'credit_note_id'  => $cn['credit_note_id'],
                'adjusted_amount' => $amount,
                'adjustment_date' => now(),
                'remarks'         => 'Credit note adjusted in sale',
                'created_by'      => $createdBy,
            ]);

            $newBalance = max(0, ($creditNote->balance_amount ?? 0) - $amount);
            $creditNote->update([
                'used_amount'    => ($creditNote->used_amount ?? 0) + $amount,
                'balance_amount' => $newBalance,
                'status'         => $newBalance <= 0 ? 3 : $creditNote->status,
            ]);

            $totalAdjusted += $amount;
        }
        return $totalAdjusted;
    }
}
