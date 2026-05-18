<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\StockAdjustmentRequest;
use App\Services\StockAdjustmentService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockAdjustmentController extends Controller
{
    use ApiResponseTrait;
    public function __construct(private StockAdjustmentService $service) {}
    private function orgId(Request $r): int { return $r->attributes->get('organization_id'); }

    public function create(StockAdjustmentRequest $request): JsonResponse
    {
        $adj = $this->service->createAdjustment($this->orgId($request), $request->validated());
        return $this->successResponse($adj, 'Stock adjustment created', 201);
    }

    public function approve(Request $request): JsonResponse
    {
        $adj = $this->service->approveAdjustment($this->orgId($request), (int)$request->input('id'));
        return $this->successResponse($adj, 'Stock adjustment approved');
    }

    public function reject(Request $request): JsonResponse
    {
        $adj = $this->service->rejectAdjustment($this->orgId($request), (int)$request->input('id'));
        return $this->successResponse($adj, 'Stock adjustment rejected');
    }

    public function search(Request $request): JsonResponse
    {
        $result = $this->service->searchAdjustments($this->orgId($request), $request->all());
        return $this->successResponse($result, 'Adjustments fetched');
    }

    public function details(Request $request, int $id): JsonResponse
    {
        $adj = $this->service->adjustmentDetails($this->orgId($request), $id);
        return $this->successResponse($adj, 'Adjustment details');
    }
}
