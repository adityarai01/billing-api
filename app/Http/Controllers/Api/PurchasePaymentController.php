<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Services\PurchasePaymentService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchasePaymentController extends Controller
{
    use ApiResponseTrait;
    public function __construct(private PurchasePaymentService $service) {}
    private function orgId(Request $r): int { return $r->attributes->get('organization_id'); }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'purchase_id'  => ['required', 'integer'],
            'payment_mode' => ['required', 'integer', 'in:1,2,3,4,5,6'],
            'amount'       => ['required', 'numeric', 'min:0'],
            'payment_date' => ['required', 'date'],
        ]);
        $payment = $this->service->createPayment($this->orgId($request), $request->all());
        return $this->successResponse($payment, 'Payment recorded', 201);
    }

    public function search(Request $request): JsonResponse
    {
        $result = $this->service->searchPayments($this->orgId($request), $request->all());
        return $this->successResponse($result, 'Payments fetched');
    }
}
