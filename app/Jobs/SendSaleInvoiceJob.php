<?php

namespace App\Jobs;

use App\Services\SaleInvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSaleInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $saleId,
        public string $channel,
        public ?int $templateType = null,
    ) {}

    public function handle(SaleInvoiceService $service): void
    {
        $payload = $service->prepareSendPayload($this->saleId, $this->channel, $this->templateType);

        Log::info('SendSaleInvoiceJob placeholder executed.', $payload);
    }
}
