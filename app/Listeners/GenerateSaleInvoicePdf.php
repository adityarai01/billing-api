<?php
namespace App\Listeners;
use App\Events\SaleCreated;
use App\Jobs\GenerateSaleInvoicePdfJob;

class GenerateSaleInvoicePdf
{
    public function handle(SaleCreated $event): void
    {
        GenerateSaleInvoicePdfJob::dispatch($event->sale->id);
    }
}
