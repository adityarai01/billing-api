<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalePaymentRequest;
use App\Services\SalePaymentService;
use App\Services\CustomerLedgerService;
use App\Models\Sale;
use App\Events\SalePaymentCreated;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalePaymentController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private SalePaymentService    $service,
        private CustomerLedgerService $ledgerService,
    ) {}

    private function orgId(Request $r): int { return $r->attributes->get('organization_id'); }
    private function userId(Request $r): ?int { return $r->attributes->get('user_id'); }

    public function create(SalePaymentRequest $request): JsonResponse
    {
        $orgId  = $this->orgId($request);
        $data   = $request->validated();
        $sale   = Sale::where('organization_id', $orgId)->findOrFail($data['sale_id']);

        $this->service->createPaymentsForSale($orgId, $sale->id, [$data], $sale->customer_id, $this->userId($request));

        $updated = $sale->fresh();
        event(new SalePaymentCreated($updated));
        return $this->successResponse($updated, 'Payment recorded successfully', 201);
    }

    public function search(Request $request): JsonResponse
    {
        $result = $this->service->searchPayments($this->orgId($request), $request->all());
        return $this->successResponse($result, 'Payments fetched successfully');
    }
}
