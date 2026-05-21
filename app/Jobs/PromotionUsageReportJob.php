<?php
namespace App\Jobs;

use App\Services\PromotionUsageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PromotionUsageReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int    $organizationId,
        private array  $filters = [],
    ) {}

    public function handle(PromotionUsageService $usageService): void
    {
        $usageService->usageSummary(array_merge($this->filters, [
            'organization_id' => $this->organizationId,
        ]));
    }
}
