<?php
namespace App\Jobs;

use App\Services\SaleInvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSaleInvoiceWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public int $saleId) {}
    public function handle(SaleInvoiceService $service): void
    {
        $payload = $service->prepareSendPayload($this->saleId, 'whatsapp');

        Log::info('SendSaleInvoiceWhatsAppJob placeholder executed.', $payload);
    }
}
