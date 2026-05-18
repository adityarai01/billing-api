<?php
namespace App\Jobs;
use App\Models\ProductVariant;
use App\Models\ProductBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculateProductStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public int $productVariantId) {}
    public function handle(): void
    {
        $total = ProductBatch::where('product_variant_id', $this->productVariantId)->where('deleted', 0)->sum('available_qty');
        ProductVariant::where('id', $this->productVariantId)->update(['stock_qty' => $total]);
    }
}
