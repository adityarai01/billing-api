<?php
namespace App\Jobs;

use App\Services\PromotionCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RebuildPromotionCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $organizationId) {}

    public function handle(PromotionCacheService $cacheService): void
    {
        $cacheService->rebuildPromotionCache($this->organizationId);
    }
}
