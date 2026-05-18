<?php
namespace App\Listeners;
use App\Jobs\RebuildStockReportCacheJob;
class ClearStockCache
{
    public function handle(object $event): void
    {
        $orgId = $event->purchase?->organization_id
            ?? $event->purchaseReturn?->organization_id
            ?? $event->adjustment?->organization_id
            ?? null;
        if ($orgId) RebuildStockReportCacheJob::dispatch($orgId)->onQueue('default');
    }
}
