<?php
namespace App\Listeners;
use App\Jobs\NearExpiryAlertJob;
class DispatchNearExpiryAlert
{
    public function handle(object $event): void
    {
        $orgId = $event->purchase?->organization_id ?? null;
        if ($orgId) NearExpiryAlertJob::dispatch($orgId)->onQueue('default');
    }
}
