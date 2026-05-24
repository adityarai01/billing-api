<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequest;
use App\Services\PurchaseService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    use ApiResponseTrait;
    public function __construct(private PurchaseService $service) {}
    private function orgId(Request $r): int { return $r->attributes->get('organization_id'); }

    public function create(PurchaseRequest $request): JsonResponse
    {
        $purchase = $this->service->createPurchase($this->orgId($request), $request->validated());
        return $this->successResponse($purchase, 'Purchase created successfully', 201);
    }

    public function update(PurchaseRequest $request): JsonResponse
    {
        $purchase = $this->service->updatePurchase($this->orgId($request), (int)$request->input('id'), $request->validated());
        return $this->successResponse($purchase, 'Purchase updated successfully');
    }

    public function search(Request $request): JsonResponse
    {
        $result = $this->service->searchPurchases($this->orgId($request), $request->all());
        return $this->successResponse($result, 'Purchases fetched successfully');
    }

    public function details(Request $request, int $id): JsonResponse
    {
        $purchase = $this->service->purchaseDetails($this->orgId($request), $id);
        return $this->successResponse($purchase, 'Purchase details fetched');
    }

    public function cancel(Request $request): JsonResponse
    {
        $purchase = $this->service->cancelPurchase($this->orgId($request), (int)$request->input('id'));
        return $this->successResponse($purchase, 'Purchase cancelled');
    }

    /** Create a purchase request (workflow_stage = 1) */
    public function createRequest(Request $request): JsonResponse
    {
        try {
            $purchase = $this->service->createWorkflow($this->orgId($request), $request->all(), 1, $request->attributes->get('user_id'));
            return $this->successResponse($purchase, 'Purchase request created', 201);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /** Approve PR → PO (workflow_stage = 2) */
    public function approvePR(Request $request): JsonResponse
    {
        try {
            $purchase = $this->service->approveWorkflow($this->orgId($request), (int)$request->input('id'), $request->attributes->get('user_id'));
            return $this->successResponse($purchase, 'Purchase request approved');
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /** Receive goods → GRN (workflow_stage = 3, stock updated) */
    public function receiveGoods(Request $request): JsonResponse
    {
        try {
            $purchase = $this->service->receiveWorkflow($this->orgId($request), (int)$request->input('id'), $request->all(), $request->attributes->get('user_id'));
            return $this->successResponse($purchase, 'Goods received, stock updated');
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /** Reject PR */
    public function rejectPR(Request $request): JsonResponse
    {
        try {
            $purchase = $this->service->rejectWorkflow($this->orgId($request), (int)$request->input('id'), $request->input('reason', ''));
            return $this->successResponse($purchase, 'Purchase request rejected');
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
