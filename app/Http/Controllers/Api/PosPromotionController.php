<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApplyPromotionRequest;
use App\Http\Requests\ValidateCouponRequest;
use App\Services\BuyXGetYService;
use App\Services\PromotionCalculationService;
use App\Services\PromotionCouponService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosPromotionController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private PromotionCalculationService $calcService,
        private PromotionCouponService      $couponService,
        private BuyXGetYService             $buyGetService,
    ) {}

    private function orgId(Request $r): int
    {
        return $r->attributes->get('organization_id');
    }

    public function calculate(ApplyPromotionRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $data      = [
                ...$validated,
                'organization_id' => $this->orgId($request),
                'items'           => $validated['cart_items'] ?? [],
            ];
            $result = $this->calcService->calculateCartPromotions($data);
            return $this->successResponse($result, 'Cart promotions calculated.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function applyCoupon(ValidateCouponRequest $request): JsonResponse
    {
        $result = $this->couponService->validateCoupon(
            $request->input('coupon_code'),
            $this->orgId($request),
            $request->validated()
        );

        if (!$result['valid']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'data'    => null,
            ], 200);
        }

        return $this->successResponse([
            'valid'           => true,
            'coupon'          => $result['coupon'],
            'discount_amount' => $result['discount_amount'],
        ], $result['message']);
    }

    public function removeCoupon(): JsonResponse
    {
        return $this->successResponse(['coupon' => null, 'discount_amount' => 0], 'Coupon removed.');
    }

    public function freeItemOptions(Request $request): JsonResponse
    {
        try {
            $promotionId = $request->input('promotion_id');
            $cartItems   = $request->input('cart_items', []);

            $options = $this->buyGetService->getEligibleFreeItems(
                \App\Models\PromotionBuyGetRule::where('promotion_id', $promotionId)->first(),
                $cartItems
            );

            return $this->successResponse($options, 'Free item options fetched.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function selectFreeItem(Request $request): JsonResponse
    {
        return $this->successResponse([
            'free_item' => $request->input('free_item'),
        ], 'Free item selected.');
    }
}
