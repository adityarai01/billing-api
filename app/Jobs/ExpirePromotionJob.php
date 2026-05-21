<?php
namespace App\Jobs;

use App\Models\Promotion;
use App\Services\PromotionCacheService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExpirePromotionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(PromotionCacheService $cacheService): void
    {
        $expiredOrgs = Promotion::where('status', 1)
            ->where('deleted', 0)
            ->whereNotNull('end_date')
            ->where('end_date', '<', Carbon::now())
            ->pluck('organization_id')
            ->unique();

        Promotion::where('status', 1)
            ->where('deleted', 0)
            ->whereNotNull('end_date')
            ->where('end_date', '<', Carbon::now())
            ->update(['status' => 0]);

        foreach ($expiredOrgs as $orgId) {
            $cacheService->clearPromotionCache($orgId);
        }
    }
}
