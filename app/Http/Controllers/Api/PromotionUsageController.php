<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PromotionUsageService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromotionUsageController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private PromotionUsageService $usageService) {}

    private function orgId(Request $r): int
    {
        return $r->attributes->get('organization_id');
    }

    public function search(Request $request): JsonResponse
    {
        $result = $this->usageService->searchUsage(
            array_merge($request->all(), ['organization_id' => $this->orgId($request)])
        );
        return $this->successResponse($result, 'Usage history fetched successfully.');
    }

    public function summary(Request $request): JsonResponse
    {
        $result = $this->usageService->usageSummary(
            array_merge($request->all(), ['organization_id' => $this->orgId($request)])
        );
        return $this->successResponse($result, 'Usage summary fetched successfully.');
    }
}
