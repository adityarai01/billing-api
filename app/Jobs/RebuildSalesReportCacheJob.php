<?php
namespace App\Jobs;
use App\Services\SalesReportCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RebuildSalesReportCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public int $organizationId) {}
    public function handle(SalesReportCacheService $cacheService): void
    {
        $cacheService->clearSalesCache($this->organizationId);
    }
}
