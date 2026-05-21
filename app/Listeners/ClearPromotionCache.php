<?php
namespace App\Listeners;

use App\Jobs\ClearPromotionCacheJob;

class ClearPromotionCache
{
    public function handle(object $event): void
    {
        $organizationId = $event->promotion->organization_id ?? null;
        if ($organizationId) {
            ClearPromotionCacheJob::dispatch($organizationId);
        }
    }
}
