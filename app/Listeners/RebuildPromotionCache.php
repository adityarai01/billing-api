<?php
namespace App\Listeners;

use App\Jobs\RebuildPromotionCacheJob;

class RebuildPromotionCache
{
    public function handle(object $event): void
    {
        $organizationId = $event->promotion->organization_id ?? null;
        if ($organizationId) {
            RebuildPromotionCacheJob::dispatch($organizationId);
        }
    }
}
