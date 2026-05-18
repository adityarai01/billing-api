<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HeldBillService;
use App\Models\HeldBill;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HeldBillController extends Controller
{
    use ApiResponseTrait;
    public function __construct(private HeldBillService $service) {}
    private function orgId(Request $r): int { return $r->attributes->get('organization_id'); }

    public function search(Request $request): JsonResponse
    {
        $result = $this->service->searchHeldBills($this->orgId($request), $request->all());
        return $this->successResponse($result, 'Held bills fetched');
    }

    public function details(Request $request, int $id): JsonResponse
    {
        $bill = HeldBill::where('organization_id', $this->orgId($request))->where('id', $id)->with('heldBillItems')->firstOrFail();
        return $this->successResponse($bill, 'Held bill details fetched');
    }

    public function cancel(Request $request): JsonResponse
    {
        $bill = $this->service->cancelHeldBill($this->orgId($request), (int) $request->input('id'));
        return $this->successResponse($bill, 'Held bill cancelled');
    }
}
