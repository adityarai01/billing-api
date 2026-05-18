<?php

namespace App\Http\Controllers\Api;

use App\Enums\InvoicePaperSizeEnum;
use App\Enums\InvoiceTemplateTypeEnum;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateSaleInvoicePdfJob;
use App\Jobs\SendSaleInvoiceJob;
use App\Services\SaleInvoiceService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class SaleInvoiceController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private SaleInvoiceService $saleInvoiceService,
    ) {}

    public function show(Request $request, int $saleId): JsonResponse
    {
        return $this->preview($request, $saleId);
    }

    public function preview(Request $request, int $saleId): JsonResponse
    {
        $templateType = $this->resolveTemplateType($request);
        $data = $this->saleInvoiceService->getInvoiceData($this->orgId($request), $saleId, $templateType);

        return $this->successResponse($data, 'Invoice preview fetched successfully');
    }

    public function thermal80mm(Request $request, int $saleId)
    {
        $data = $this->saleInvoiceService->getInvoiceData(
            $this->orgId($request),
            $saleId,
            InvoiceTemplateTypeEnum::Thermal80mm->value
        );

        return response()->view('invoices.thermal-80mm', $data);
    }

    public function thermal58mm(Request $request, int $saleId)
    {
        $data = $this->saleInvoiceService->getInvoiceData(
            $this->orgId($request),
            $saleId,
            InvoiceTemplateTypeEnum::Thermal58mm->value
        );

        return response()->view('invoices.thermal-58mm', $data);
    }

    public function a4Gst(Request $request, int $saleId)
    {
        $data = $this->saleInvoiceService->getInvoiceData(
            $this->orgId($request),
            $saleId,
            InvoiceTemplateTypeEnum::A4GST->value
        );

        return response()->view('invoices.a4-gst', $data);
    }

    public function simple(Request $request, int $saleId)
    {
        $data = $this->saleInvoiceService->getInvoiceData(
            $this->orgId($request),
            $saleId,
            InvoiceTemplateTypeEnum::SimpleRetail->value
        );

        return response()->view('invoices.simple-retail', $data);
    }

    public function pdf(Request $request, int $saleId)
    {
        try {
            $templateType = $this->resolveTemplateType($request) ?? InvoiceTemplateTypeEnum::A4GST->value;
            $pdf = $this->saleInvoiceService->generatePdf($this->orgId($request), $saleId, $templateType);
            $fileName = 'invoice-' . $saleId . '.pdf';

            return $request->boolean('download')
                ? $pdf->download($fileName)
                : $pdf->stream($fileName);
        } catch (RuntimeException $exception) {
            return $this->errorResponse($exception->getMessage(), 503);
        }
    }

    public function generatePdf(Request $request, int $saleId): JsonResponse
    {
        $templateType = $this->resolveTemplateType($request) ?? InvoiceTemplateTypeEnum::A4GST->value;

        GenerateSaleInvoicePdfJob::dispatch($saleId, $templateType);

        return $this->successResponse([
            'sale_id' => $saleId,
            'template_type' => $templateType,
        ], 'Invoice PDF generation queued', 202);
    }

    public function sendWhatsapp(Request $request, int $saleId): JsonResponse
    {
        $templateType = $this->resolveTemplateType($request) ?? InvoiceTemplateTypeEnum::A4GST->value;
        SendSaleInvoiceJob::dispatch($saleId, 'whatsapp', $templateType);

        return $this->successResponse([
            'sale_id' => $saleId,
            'channel' => 'whatsapp',
        ], 'Invoice WhatsApp job queued', 202);
    }

    public function sendEmail(Request $request, int $saleId): JsonResponse
    {
        $templateType = $this->resolveTemplateType($request) ?? InvoiceTemplateTypeEnum::A4GST->value;
        SendSaleInvoiceJob::dispatch($saleId, 'email', $templateType);

        return $this->successResponse([
            'sale_id' => $saleId,
            'channel' => 'email',
        ], 'Invoice email job queued', 202);
    }

    public function settings(Request $request): JsonResponse
    {
        return $this->successResponse([
            'settings' => $this->saleInvoiceService->getPrintSettings($this->orgId($request)),
            'template_options' => InvoiceTemplateTypeEnum::options(),
            'paper_size_options' => InvoicePaperSizeEnum::options(),
        ], 'Invoice settings fetched successfully');
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'invoice_prefix' => ['nullable', 'string', 'max:20'],
            'invoice_start_no' => ['nullable', 'integer', 'min:1'],
            'invoice_template' => ['nullable', 'integer', 'in:' . implode(',', InvoiceTemplateTypeEnum::values())],
            'thermal_paper_size' => ['nullable', 'integer', 'in:' . implode(',', InvoicePaperSizeEnum::values())],
            'print_after_sale' => ['nullable', 'boolean'],
            'show_logo_on_invoice' => ['nullable', 'boolean'],
            'show_gst_on_invoice' => ['nullable', 'boolean'],
            'show_discount_on_invoice' => ['nullable', 'boolean'],
            'show_hsn_on_invoice' => ['nullable', 'boolean'],
            'show_batch_on_invoice' => ['nullable', 'boolean'],
            'show_expiry_on_invoice' => ['nullable', 'boolean'],
            'show_terms_on_invoice' => ['nullable', 'boolean'],
            'show_signature_on_invoice' => ['nullable', 'boolean'],
            'terms_conditions' => ['nullable', 'string'],
            'invoice_footer_message' => ['nullable', 'string'],
        ]);

        return $this->successResponse([
            'settings' => $this->saleInvoiceService->updatePrintSettings($this->orgId($request), $data),
            'template_options' => InvoiceTemplateTypeEnum::options(),
            'paper_size_options' => InvoicePaperSizeEnum::options(),
        ], 'Invoice settings updated successfully');
    }

    private function orgId(Request $request): int
    {
        return (int) $request->attributes->get('organization_id');
    }

    private function resolveTemplateType(Request $request): ?int
    {
        $templateType = $request->integer('template_type');

        return InvoiceTemplateTypeEnum::tryFrom($templateType)?->value;
    }
}
