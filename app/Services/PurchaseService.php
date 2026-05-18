<?php
namespace App\Services;

use App\Events\PurchaseCreated;
use App\Jobs\GeneratePurchasePdfJob;
use App\Models\ProductBatch;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        private StockLedgerService $stockLedgerService,
        private PurchasePaymentService $paymentService
    ) {}

    public function generatePurchaseNo(int $organizationId): string
    {
        $last = Purchase::where('organization_id', $organizationId)
            ->orderByDesc('id')->value('purchase_no');
        $num = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $num = ((int) $m[1]) + 1;
        }
        return 'PO-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    public function calculatePurchaseTotals(array $items, array $extra = []): array
    {
        $subtotal = 0;
        $totalCgst = 0;
        $totalSgst = 0;
        $totalIgst = 0;
        $totalGst = 0;

        foreach ($items as &$item) {
            $grossAmount = round($item['qty'] * $item['purchase_price'], 2);
            $discountAmount = 0;
            if (!empty($item['discount_type']) && !empty($item['discount_value'])) {
                $discountAmount = $item['discount_type'] == 1
                    ? round($grossAmount * $item['discount_value'] / 100, 2)
                    : round($item['discount_value'], 2);
            }
            $taxable = $grossAmount - $discountAmount;
            $gstPct = $item['gst_percent'] ?? 0;
            $cgstPct = round($gstPct / 2, 2);
            $sgstPct = round($gstPct / 2, 2);
            $igstPct = 0;
            $cgstAmt = round($taxable * $cgstPct / 100, 2);
            $sgstAmt = round($taxable * $sgstPct / 100, 2);
            $igstAmt = 0;
            $gstAmt = $cgstAmt + $sgstAmt;
            $totalAmt = $taxable + $gstAmt;
            $item = array_merge($item, [
                'gross_amount' => $grossAmount,
                'discount_amount' => $discountAmount,
                'taxable_amount' => $taxable,
                'cgst_percent' => $cgstPct,
                'sgst_percent' => $sgstPct,
                'igst_percent' => $igstPct,
                'cgst_amount' => $cgstAmt,
                'sgst_amount' => $sgstAmt,
                'igst_amount' => $igstAmt,
                'gst_amount' => $gstAmt,
                'total_amount' => $totalAmt,
                'total_qty' => ($item['qty'] ?? 0) + ($item['free_qty'] ?? 0),
            ]);
            $subtotal += $grossAmount;
            $totalCgst += $cgstAmt;
            $totalSgst += $sgstAmt;
            $totalIgst += $igstAmt;
            $totalGst += $gstAmt;
        }

        $taxableAmount = $subtotal - ($extra['discount_amount'] ?? 0);
        $grandTotal = round($taxableAmount + $totalGst + ($extra['other_charges'] ?? 0) + ($extra['round_off'] ?? 0), 2);

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'taxable_amount' => $taxableAmount,
            'cgst_amount' => $totalCgst,
            'sgst_amount' => $totalSgst,
            'igst_amount' => $totalIgst,
            'gst_amount' => $totalGst,
            'grand_total' => $grandTotal,
        ];
    }

    public function createPurchase(int $organizationId, array $data): Purchase
    {
        return DB::transaction(function () use ($organizationId, $data) {
            $purchaseNo = $this->generatePurchaseNo($organizationId);
            $totals = $this->calculatePurchaseTotals($data['items'] ?? [], $data);

            $purchase = Purchase::create([
                'organization_id'     => $organizationId,
                'supplier_id'         => $data['supplier_id'] ?? null,
                'purchase_no'         => $purchaseNo,
                'supplier_invoice_no' => $data['supplier_invoice_no'] ?? null,
                'purchase_date'       => $data['purchase_date'],
                'due_date'            => $data['due_date'] ?? null,
                'subtotal'            => $totals['subtotal'],
                'discount_type'       => $data['discount_type'] ?? null,
                'discount_value'      => $data['discount_value'] ?? 0,
                'discount_amount'     => $data['discount_amount'] ?? 0,
                'taxable_amount'      => $totals['taxable_amount'],
                'cgst_amount'         => $totals['cgst_amount'],
                'sgst_amount'         => $totals['sgst_amount'],
                'igst_amount'         => $totals['igst_amount'],
                'gst_amount'          => $totals['gst_amount'],
                'other_charges'       => $data['other_charges'] ?? 0,
                'round_off'           => $data['round_off'] ?? 0,
                'grand_total'         => $totals['grand_total'],
                'paid_amount'         => 0,
                'due_amount'          => $totals['grand_total'],
                'payment_status'      => 1,
                'purchase_status'     => $data['purchase_status'] ?? 2,
                'remarks'             => $data['remarks'] ?? null,
                'created_by'          => $data['created_by'] ?? null,
            ]);

            $this->createItemsForPurchase($organizationId, $purchase, $totals['items'], $data['created_by'] ?? null);

            if (!empty($data['payments'])) {
                foreach ($data['payments'] as $payment) {
                    PurchasePayment::create(array_merge($payment, [
                        'organization_id' => $organizationId,
                        'purchase_id'     => $purchase->id,
                        'supplier_id'     => $data['supplier_id'] ?? null,
                    ]));
                }
                $this->paymentService->updatePurchasePaymentStatus($purchase->id);
                $purchase->refresh();
            }

            if (!empty($data['supplier_id'])) {
                Supplier::where('id', $data['supplier_id'])
                    ->increment('current_balance', $purchase->due_amount);
            }

            event(new PurchaseCreated($purchase));

            if ($purchase->purchase_status == 2) {
                GeneratePurchasePdfJob::dispatch($purchase->id)->onQueue('default');
            }

            return $purchase->fresh();
        });
    }

    public function updatePurchase(int $organizationId, int $id, array $data): Purchase
    {
        return DB::transaction(function () use ($organizationId, $id, $data) {
            $purchase = Purchase::with(['purchaseItems', 'purchasePayments'])
                ->where('organization_id', $organizationId)
                ->where('deleted', 0)
                ->findOrFail($id);

            if ((int) $purchase->purchase_status === 3) {
                abort(422, 'Cancelled purchase cannot be updated');
            }

            if (empty($data['items'])) {
                $purchase->update(array_intersect_key($data, array_flip([
                    'supplier_invoice_no',
                    'due_date',
                    'remarks',
                    'other_charges',
                    'round_off',
                    'purchase_status',
                ])));
                Cache::forget("org:{$organizationId}:purchase:{$id}:details");
                return $purchase->fresh();
            }

            if (!empty($purchase->supplier_id) && (float) $purchase->due_amount > 0) {
                Supplier::where('id', $purchase->supplier_id)->decrement('current_balance', (float) $purchase->due_amount);
            }

            $this->reverseExistingItems($purchase, $data['created_by'] ?? null);

            foreach ($purchase->purchaseItems as $existingItem) {
                $existingItem->update([
                    'deleted' => 1,
                    'status' => 0,
                ]);
            }

            foreach ($purchase->purchasePayments as $existingPayment) {
                $existingPayment->update([
                    'deleted' => 1,
                    'status' => 0,
                ]);
            }

            $totals = $this->calculatePurchaseTotals($data['items'] ?? [], $data);

            $purchase->update([
                'supplier_id'         => $data['supplier_id'] ?? null,
                'supplier_invoice_no' => $data['supplier_invoice_no'] ?? null,
                'purchase_date'       => $data['purchase_date'],
                'due_date'            => $data['due_date'] ?? null,
                'subtotal'            => $totals['subtotal'],
                'discount_type'       => $data['discount_type'] ?? null,
                'discount_value'      => $data['discount_value'] ?? 0,
                'discount_amount'     => $data['discount_amount'] ?? 0,
                'taxable_amount'      => $totals['taxable_amount'],
                'cgst_amount'         => $totals['cgst_amount'],
                'sgst_amount'         => $totals['sgst_amount'],
                'igst_amount'         => $totals['igst_amount'],
                'gst_amount'          => $totals['gst_amount'],
                'other_charges'       => $data['other_charges'] ?? 0,
                'round_off'           => $data['round_off'] ?? 0,
                'grand_total'         => $totals['grand_total'],
                'paid_amount'         => 0,
                'due_amount'          => $totals['grand_total'],
                'payment_status'      => 1,
                'purchase_status'     => $data['purchase_status'] ?? $purchase->purchase_status,
                'remarks'             => $data['remarks'] ?? null,
                'updated_by'          => $data['updated_by'] ?? null,
            ]);

            $this->createItemsForPurchase($organizationId, $purchase, $totals['items'], $data['created_by'] ?? null);

            if (!empty($data['payments'])) {
                foreach ($data['payments'] as $payment) {
                    PurchasePayment::create(array_merge($payment, [
                        'organization_id' => $organizationId,
                        'purchase_id' => $purchase->id,
                        'supplier_id' => $purchase->supplier_id,
                    ]));
                }
                $this->paymentService->updatePurchasePaymentStatus($purchase->id);
                $purchase->refresh();
            }

            if (!empty($purchase->supplier_id)) {
                Supplier::where('id', $purchase->supplier_id)->increment('current_balance', (float) $purchase->due_amount);
            }

            Cache::forget("org:{$organizationId}:purchase:{$id}:details");

            return $purchase->fresh(['supplier', 'purchaseItems', 'purchasePayments']);
        });
    }

    public function cancelPurchase(int $organizationId, int $id): Purchase
    {
        $purchase = $this->findOrFail($organizationId, $id);
        if ($purchase->purchase_status == 3) {
            abort(422, 'Purchase already cancelled');
        }
        $purchase->update(['purchase_status' => 3, 'status' => 0]);
        Cache::forget("org:{$organizationId}:purchase:{$id}:details");
        return $purchase->fresh();
    }

    public function searchPurchases(int $organizationId, array $filters = []): array
    {
        $query = Purchase::with(['supplier'])
            ->where('organization_id', $organizationId)
            ->where('deleted', 0);
        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }
        if (!empty($filters['purchase_status'])) {
            $query->where('purchase_status', $filters['purchase_status']);
        }
        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }
        if (!empty($filters['from_date'])) {
            $query->whereDate('purchase_date', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('purchase_date', '<=', $filters['to_date']);
        }
        if (!empty($filters['search'])) {
            $q = $filters['search'];
            $query->where(fn ($builder) => $builder
                ->where('purchase_no', 'like', "%{$q}%")
                ->orWhere('supplier_invoice_no', 'like', "%{$q}%"));
        }
        return $query->orderByDesc('id')->get()->toArray();
    }

    public function purchaseDetails(int $organizationId, int $id): Purchase
    {
        $cacheKey = "org:{$organizationId}:purchase:{$id}:details";
        return Cache::remember($cacheKey, 600, function () use ($organizationId, $id) {
            $purchase = Purchase::with(['supplier', 'purchaseItems.productVariant', 'purchaseItems.productBatch', 'purchasePayments'])
                ->where('organization_id', $organizationId)
                ->where('id', $id)
                ->where('deleted', 0)
                ->first();
            if (!$purchase) {
                abort(404, 'Purchase not found');
            }
            return $purchase;
        });
    }

    public function findOrFail(int $organizationId, int $id): Purchase
    {
        $purchase = Purchase::where('id', $id)
            ->where('organization_id', $organizationId)
            ->where('deleted', 0)
            ->first();
        if (!$purchase) {
            abort(404, 'Purchase not found');
        }
        return $purchase;
    }

    private function recalculateVariantStockFromBatches(int $variantId): void
    {
        $total = ProductBatch::where('product_variant_id', $variantId)->where('deleted', 0)->sum('available_qty');
        ProductVariant::where('id', $variantId)->update(['stock_qty' => $total]);
    }

    private function createItemsForPurchase(int $organizationId, Purchase $purchase, array $items, ?int $createdBy = null): void
    {
        foreach ($items as $itemData) {
            $variant = ProductVariant::find($itemData['product_variant_id']);
            PurchaseItem::create(array_merge($itemData, [
                'organization_id' => $organizationId,
                'purchase_id'     => $purchase->id,
                'product_name'    => $itemData['product_name'] ?? optional($variant->product ?? null)->name,
                'variant_name'    => $itemData['variant_name'] ?? $variant?->variant_name,
                'sku'             => $itemData['sku'] ?? $variant?->sku,
                'barcode'         => $itemData['barcode'] ?? $variant?->barcode,
            ]));

            $inQty = (float) ($itemData['qty'] ?? 0) + (float) ($itemData['free_qty'] ?? 0);

            if (!empty($itemData['batch_id'])) {
                ProductBatch::where('id', $itemData['batch_id'])->increment('available_qty', $inQty);
                $newBatchQty = ProductBatch::where('id', $itemData['batch_id'])->value('available_qty');
                $this->recalculateVariantStockFromBatches($itemData['product_variant_id']);
                $balanceQty = $newBatchQty;
            } else {
                ProductVariant::where('id', $itemData['product_variant_id'])->increment('stock_qty', $inQty);
                $balanceQty = ProductVariant::where('id', $itemData['product_variant_id'])->value('stock_qty');
            }

            $this->stockLedgerService->createLedger([
                'organization_id'    => $organizationId,
                'product_id'         => $itemData['product_id'],
                'product_variant_id' => $itemData['product_variant_id'],
                'batch_id'           => $itemData['batch_id'] ?? null,
                'transaction_type'   => 2,
                'reference_id'       => $purchase->id,
                'reference_no'       => $purchase->purchase_no,
                'in_qty'             => $inQty,
                'out_qty'            => 0,
                'balance_qty'        => $balanceQty,
                'rate'               => $itemData['purchase_price'] ?? 0,
                'stock_value'        => round($balanceQty * ($itemData['purchase_price'] ?? 0), 2),
                'created_by'         => $createdBy,
                'remarks'            => 'Purchase update: ' . $purchase->purchase_no,
            ]);
        }
    }

    private function reverseExistingItems(Purchase $purchase, ?int $createdBy = null): void
    {
        foreach ($purchase->purchaseItems as $item) {
            $outQty = (float) ($item->total_qty ?? ((float) ($item->qty ?? 0) + (float) ($item->free_qty ?? 0)));

            if ($outQty <= 0) {
                continue;
            }

            if (!empty($item->batch_id)) {
                ProductBatch::where('id', $item->batch_id)->decrement('available_qty', $outQty);
                $newBatchQty = ProductBatch::where('id', $item->batch_id)->value('available_qty');
                $this->recalculateVariantStockFromBatches($item->product_variant_id);
                $balanceQty = $newBatchQty;
            } else {
                ProductVariant::where('id', $item->product_variant_id)->decrement('stock_qty', $outQty);
                $balanceQty = ProductVariant::where('id', $item->product_variant_id)->value('stock_qty');
            }

            $this->stockLedgerService->createLedger([
                'organization_id'    => $purchase->organization_id,
                'product_id'         => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'batch_id'           => $item->batch_id,
                'transaction_type'   => 5,
                'reference_id'       => $purchase->id,
                'reference_no'       => $purchase->purchase_no,
                'in_qty'             => 0,
                'out_qty'            => $outQty,
                'balance_qty'        => $balanceQty,
                'rate'               => $item->purchase_price ?? 0,
                'stock_value'        => round($balanceQty * ($item->purchase_price ?? 0), 2),
                'created_by'         => $createdBy,
                'remarks'            => 'Purchase edit reverse: ' . $purchase->purchase_no,
            ]);
        }
    }
}
