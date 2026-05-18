<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CustomerLedgerService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerLedgerController extends Controller
{
    use ApiResponseTrait;
    public function __construct(private CustomerLedgerService $service) {}
    private function orgId(Request $r): int { return $r->attributes->get('organization_id'); }

    public function search(Request $request): JsonResponse
    {
        $result = $this->service->getCustomerLedger($this->orgId($request), $request->all());
        return $this->successResponse($result, 'Customer ledger fetched');
    }

    public function customerLedger(Request $request, int $customerId): JsonResponse
    {
        $result = $this->service->getCustomerLedger($this->orgId($request), array_merge($request->all(), ['customer_id' => $customerId]));
        return $this->successResponse($result, 'Customer ledger fetched');
    }
}
