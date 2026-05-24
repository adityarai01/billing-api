<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\ProductVariant;
use App\Models\ProductBatch;
use App\Models\StockLedger;
use App\Enums\StockTransactionTypeEnum;
use Illuminate\Support\Facades\DB;

class SaleStockService
{
    public function __construct(private StockAlertService $alertService) {}
    public function deductStockForSale(int $saleId): void
    {
        $sale  = Sale::with('saleItems')->findOrFail($saleId);
        $items = $sale->saleItems()->where('deleted', 0)->where('is_free_item', 0)->get();

        foreach ($items as $item) {
            // base_qty is a decimal column returned as string "0.000" — cast first, then compare
            $baseQtyVal = (float) ($item->base_qty ?? 0);
            $deductQty  = $baseQtyVal > 0 ? $baseQtyVal : (float) $item->qty;

            if ($item->batch_id) {
                $this->decreaseBatchStock($item->batch_id, $deductQty);
            } else {
                $this->decreaseVariantStock($item->product_variant_id, $deductQty);
            }

            $this->createSaleStockLedger([
                'organization_id'    => $sale->organization_id,
                'product_id'         => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'batch_id'           => $item->batch_id,
                'transaction_type'   => StockTransactionTypeEnum::Sale->value ?? 3,
                'unit_id'            => $item->sale_unit_id,
                'unit_name'          => $item->sale_unit_name,
                'conversion_qty'     => $item->conversion_qty ?? 1,
                'in_qty'             => 0,
                'out_qty'            => (float) $item->qty,
                'base_in_qty'        => 0,
                'base_out_qty'       => $deductQty,
                'reference_id'       => $saleId,
                'reference_no'       => $sale->invoice_no,
                'remarks'            => 'Sale: ' . $sale->invoice_no,
            ]);

            // Immediately check & fire low-stock / out-of-stock notification
            $this->alertService->checkAndAlert((int) $sale->organization_id, (int) $item->product_variant_id);

            $this->recalculateProductStock($item->product_id);
        }

        $sale->update(['stock_status' => 1]);
    }

    public function reverseStockForSaleCancel(int $saleId): void
    {
        $sale  = Sale::with('saleItems')->findOrFail($saleId);
        $items = $sale->saleItems()->where('deleted', 0)->where('is_free_item', 0)->get();

        foreach ($items as $item) {
            $baseQtyVal = (float) ($item->base_qty ?? 0);
            $restoreQty = $baseQtyVal > 0 ? $baseQtyVal : (float) $item->qty;

            if ($item->batch_id) {
                $batch = ProductBatch::find($item->batch_id);
                if ($batch) {
                    $batch->increment('available_qty', $restoreQty);
                    $batch->increment('available_qty_base', $restoreQty);
                }
            } else {
                ProductVariant::where('id', $item->product_variant_id)->increment('stock_qty', $restoreQty);
                ProductVariant::where('id', $item->product_variant_id)
                    ->update(['available_stock_base_qty' => DB::raw('stock_qty')]);
            }

            $this->createSaleStockLedger([
                'organization_id'    => $sale->organization_id,
                'product_id'         => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'batch_id'           => $item->batch_id,
                'transaction_type'   => StockTransactionTypeEnum::Sale->value ?? 3,
                'unit_id'            => $item->sale_unit_id,
                'unit_name'          => $item->sale_unit_name,
                'conversion_qty'     => $item->conversion_qty ?? 1,
                'in_qty'             => (float) $item->qty,
                'out_qty'            => 0,
                'base_in_qty'        => $restoreQty,
                'base_out_qty'       => 0,
                'reference_id'       => $saleId,
                'reference_no'       => $sale->invoice_no,
                'remarks'            => 'Sale Cancel: ' . $sale->invoice_no,
            ]);

            $this->recalculateProductStock($item->product_id);
        }

        $sale->update(['stock_status' => 3]);
    }

    public function decreaseVariantStock(int $productVariantId, float $baseQty): void
    {
        ProductVariant::where('id', $productVariantId)->decrement('stock_qty', $baseQty);
        // Sync available_stock_base_qty to the new stock_qty so they never diverge
        ProductVariant::where('id', $productVariantId)
            ->update(['available_stock_base_qty' => DB::raw('stock_qty')]);
    }

    public function decreaseBatchStock(int $batchId, float $baseQty): void
    {
        $batch = ProductBatch::findOrFail($batchId);
        $batch->decrement('available_qty', $baseQty);
        $batch->decrement('available_qty_base', $baseQty);

        $totalBatchQty = ProductBatch::where('product_variant_id', $batch->product_variant_id)->sum('available_qty');
        ProductVariant::where('id', $batch->product_variant_id)->update([
            'stock_qty'               => $totalBatchQty,
            'available_stock_base_qty'=> $totalBatchQty,
        ]);
    }

    public function recalculateProductStock(int $productId): void
    {
        // stock_qty lives on product_variants; nothing aggregated on products table
    }

    public function createSaleStockLedger(array $data): void
    {
        $variantBalance = $data['batch_id']
            ? (ProductBatch::find($data['batch_id'])?->available_qty ?? 0)
            : (ProductVariant::find($data['product_variant_id'])?->stock_qty ?? 0);

        $baseBalance = $data['batch_id']
            ? (ProductBatch::find($data['batch_id'])?->available_qty_base ?? $variantBalance)
            : (ProductVariant::find($data['product_variant_id'])?->available_stock_base_qty ?? $variantBalance);

        StockLedger::create([
            'organization_id'    => $data['organization_id'],
            'product_id'         => $data['product_id'],
            'product_variant_id' => $data['product_variant_id'],
            'batch_id'           => $data['batch_id'] ?? null,
            'unit_id'            => $data['unit_id'] ?? null,
            'unit_name'          => $data['unit_name'] ?? null,
            'conversion_qty'     => $data['conversion_qty'] ?? 1,
            'transaction_type'   => $data['transaction_type'],
            'in_qty'             => $data['in_qty'],
            'out_qty'            => $data['out_qty'],
            'base_in_qty'        => $data['base_in_qty'] ?? $data['in_qty'],
            'base_out_qty'       => $data['base_out_qty'] ?? $data['out_qty'],
            'balance_qty'        => $variantBalance,
            'base_balance_qty'   => $baseBalance,
            'reference_id'       => $data['reference_id'],
            'reference_no'       => $data['reference_no'],
            'remarks'            => $data['remarks'] ?? null,
        ]);
    }
}
