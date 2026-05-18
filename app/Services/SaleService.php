<?php
namespace App\Services;

use App\Enums\CustomerLedgerTypeEnum;
use App\Enums\SaleStatusEnum;
use App\Enums\SaleStockStatusEnum;
use App\Enums\StockTransactionTypeEnum;
use App\Events\SaleCancelled;
use App\Events\SaleCreated;
use App\Models\Organization;
use App\Models\ProductBatch;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(
        private PosCalculationService $calcService,
        private SalePaymentService    $paymentService,
        private SaleDiscountService   $discountService,
        private SaleStockService      $stockService,
        private CustomerLedgerService $ledgerService,
    ) {}

    public function generateInvoiceNo(int $organizationId): string
    {
        $organization = Organization::where('deleted', 0)->findOrFail($organizationId);
        $prefix = trim((string) ($organization->invoice_prefix ?: 'INV'));
        $prefix = rtrim($prefix, '-');
        $startNo = max(1, (int) ($organization->invoice_start_no ?: 1));

        $last = Sale::where('organization_id', $organizationId)->orderByDesc('id')->value('invoice_no');
        $num  = $startNo;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $num = max($startNo, ((int) $m[1]) + 1);
        }

        return $prefix . '-' . str_pad($num, 6, '0', STR_PAD_LEFT);
    }

    public function createSale(int $organizationId, array $data, ?int $userId = null): Sale
    {
        return DB::transaction(function () use ($organizationId, $data, $userId) {
            $cart = $this->calcService->calculateCart($data);

            $stockErrors = $this->calcService->validateStock($data['items']);
            if (!empty($stockErrors)) {
                throw new \Exception('Stock validation failed: ' . implode(', ', $stockErrors));
            }

            $invoiceNo = $this->generateInvoiceNo($organizationId);

            $sale = Sale::create([
                'organization_id'           => $organizationId,
                'customer_id'               => $data['customer_id'] ?? null,
                'invoice_no'                => $invoiceNo,
                'invoice_date'              => $data['invoice_date'],
                'invoice_type'              => $data['invoice_type'] ?? 1,
                'subtotal'                  => $cart['subtotal'],
                'item_discount_amount'      => $cart['item_discount_amount'],
                'invoice_discount_type'     => $cart['invoice_discount_type'],
                'invoice_discount_value'    => $cart['invoice_discount_value'],
                'invoice_discount_amount'   => $cart['invoice_discount_amount'],
                'coupon_code'               => $data['coupon_code'] ?? null,
                'coupon_discount_amount'    => $cart['coupon_discount_amount'],
                'promotion_discount_amount' => $cart['promotion_discount_amount'],
                'total_discount_amount'     => $cart['total_discount_amount'],
                'taxable_amount'            => $cart['taxable_amount'],
                'cgst_amount'               => $cart['cgst_amount'],
                'sgst_amount'               => $cart['sgst_amount'],
                'igst_amount'               => $cart['igst_amount'],
                'gst_amount'                => $cart['gst_amount'],
                'other_charges'             => $cart['other_charges'],
                'round_off'                 => $cart['round_off'],
                'grand_total'               => $cart['grand_total'],
                'paid_amount'               => 0,
                'due_amount'                => $cart['grand_total'],
                'payment_status'            => 1,
                'sale_status'               => SaleStatusEnum::Completed->value,
                'stock_status'              => SaleStockStatusEnum::NotDeducted->value,
                'notes'                     => $data['notes'] ?? null,
                'terms_conditions'          => $data['terms_conditions'] ?? null,
                'created_by'                => $userId,
            ]);

            $itemIds = [];
            foreach ($cart['items'] as $item) {
                $si = SaleItem::create([
                    'organization_id'           => $organizationId,
                    'sale_id'                   => $sale->id,
                    'product_id'                => $item['product_id'],
                    'product_variant_id'        => $item['product_variant_id'],
                    'batch_id'                  => $item['batch_id'] ?? null,
                    'product_name'              => $item['product_name'] ?? null,
                    'variant_name'              => $item['variant_name'] ?? null,
                    'sku'                       => $item['sku'] ?? null,
                    'barcode'                   => $item['barcode'] ?? null,
                    'batch_no'                  => $item['batch_no'] ?? null,
                    'qty'                       => $item['qty'],
                    'mrp'                       => $item['mrp'] ?? 0,
                    'unit_price'                => $item['unit_price'],
                    'gross_amount'              => $item['gross_amount'],
                    'discount_type'             => $item['discount_type'] ?? null,
                    'discount_value'            => $item['discount_value'] ?? 0,
                    'discount_amount'           => $item['discount_amount'],
                    'promotion_id'              => $item['promotion_id'] ?? null,
                    'promotion_discount_amount' => $item['promotion_discount_amount'],
                    'total_discount_amount'     => $item['total_discount_amount'],
                    'taxable_amount'            => $item['taxable_amount'],
                    'gst_percent'               => $item['gst_percent'],
                    'cgst_percent'              => $item['cgst_percent'],
                    'sgst_percent'              => $item['sgst_percent'],
                    'igst_percent'              => $item['igst_percent'],
                    'cgst_amount'               => $item['cgst_amount'],
                    'sgst_amount'               => $item['sgst_amount'],
                    'igst_amount'               => $item['igst_amount'],
                    'gst_amount'                => $item['gst_amount'],
                    'total_amount'              => $item['total_amount'],
                    'purchase_price'            => $item['purchase_price'],
                    'profit_amount'             => $item['profit_amount'],
                    'is_free_item'              => $item['is_free_item'] ?? 0,
                ]);
                $itemIds[] = array_merge($item, ['sale_item_id' => $si->id]);
            }

            $this->discountService->createItemDiscountRows($organizationId, $sale->id, $itemIds, $userId);
            $this->discountService->createInvoiceDiscountRows($organizationId, $sale->id, $cart, $userId);

            $creditNoteAdjusted = 0;
            if (!empty($data['credit_notes'])) {
                $creditNoteAdjusted = $this->discountService->applyCreditNoteAdjustment(
                    $organizationId,
                    $sale->id,
                    $data['credit_notes'],
                    $data['customer_id'] ?? null,
                    $userId
                );
                if ($creditNoteAdjusted > 0 && $data['customer_id']) {
                    $this->ledgerService->createCreditNoteAdjustmentLedger($sale->id, 0, $creditNoteAdjusted);
                }
            }

            $payments = $data['payments'] ?? [];
            if (!empty($payments)) {
                $this->paymentService->createPaymentsForSale($organizationId, $sale->id, $payments, $data['customer_id'] ?? null, $userId);
            }

            $this->stockService->deductStockForSale($sale->id);

            if ($sale->customer_id) {
                $this->ledgerService->createSaleLedger($sale->id);
            }

            event(new SaleCreated($sale));
            return $sale->fresh();
        });
    }

    public function cancelSale(int $organizationId, int $saleId): Sale
    {
        return DB::transaction(function () use ($organizationId, $saleId) {
            $sale = Sale::where('organization_id', $organizationId)->where('id', $saleId)->where('deleted', 0)->firstOrFail();

            if ($sale->sale_status === SaleStatusEnum::Cancelled->value) {
                throw new \Exception('Sale is already cancelled.');
            }

            if ($sale->stock_status === SaleStockStatusEnum::StockDeducted->value) {
                $this->stockService->reverseStockForSaleCancel($saleId);
            }

            $adjustments = $sale->saleCreditNoteAdjustments;
            foreach ($adjustments as $adjustment) {
                $creditNote = \App\Models\SupplierCreditNote::find($adjustment->credit_note_id);
                if ($creditNote) {
                    $creditNote->update([
                        'used_amount'    => max(0, ($creditNote->used_amount ?? 0) - $adjustment->adjusted_amount),
                        'balance_amount' => ($creditNote->balance_amount ?? 0) + $adjustment->adjusted_amount,
                        'status'         => 2,
                    ]);
                }
            }

            if ($sale->customer_id) {
                \App\Models\CustomerLedger::create([
                    'organization_id'  => $organizationId,
                    'customer_id'      => $sale->customer_id,
                    'transaction_date' => now(),
                    'transaction_type' => CustomerLedgerTypeEnum::Sale->value,
                    'reference_id'     => $sale->id,
                    'reference_no'     => $sale->invoice_no,
                    'debit_amount'     => 0,
                    'credit_amount'    => $sale->grand_total,
                    'balance_amount'   => 0,
                    'remarks'          => 'Sale Cancelled: ' . $sale->invoice_no,
                ]);
                $this->ledgerService->recalculateCustomerBalance($sale->customer_id);
            }

            $sale->update([
                'sale_status'  => SaleStatusEnum::Cancelled->value,
                'stock_status' => SaleStockStatusEnum::Reversed->value,
            ]);

            event(new SaleCancelled($sale));
            return $sale->fresh();
        });
    }

    public function processReturn(int $organizationId, int $saleId, array $data): array
    {
        return DB::transaction(function () use ($organizationId, $saleId, $data) {
            $sale = Sale::with(['saleItems', 'customer'])
                ->where('organization_id', $organizationId)
                ->where('id', $saleId)
                ->where('deleted', 0)
                ->firstOrFail();

            if ((int) $sale->sale_status === SaleStatusEnum::Cancelled->value) {
                throw new \Exception('Cancelled sale cannot be returned.');
            }

            $items = collect($data['items'] ?? [])
                ->filter(fn(array $item) => (float) ($item['return_qty'] ?? 0) > 0)
                ->values();

            if ($items->isEmpty()) {
                throw new \Exception('Select at least one item to return.');
            }

            $refundAmount = 0.0;
            $returnedRows = [];

            foreach ($items as $itemData) {
                $saleItem = $sale->saleItems->firstWhere('id', (int) $itemData['sale_item_id']);

                if (!$saleItem) {
                    throw new \Exception('Sale item not found.');
                }

                $returnQty = (float) $itemData['return_qty'];
                $availableQty = max(0, (float) $saleItem->qty - (float) $saleItem->returned_qty);

                if ($returnQty > $availableQty) {
                    throw new \Exception('Return quantity exceeds available quantity for ' . ($saleItem->product_name ?? 'item'));
                }

                $refund = round(((float) $saleItem->total_amount / max(1, (float) $saleItem->qty)) * $returnQty, 2);

                $saleItem->update([
                    'returned_qty' => round((float) $saleItem->returned_qty + $returnQty, 3),
                ]);

                if (!empty($saleItem->batch_id)) {
                    ProductBatch::where('id', $saleItem->batch_id)->increment('available_qty', $returnQty);
                    ProductVariant::where('id', $saleItem->product_variant_id)
                        ->update([
                            'stock_qty' => ProductBatch::where('product_variant_id', $saleItem->product_variant_id)->sum('available_qty'),
                        ]);
                } else {
                    ProductVariant::where('id', $saleItem->product_variant_id)->increment('stock_qty', $returnQty);
                }

                $this->stockService->createSaleStockLedger([
                    'organization_id'    => $organizationId,
                    'product_id'         => $saleItem->product_id,
                    'product_variant_id' => $saleItem->product_variant_id,
                    'batch_id'           => $saleItem->batch_id,
                    'transaction_type'   => StockTransactionTypeEnum::SaleReturn->value,
                    'in_qty'             => $returnQty,
                    'out_qty'            => 0,
                    'reference_id'       => $sale->id,
                    'reference_no'       => $sale->invoice_no,
                    'remarks'            => 'Sale Return: ' . $sale->invoice_no,
                ]);

                $refundAmount += $refund;
                $returnedRows[] = [
                    'sale_item_id' => $saleItem->id,
                    'product_name' => $saleItem->product_name,
                    'variant_name' => $saleItem->variant_name,
                    'return_qty' => $returnQty,
                    'refund_amount' => $refund,
                    'reason' => $itemData['reason'] ?? null,
                ];
            }

            $sale->refresh();
            $allReturned = $sale->saleItems()
                ->where('deleted', 0)
                ->where('is_free_item', 0)
                ->whereRaw('returned_qty < qty')
                ->count() === 0;

            $sale->update([
                'sale_status' => $allReturned
                    ? SaleStatusEnum::Returned->value
                    : SaleStatusEnum::PartiallyReturned->value,
            ]);

            if (!empty($sale->customer_id) && $refundAmount > 0) {
                $this->ledgerService->createLedger([
                    'organization_id'  => $organizationId,
                    'customer_id'      => $sale->customer_id,
                    'transaction_date' => now(),
                    'transaction_type' => CustomerLedgerTypeEnum::SalesReturn->value,
                    'reference_id'     => $sale->id,
                    'reference_no'     => $sale->invoice_no,
                    'debit_amount'     => 0,
                    'credit_amount'    => round($refundAmount, 2),
                    'remarks'          => 'Sales Return: ' . $sale->invoice_no . (!empty($data['reason']) ? ' - ' . $data['reason'] : ''),
                ]);
            }

            return [
                'sale' => $sale->fresh(['customer', 'saleItems', 'salePayments']),
                'refund_amount' => round($refundAmount, 2),
                'returned_items' => $returnedRows,
            ];
        });
    }

    public function searchSales(int $organizationId, array $filters): array
    {
        $query = Sale::where('organization_id', $organizationId)->where('deleted', 0);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder->where('invoice_no', 'like', "%{$search}%");
            });
        }
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }
        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }
        if (!empty($filters['sale_status'])) {
            $query->where('sale_status', $filters['sale_status']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('invoice_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('invoice_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        $perPage = $filters['per_page'] ?? 20;
        return $query->with('customer')->orderByDesc('id')->paginate($perPage)->toArray();
    }

    public function saleDetails(int $organizationId, int $id): Sale
    {
        return Sale::where('organization_id', $organizationId)->where('id', $id)->where('deleted', 0)
            ->with(['customer', 'saleItems.productVariant', 'saleItems.productBatch', 'salePayments', 'saleInvoiceDiscounts', 'saleCreditNoteAdjustments'])
            ->firstOrFail();
    }

    public function updateSalePaymentStatus(int $saleId): void
    {
        $this->paymentService->updateSalePaidDueStatus($saleId);
    }
}
