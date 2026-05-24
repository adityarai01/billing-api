<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Organization;
use App\Jobs\LowStockAlertJob;

class CheckStockAlerts extends Command
{
    protected $signature   = 'stock:check-alerts {--org= : Specific organization ID}';
    protected $description = 'Check all product variants and fire low-stock / out-of-stock notifications';

    public function handle(): void
    {
        $orgId = $this->option('org');

        $query = Organization::where('deleted', 0);
        if ($orgId) $query->where('id', $orgId);

        $orgs = $query->pluck('id');
        foreach ($orgs as $id) {
            LowStockAlertJob::dispatchSync($id);
            $this->info("Checked stock alerts for org {$id}");
        }

        $this->info('Done.');
    }
}
