<?php
namespace App\Services;

use App\Models\Sale;
use App\Models\ProductVariant;
use App\Models\ProductBatch;
use App\Models\StockLedger;
use App\Enums\StockTransactionTypeEnum;

class SaleStockService
{
    public function deductStockForSale(int $saleId): void
    {
        $sale  = Sale::with('saleItems')->findOrFail($saleId);
        $items = $sale->saleItems()->where('deleted', 0)->where('is_free_item', 0)->get();

        foreach ($items as $item) {
            if ($item->batch_id) {
                $this->decreaseBatchStock($item->batch_id, (float) $item->qty);
            } else {
                $this->decreaseVariantStock($item->product_variant_id, (float) $item->qty);
            }
            $this->createSaleStockLedger([
                'organization_id'    => $sale->organization_id,
                'product_id'         => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'batch_id'           => $item->batch_id,
                'transaction_type'   => StockTransactionTypeEnum::Sale->value ?? 3,
                'in_qty'             => 0,
                'out_qty'            => (float) $item->qty,
                'reference_id'       => $saleId,
                'reference_no'       => $sale->invoice_no,
                'remarks'            => 'Sale: ' . $sale->invoice_no,
            ]);
            $this->recalculateProductStock($item->product_id);
        }

        $sale->update(['stock_status' => 1]);
    }

    public function reverseStockForSaleCancel(int $saleId): void
    {
        $sale  = Sale::with('saleItems')->findOrFail($saleId);
        $items = $sale->saleItems()->where('deleted', 0)->where('is_free_item', 0)->get();

        foreach ($items as $item) {
            if ($item->batch_id) {
                ProductBatch::where('id', $item->batch_id)->increment('available_qty', (float) $item->qty);
            } else {
                ProductVariant::where('id', $item->product_variant_id)->increment('stock_qty', (float) $item->qty);
            }
            $this->createSaleStockLedger([
                'organization_id'    => $sale->organization_id,
                'product_id'         => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'batch_id'           => $item->batch_id,
                'transaction_type'   => StockTransactionTypeEnum::Sale->value ?? 3,
                'in_qty'             => (float) $item->qty,
                'out_qty'            => 0,
                'reference_id'       => $saleId,
                'reference_no'       => $sale->invoice_no,
                'remarks'            => 'Sale Cancel: ' . $sale->invoice_no,
            ]);
            $this->recalculateProductStock($item->product_id);
        }

        $sale->update(['stock_status' => 3]);
    }

    public function decreaseVariantStock(int $productVariantId, float $qty): void
    {
        ProductVariant::where('id', $productVariantId)->decrement('stock_qty', $qty);
    }

    public function decreaseBatchStock(int $batchId, float $qty): void
    {
        $batch = ProductBatch::findOrFail($batchId);
        $batch->decrement('available_qty', $qty);
        ProductVariant::where('id', $batch->product_variant_id)
            ->update(['stock_qty' => ProductBatch::where('product_variant_id', $batch->product_variant_id)->sum('available_qty')]);
    }

    public function recalculateProductStock(int $productId): void
    {
        // stock_qty lives on product_variants; no current_stock column on products
    }

    public function createSaleStockLedger(array $data): void
    {
        $variantBalance = $data['batch_id']
            ? (ProductBatch::find($data['batch_id'])?->available_qty ?? 0)
            : (ProductVariant::find($data['product_variant_id'])?->stock_qty ?? 0);

        StockLedger::create([
            'organization_id'    => $data['organization_id'],
            'product_id'         => $data['product_id'],
            'product_variant_id' => $data['product_variant_id'],
            'batch_id'           => $data['batch_id'] ?? null,
            'transaction_type'   => $data['transaction_type'],
            'in_qty'             => $data['in_qty'],
            'out_qty'            => $data['out_qty'],
            'balance_qty'        => $variantBalance,
            'reference_id'       => $data['reference_id'],
            'reference_no'       => $data['reference_no'],
            'remarks'            => $data['remarks'] ?? null,
        ]);
    }
}
