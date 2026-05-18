<?php
namespace App\Jobs;

use App\Services\SaleInvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateSaleInvoicePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $saleId,
        public ?int $templateType = null,
    ) {}

    public function handle(SaleInvoiceService $service): void
    {
        $path = $service->generateAndStorePdf($this->saleId, $this->templateType);

        Log::info('GenerateSaleInvoicePdfJob completed.', [
            'sale_id' => $this->saleId,
            'template_type' => $this->templateType,
            'invoice_pdf_path' => $path,
        ]);
    }
}
