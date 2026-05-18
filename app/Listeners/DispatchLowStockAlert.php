<?php
namespace App\Listeners;
use App\Jobs\LowStockAlertJob;
class DispatchLowStockAlert
{
    public function handle(object $event): void
    {
        $orgId = $event->purchase?->organization_id ?? $event->adjustment?->organization_id ?? null;
        if ($orgId) LowStockAlertJob::dispatch($orgId)->onQueue('default');
    }
}
