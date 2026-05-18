<?php
namespace App\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class RebuildStockReportCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public int $organizationId) {}
    public function handle(): void
    {
        // Clear cached reports so next request rebuilds
        Cache::forget("org:{$this->organizationId}:stock:current");
        Cache::forget("org:{$this->organizationId}:stock:low");
        Cache::forget("org:{$this->organizationId}:stock:near-expiry");
    }
}
