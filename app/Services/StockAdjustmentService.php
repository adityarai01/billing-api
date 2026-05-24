<?php
namespace App\Services;

use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\ProductVariant;
use App\Models\ProductBatch;
use App\Events\StockAdjusted;
use Illuminate\Support\Facades\DB;

class StockAdjustmentService
{
    public function __construct(private StockLedgerService $ledgerService) {}

    private function generateAdjNo(int $organizationId): string
    {
        $last = StockAdjustment::where('organization_id', $organizationId)->orderByDesc('id')->value('adjustment_no');
        $num = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) $num = ((int) $m[1]) + 1;
        return 'ADJ-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    public function createAdjustment(int $organizationId, array $data): StockAdjustment
    {
        return DB::transaction(function () use ($organizationId, $data) {
            $adjustment = StockAdjustment::create([
                'organization_id' => $organizationId,
                'adjustment_no'   => $this->generateAdjNo($organizationId),
                'adjustment_date' => $data['adjustment_date'],
                'adjustment_type' => $data['adjustment_type'],
                'reason_type'     => $data['reason_type'],
                'remarks'         => $data['remarks'] ?? null,
                'approval_status' => 1, // Pending
                'created_by'      => $data['created_by'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $variant = ProductVariant::find($item['product_variant_id']);
                $oldQty = $variant ? (float)$variant->stock_qty : 0;
                StockAdjustmentItem::create([
                    'organization_id'     => $organizationId,
                    'stock_adjustment_id' => $adjustment->id,
                    'product_id'          => $item['product_id'] ?? $variant?->product_id,
                    'product_variant_id'  => $item['product_variant_id'],
                    'batch_id'            => $item['batch_id'] ?? null,
                    'product_name'        => $item['product_name'] ?? null,
                    'variant_name'        => $item['variant_name'] ?? $variant?->variant_name,
                    'batch_no'            => $item['batch_no'] ?? null,
                    'old_qty'             => $oldQty,
                    'adjustment_qty'      => $item['adjustment_qty'],
                    'new_qty'             => $data['adjustment_type'] == 1
                        ? $oldQty + $item['adjustment_qty']
                        : max(0, $oldQty - $item['adjustment_qty']),
                    'rate'                => $item['rate'] ?? 0,
                    'stock_value'         => 0,
                    'remarks'             => $item['remarks'] ?? null,
                ]);
            }

            return $adjustment->fresh(['stockAdjustmentItems']);
        });
    }

    public function approveAdjustment(int $organizationId, int $id, ?int $approvedBy = null): StockAdjustment
    {
        return DB::transaction(function () use ($organizationId, $id, $approvedBy) {
            $adjustment = StockAdjustment::where('id', $id)->where('organization_id', $organizationId)->where('approval_status', 1)->firstOrFail();

            foreach ($adjustment->stockAdjustmentItems as $item) {
                $oldQty = (float)$item->old_qty;
                $adjQty = (float)$item->adjustment_qty;
                $isIncrease = $adjustment->adjustment_type == 1;
                $newQty = $isIncrease ? $oldQty + $adjQty : max(0, $oldQty - $adjQty);
                $item->update(['new_qty' => $newQty, 'stock_value' => round($newQty * $item->rate, 2)]);

                if (!empty($item->batch_id)) {
                    $isIncrease
                        ? ProductBatch::where('id', $item->batch_id)->increment('available_qty', $adjQty)
                        : ProductBatch::where('id', $item->batch_id)->decrement('available_qty', $adjQty);
                    $total = ProductBatch::where('product_variant_id', $item->product_variant_id)->where('deleted', 0)->sum('available_qty');
                    ProductVariant::where('id', $item->product_variant_id)->update([
                        'stock_qty'                => $total,
                        'available_stock_base_qty' => $total,
                    ]);
                } else {
                    $isIncrease
                        ? ProductVariant::where('id', $item->product_variant_id)->increment('stock_qty', $adjQty)
                        : ProductVariant::where('id', $item->product_variant_id)->decrement('stock_qty', $adjQty);
                    ProductVariant::where('id', $item->product_variant_id)
                        ->update(['available_stock_base_qty' => DB::raw('stock_qty')]);
                }

                $this->ledgerService->createLedger([
                    'organization_id'    => $organizationId,
                    'product_id'         => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'batch_id'           => $item->batch_id,
                    'transaction_type'   => 6, // StockAdjustment
                    'reference_id'       => $adjustment->id,
                    'reference_no'       => $adjustment->adjustment_no,
                    'in_qty'             => $isIncrease ? $adjQty : 0,
                    'out_qty'            => $isIncrease ? 0 : $adjQty,
                    'balance_qty'        => $newQty,
                    'rate'               => $item->rate,
                    'stock_value'        => round($newQty * $item->rate, 2),
                ]);
            }

            $adjustment->update([
                'approval_status' => 2,
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);

            event(new StockAdjusted($adjustment));
            return $adjustment->fresh();
        });
    }

    public function rejectAdjustment(int $organizationId, int $id): StockAdjustment
    {
        $adj = StockAdjustment::where('id', $id)->where('organization_id', $organizationId)->where('approval_status', 1)->firstOrFail();
        $adj->update(['approval_status' => 3]);
        return $adj->fresh();
    }

    public function searchAdjustments(int $organizationId, array $filters = []): array
    {
        $query = StockAdjustment::where('organization_id', $organizationId)->where('deleted', 0);
        if (!empty($filters['adjustment_type'])) $query->where('adjustment_type', $filters['adjustment_type']);
        if (!empty($filters['approval_status'])) $query->where('approval_status', $filters['approval_status']);
        if (!empty($filters['from_date'])) $query->whereDate('adjustment_date', '>=', $filters['from_date']);
        if (!empty($filters['to_date'])) $query->whereDate('adjustment_date', '<=', $filters['to_date']);
        return $query->orderByDesc('id')->get()->toArray();
    }

    public function adjustmentDetails(int $organizationId, int $id): StockAdjustment
    {
        $adj = StockAdjustment::with('stockAdjustmentItems')
            ->where('id', $id)->where('organization_id', $organizationId)->where('deleted', 0)->first();
        if (!$adj) abort(404, 'Adjustment not found');
        return $adj;
    }
}
