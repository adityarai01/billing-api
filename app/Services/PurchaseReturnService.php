<?php
namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\PurchaseItem;
use App\Models\ProductVariant;
use App\Models\ProductBatch;
use App\Models\Supplier;
use App\Events\PurchaseReturned;
use Illuminate\Support\Facades\DB;

class PurchaseReturnService
{
    public function __construct(
        private StockLedgerService $ledgerService,
        private DebitNoteService $debitNoteService
    ) {}

    private function generateReturnNo(int $organizationId): string
    {
        $last = PurchaseReturn::where('organization_id', $organizationId)->orderByDesc('id')->value('return_no');
        $num = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) $num = ((int) $m[1]) + 1;
        return 'PR-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    public function validateReturnQty(int $organizationId, array $items): void
    {
        foreach ($items as $item) {
            $purchaseItem = PurchaseItem::find($item['purchase_item_id']);
            if (!$purchaseItem) abort(422, 'Purchase item not found');
            $alreadyReturned = PurchaseReturnItem::where('purchase_item_id', $purchaseItem->id)->sum('return_qty');
            $available = $purchaseItem->total_qty - $alreadyReturned;
            if ((float)$item['return_qty'] > (float)$available) {
                abort(422, "Return qty {$item['return_qty']} exceeds available {$available} for item {$purchaseItem->id}");
            }
        }
    }

    public function createReturn(int $organizationId, array $data): PurchaseReturn
    {
        return DB::transaction(function () use ($organizationId, $data) {
            $this->validateReturnQty($organizationId, $data['items']);
            $purchase = !empty($data['purchase_id'])
                ? Purchase::where('organization_id', $organizationId)->findOrFail($data['purchase_id'])
                : null;
            $supplierId = $data['supplier_id'] ?? $purchase?->supplier_id;
            $returnNo = $this->generateReturnNo($organizationId);
            $normalizedItems = array_map(function (array $item) {
                $returnQty = (float) ($item['return_qty'] ?? 0);
                $purchasePrice = (float) ($item['purchase_price'] ?? 0);
                $item['total_amount'] = round((float) ($item['total_amount'] ?? ($returnQty * $purchasePrice)), 2);
                return $item;
            }, $data['items']);
            $subtotal = array_sum(array_column($normalizedItems, 'total_amount'));

            $return = PurchaseReturn::create([
                'organization_id' => $organizationId,
                'purchase_id'     => $data['purchase_id'] ?? null,
                'supplier_id'     => $supplierId,
                'return_no'       => $returnNo,
                'return_date'     => $data['return_date'],
                'subtotal'        => $subtotal,
                'grand_total'     => $subtotal,
                'settlement_type' => $data['settlement_type'] ?? 1,
                'return_status'   => 2, // Completed
                'reason'          => $data['reason'] ?? null,
                'remarks'         => $data['remarks'] ?? null,
                'created_by'      => $data['created_by'] ?? null,
            ]);

            foreach ($normalizedItems as $itemData) {
                $purchaseItem = PurchaseItem::find($itemData['purchase_item_id']);
                PurchaseReturnItem::create(array_merge($itemData, [
                    'organization_id'       => $organizationId,
                    'purchase_return_id'    => $return->id,
                    'purchase_id'           => $data['purchase_id'] ?? null,
                    'product_id'            => $purchaseItem->product_id,
                    'product_variant_id'    => $purchaseItem->product_variant_id,
                    'batch_id'              => $purchaseItem->batch_id,
                    'product_name'          => $purchaseItem->product_name,
                    'variant_name'          => $purchaseItem->variant_name,
                    'sku'                   => $purchaseItem->sku,
                    'barcode'               => $purchaseItem->barcode,
                    'batch_no'              => $purchaseItem->batch_no,
                    'purchased_qty'         => $purchaseItem->total_qty,
                    'already_returned_qty'  => PurchaseReturnItem::where('purchase_item_id', $purchaseItem->id)->sum('return_qty'),
                    'purchase_price'        => $purchaseItem->purchase_price,
                    'mrp'                   => $purchaseItem->mrp,
                ]));

                $returnQty = (float)$itemData['return_qty'];
                if (!empty($purchaseItem->batch_id)) {
                    ProductBatch::where('id', $purchaseItem->batch_id)->decrement('available_qty', $returnQty);
                    $total = ProductBatch::where('product_variant_id', $purchaseItem->product_variant_id)->where('deleted', 0)->sum('available_qty');
                    ProductVariant::where('id', $purchaseItem->product_variant_id)->update(['stock_qty' => $total]);
                    $balanceQty = $total;
                } else {
                    ProductVariant::where('id', $purchaseItem->product_variant_id)->decrement('stock_qty', $returnQty);
                    $balanceQty = ProductVariant::where('id', $purchaseItem->product_variant_id)->value('stock_qty');
                }

                $this->ledgerService->createLedger([
                    'organization_id'    => $organizationId,
                    'product_id'         => $purchaseItem->product_id,
                    'product_variant_id' => $purchaseItem->product_variant_id,
                    'batch_id'           => $purchaseItem->batch_id,
                    'transaction_type'   => 5, // PurchaseReturn
                    'reference_id'       => $return->id,
                    'reference_no'       => $returnNo,
                    'in_qty'             => 0,
                    'out_qty'            => $returnQty,
                    'balance_qty'        => $balanceQty,
                    'rate'               => $purchaseItem->purchase_price,
                    'stock_value'        => round($balanceQty * $purchaseItem->purchase_price, 2),
                    'created_by'         => $data['created_by'] ?? null,
                ]);
            }

            if ($return->settlement_type == 1) { // DebitNote
                $this->debitNoteService->createFromPurchaseReturn($organizationId, $return);
            } elseif ($return->settlement_type == 3 && !empty($supplierId)) {
                Supplier::where('id', $supplierId)->decrement('current_balance', $return->grand_total);
            }

            event(new PurchaseReturned($return));
            return $return->fresh(['purchaseReturnItems']);
        });
    }

    public function cancelReturn(int $organizationId, int $id): PurchaseReturn
    {
        $return = $this->findOrFail($organizationId, $id);
        $return->update(['return_status' => 3, 'status' => 0]);
        return $return->fresh();
    }

    public function searchReturns(int $organizationId, array $filters = []): array
    {
        $query = PurchaseReturn::with(['supplier', 'purchase'])
            ->where('organization_id', $organizationId)
            ->where('deleted', 0);
        if (!empty($filters['supplier_id'])) $query->where('supplier_id', $filters['supplier_id']);
        if (!empty($filters['search'])) {
            $q = $filters['search'];
            $query->where(fn ($b) => $b->where('return_no', 'like', "%{$q}%"));
        }
        if (!empty($filters['from_date'])) $query->whereDate('return_date', '>=', $filters['from_date']);
        if (!empty($filters['to_date'])) $query->whereDate('return_date', '<=', $filters['to_date']);
        return $query->orderByDesc('id')->get()->toArray();
    }

    public function returnDetails(int $organizationId, int $id): PurchaseReturn
    {
        $r = PurchaseReturn::with(['supplier', 'purchaseReturnItems', 'debitNote'])
            ->where('id', $id)->where('organization_id', $organizationId)->where('deleted', 0)->first();
        if (!$r) abort(404, 'Purchase return not found');
        return $r;
    }

    public function findOrFail(int $organizationId, int $id): PurchaseReturn
    {
        $r = PurchaseReturn::where('id', $id)->where('organization_id', $organizationId)->where('deleted', 0)->first();
        if (!$r) abort(404, 'Purchase return not found');
        return $r;
    }
}
