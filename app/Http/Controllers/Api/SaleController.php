<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SaleService;
use App\Services\SaleInvoiceService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private SaleService        $saleService,
        private SaleInvoiceService $invoiceService,
    ) {}

    private function orgId(Request $r): int { return $r->attributes->get('organization_id'); }

    public function search(Request $request): JsonResponse
    {
        $result = $this->saleService->searchSales($this->orgId($request), $request->all());
        return $this->successResponse($result, 'Sales fetched successfully');
    }

    public function details(Request $request, int $id): JsonResponse
    {
        $sale = $this->saleService->saleDetails($this->orgId($request), $id);
        return $this->successResponse($sale, 'Sale details fetched');
    }

    public function cancel(Request $request): JsonResponse
    {
        $sale = $this->saleService->cancelSale($this->orgId($request), (int) $request->input('id'));
        return $this->successResponse($sale, 'Sale cancelled successfully');
    }

    public function processReturn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sale_id' => ['required', 'integer'],
            'reason' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'integer'],
            'items.*.return_qty' => ['required', 'numeric', 'min:0.001'],
            'items.*.reason' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $this->saleService->processReturn($this->orgId($request), (int) $validated['sale_id'], $validated);
        return $this->successResponse($result, 'Sale return processed successfully');
    }

    public function invoice(Request $request, int $id): JsonResponse
    {
        $data = $this->invoiceService->getInvoiceData($this->orgId($request), $id);
        return $this->successResponse($data, 'Invoice data fetched');
    }

    public function thermalInvoice(Request $request, int $id): JsonResponse
    {
        $data = $this->invoiceService->generateThermalInvoice($this->orgId($request), $id);
        return $this->successResponse($data, 'Thermal invoice data fetched');
    }

    public function a4Invoice(Request $request, int $id): JsonResponse
    {
        $data = $this->invoiceService->generateA4Invoice($this->orgId($request), $id);
        return $this->successResponse($data, 'A4 invoice data fetched');
    }
}
