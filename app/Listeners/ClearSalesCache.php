<?php
namespace App\Listeners;
use App\Jobs\ClearSalesCacheJob;

class ClearSalesCache
{
    public function handle(object $event): void
    {
        $orgId = $event->sale->organization_id ?? $event->heldBill->organization_id ?? null;
        if ($orgId) ClearSalesCacheJob::dispatch($orgId);
    }
}
