<?php
namespace App\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ProductVariant;

class LowStockAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public int $organizationId) {}
    public function handle(): void
    {
        $lowStockVariants = ProductVariant::whereHas('product', fn($q) => $q->where('organization_id', $this->organizationId)->where('deleted', 0))
            ->where('deleted', 0)
            ->whereRaw('stock_qty <= low_stock_alert AND low_stock_alert > 0')
            ->get();
        // TODO: send notifications / create alert records
        \Illuminate\Support\Facades\Log::info("LowStockAlertJob: {$lowStockVariants->count()} low stock variants for org {$this->organizationId}");
    }
}
