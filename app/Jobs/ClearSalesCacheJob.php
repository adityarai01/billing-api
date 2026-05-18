<?php
namespace App\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class ClearSalesCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public int $organizationId) {}
    public function handle(): void
    {
        Cache::forget("org:{$this->organizationId}:sales:list");
        Cache::forget("org:{$this->organizationId}:sales:daily-summary");
        Cache::forget("org:{$this->organizationId}:sales:payment-mode-summary");
    }
}
