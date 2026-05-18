<?php
namespace App\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ProductBatch;

class NearExpiryAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public int $organizationId, public int $days = 30) {}
    public function handle(): void
    {
        $limitDate = now()->addDays($this->days)->toDateString();
        $batches = ProductBatch::whereHas('productVariant.product', fn($q) => $q->where('organization_id', $this->organizationId))
            ->where('deleted', 0)->where('status', 1)->where('available_qty', '>', 0)
            ->whereNotNull('expiry_date')->where('expiry_date', '<=', $limitDate)->get();
        \Illuminate\Support\Facades\Log::info("NearExpiryAlertJob: {$batches->count()} batches near expiry for org {$this->organizationId}");
    }
}
