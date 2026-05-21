<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PromotionRequest;
use App\Services\PromotionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private PromotionService $promotionService) {}

    private function orgId(Request $r): int
    {
        return $r->attributes->get('organization_id');
    }

    public function create(PromotionRequest $request): JsonResponse
    {
        try {
            $promotion = $this->promotionService->createPromotionWithRules(
                array_merge($request->validated(), ['organization_id' => $this->orgId($request), 'created_by' => $request->attributes->get('user_id')])
            );
            return $this->successResponse($promotion, 'Promotion created successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function update(PromotionRequest $request): JsonResponse
    {
        try {
            $promotion = $this->promotionService->update(
                $request->input('id'),
                array_merge($request->validated(), ['updated_by' => $request->attributes->get('user_id')])
            );
            return $this->successResponse($promotion, 'Promotion updated successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function delete(Request $request): JsonResponse
    {
        try {
            $this->promotionService->delete($request->input('id'));
            return $this->successResponse(null, 'Promotion deleted successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function search(Request $request): JsonResponse
    {
        $result = $this->promotionService->search(
            array_merge($request->all(), ['organization_id' => $this->orgId($request)])
        );
        return $this->successResponse($result, 'Promotions fetched successfully.');
    }

    public function details(int $id): JsonResponse
    {
        try {
            $promotion = $this->promotionService->details($id);
            return $this->successResponse($promotion, 'Promotion details fetched successfully.');
        } catch (\Exception $e) {
            return $this->notFoundResponse('Promotion not found.');
        }
    }

    public function changeStatus(Request $request): JsonResponse
    {
        try {
            $promotion = $this->promotionService->changeStatus(
                $request->input('id'),
                $request->input('status')
            );
            return $this->successResponse($promotion, 'Status updated successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function active(Request $request): JsonResponse
    {
        $promotions = $this->promotionService->getActivePromotions($this->orgId($request));
        return $this->successResponse($promotions, 'Active promotions fetched.');
    }

    // ── Targets ──────────────────────────────────────────────────────────────────
    public function addTarget(Request $request): JsonResponse
    {
        try {
            $target = $this->promotionService->addTarget(
                $request->input('promotion_id'),
                $request->only(['target_type', 'target_id'])
            );
            return $this->successResponse($target, 'Target added.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function removeTarget(Request $request): JsonResponse
    {
        try {
            $this->promotionService->removeTarget($request->input('id'));
            return $this->successResponse(null, 'Target removed.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    // ── Buy-Get Rules ────────────────────────────────────────────────────────────
    public function addBuyGetRule(Request $request): JsonResponse
    {
        try {
            $rule = $this->promotionService->addBuyGetRule(
                $request->input('promotion_id'),
                $request->except('promotion_id')
            );
            return $this->successResponse($rule, 'Rule added.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function updateBuyGetRule(Request $request): JsonResponse
    {
        try {
            $rule = $this->promotionService->updateBuyGetRule(
                $request->input('id'),
                $request->except('id')
            );
            return $this->successResponse($rule, 'Rule updated.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function removeBuyGetRule(Request $request): JsonResponse
    {
        try {
            $this->promotionService->removeBuyGetRule($request->input('id'));
            return $this->successResponse(null, 'Rule removed.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    // ── Free Items ───────────────────────────────────────────────────────────────
    public function addFreeItem(Request $request): JsonResponse
    {
        try {
            $item = $this->promotionService->addFreeItem(
                $request->input('promotion_id'),
                $request->except('promotion_id')
            );
            return $this->successResponse($item, 'Free item added.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function removeFreeItem(Request $request): JsonResponse
    {
        try {
            $this->promotionService->removeFreeItem($request->input('id'));
            return $this->successResponse(null, 'Free item removed.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    // ── Combo Items ──────────────────────────────────────────────────────────────
    public function addComboItem(Request $request): JsonResponse
    {
        try {
            $item = $this->promotionService->addComboItem(
                $request->input('promotion_id'),
                $request->except('promotion_id')
            );
            return $this->successResponse($item, 'Combo item added.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function removeComboItem(Request $request): JsonResponse
    {
        try {
            $this->promotionService->removeComboItem($request->input('id'));
            return $this->successResponse(null, 'Combo item removed.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
