<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ProductVariant;
use App\Services\StockAlertService;

class LowStockAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $organizationId, public ?int $productVariantId = null) {}

    public function handle(StockAlertService $alertService): void
    {
        if ($this->productVariantId) {
            $alertService->checkAndAlert($this->organizationId, $this->productVariantId);
            return;
        }

        // Bulk sweep for entire organization
        ProductVariant::whereHas('product', fn($q) => $q
            ->where('organization_id', $this->organizationId)
            ->where('deleted', 0))
            ->where('deleted', 0)
            ->chunk(50, function ($variants) use ($alertService) {
                foreach ($variants as $v) {
                    $alertService->checkAndAlert($this->organizationId, $v->id);
                }
            });
    }
}
