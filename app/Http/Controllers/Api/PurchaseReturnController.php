<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseReturnRequest;
use App\Services\PurchaseReturnService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseReturnController extends Controller
{
    use ApiResponseTrait;
    public function __construct(private PurchaseReturnService $service) {}
    private function orgId(Request $r): int { return $r->attributes->get('organization_id'); }

    public function create(PurchaseReturnRequest $request): JsonResponse
    {
        $return = $this->service->createReturn($this->orgId($request), $request->validated());
        return $this->successResponse($return, 'Purchase return created', 201);
    }

    public function search(Request $request): JsonResponse
    {
        $result = $this->service->searchReturns($this->orgId($request), $request->all());
        return $this->successResponse($result, 'Purchase returns fetched');
    }

    public function details(Request $request, int $id): JsonResponse
    {
        $r = $this->service->returnDetails($this->orgId($request), $id);
        return $this->successResponse($r, 'Purchase return details');
    }

    public function cancel(Request $request): JsonResponse
    {
        $r = $this->service->cancelReturn($this->orgId($request), (int)$request->input('id'));
        return $this->successResponse($r, 'Purchase return cancelled');
    }
}
