<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PromotionCouponRequest;
use App\Http\Requests\ValidateCouponRequest;
use App\Services\PromotionCouponService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromotionCouponController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private PromotionCouponService $couponService) {}

    private function orgId(Request $r): int
    {
        return $r->attributes->get('organization_id');
    }

    public function create(PromotionCouponRequest $request): JsonResponse
    {
        try {
            $coupon = $this->couponService->create(
                array_merge($request->validated(), ['organization_id' => $this->orgId($request)])
            );
            return $this->successResponse($coupon, 'Coupon created successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function bulkCreate(Request $request): JsonResponse
    {
        try {
            $coupons = $this->couponService->bulkCreate(
                array_merge($request->all(), ['organization_id' => $this->orgId($request)])
            );
            return $this->successResponse($coupons, count($coupons) . ' coupons generated successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function update(PromotionCouponRequest $request): JsonResponse
    {
        try {
            $coupon = $this->couponService->update($request->input('id'), $request->validated());
            return $this->successResponse($coupon, 'Coupon updated successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function delete(Request $request): JsonResponse
    {
        try {
            $this->couponService->delete($request->input('id'));
            return $this->successResponse(null, 'Coupon deleted successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function search(Request $request): JsonResponse
    {
        $result = $this->couponService->search(
            array_merge($request->all(), ['organization_id' => $this->orgId($request)])
        );
        return $this->successResponse($result, 'Coupons fetched successfully.');
    }

    public function details(int $id): JsonResponse
    {
        try {
            $coupon = $this->couponService->details($id);
            return $this->successResponse($coupon, 'Coupon details fetched.');
        } catch (\Exception $e) {
            return $this->notFoundResponse('Coupon not found.');
        }
    }

    public function validate(ValidateCouponRequest $request): JsonResponse
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
}
