<?php
namespace App\Listeners;
use App\Events\SaleCreated;
use App\Jobs\SendSaleInvoiceJob;

class SendInvoiceNotification
{
    public function handle(SaleCreated $event): void
    {
        if ($event->sale->customer_id) {
            SendSaleInvoiceJob::dispatch($event->sale->id, 'whatsapp');
        }
    }
}
