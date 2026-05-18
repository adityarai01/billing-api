<?php
namespace App\Listeners;
use App\Jobs\ClearPurchaseCacheJob;
class ClearPurchaseCache
{
    public function handle(object $event): void
    {
        $orgId = $event->purchase?->organization_id ?? $event->purchaseReturn?->organization_id ?? null;
        if ($orgId) ClearPurchaseCacheJob::dispatch($orgId)->onQueue('default');
    }
}
