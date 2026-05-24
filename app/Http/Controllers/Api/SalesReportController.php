<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SalesReportCacheService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesReportController extends Controller
{
    use ApiResponseTrait;
    public function __construct(private SalesReportCacheService $service) {}
    private function orgId(Request $r): int { return $r->attributes->get('organization_id'); }

    public function sales(Request $request): JsonResponse
    {
        $result = $this->service->getSalesReport($this->orgId($request), $request->all());
        return $this->successResponse($result, 'Sales report fetched');
    }

    public function salesItem(Request $request): JsonResponse
    {
        $result = $this->service->getSalesItemReport($this->orgId($request), $request->all());
        return $this->successResponse($result, 'Sales item report fetched');
    }

    public function paymentMode(Request $request): JsonResponse
    {
        $result = $this->service->getPaymentModeReport($this->orgId($request), $request->all());
        return $this->successResponse($result, 'Payment mode report fetched');
    }

    public function dailySummary(Request $request): JsonResponse
    {
        $result = $this->service->getDailySummary($this->orgId($request), $request->all());
        return $this->successResponse($result, 'Daily summary fetched');
    }

    public function customerDue(Request $request): JsonResponse
    {
        $result = $this->service->getCustomerDueReport($this->orgId($request), $request->all());
        return $this->successResponse($result, 'Customer due report fetched');
    }

    public function profit(Request $request): JsonResponse
    {
        $result = $this->service->getProfitReport($this->orgId($request), $request->all());
        return $this->successResponse($result, 'Profit report fetched');
    }

    public function discount(Request $request): JsonResponse
    {
        $result = $this->service->getDiscountReport($this->orgId($request), $request->all());
        return $this->successResponse($result, 'Discount report fetched');
    }
}
