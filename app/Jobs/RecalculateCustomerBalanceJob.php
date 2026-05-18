<?php
namespace App\Jobs;
use App\Services\CustomerLedgerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculateCustomerBalanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public int $customerId) {}
    public function handle(CustomerLedgerService $ledgerService): void
    {
        $ledgerService->recalculateCustomerBalance($this->customerId);
    }
}
