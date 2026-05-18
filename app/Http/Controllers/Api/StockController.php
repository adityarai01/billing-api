<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Services\StockService;
use App\Services\StockLedgerService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockController extends Controller
{
    use ApiResponseTrait;
    public function __construct(private StockService $stockService, private StockLedgerService $ledgerService) {}
    private function orgId(Request $r): int { return $r->attributes->get('organization_id'); }

    public function currentReport(Request $request): JsonResponse
    {
        $data = $this->stockService->getCurrentStockReport($this->orgId($request), $request->all());
        return $this->successResponse($data, 'Current stock report');
    }

    public function batchReport(Request $request): JsonResponse
    {
        $data = $this->stockService->getBatchStockReport($this->orgId($request), $request->all());
        return $this->successResponse($data, 'Batch stock report');
    }

    public function lowStockReport(Request $request): JsonResponse
    {
        $data = $this->stockService->getLowStockReport($this->orgId($request), $request->all());
        return $this->successResponse($data, 'Low stock report');
    }

    public function nearExpiryReport(Request $request): JsonResponse
    {
        $data = $this->stockService->getNearExpiryReport($this->orgId($request), $request->all());
        return $this->successResponse($data, 'Near expiry report');
    }

    public function ledgerSearch(Request $request): JsonResponse
    {
        $data = $this->ledgerService->searchLedger($this->orgId($request), $request->all());
        return $this->successResponse($data, 'Stock ledger');
    }
}
