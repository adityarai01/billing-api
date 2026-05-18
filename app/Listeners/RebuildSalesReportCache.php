<?php
namespace App\Listeners;
use App\Jobs\RebuildSalesReportCacheJob;

class RebuildSalesReportCache
{
    public function handle(object $event): void
    {
        $orgId = $event->sale->organization_id ?? null;
        if ($orgId) RebuildSalesReportCacheJob::dispatch($orgId);
    }
}
