<?php
namespace App\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GeneratePurchasePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public int $purchaseId) {}
    public function handle(): void
    {
        // PDF generation logic — placeholder for future integration
        \Illuminate\Support\Facades\Log::info("GeneratePurchasePdfJob: purchaseId={$this->purchaseId}");
    }
}
