<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupplierLedgerService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierLedgerController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private SupplierLedgerService $service) {}

    private function orgId(Request $request): int
    {
        return (int) $request->attributes->get('organization_id');
    }

    public function supplierLedger(Request $request, int $supplierId): JsonResponse
    {
        $data = $this->service->supplierLedger($this->orgId($request), $supplierId, $request->all());
        return $this->successResponse($data, 'Supplier ledger fetched');
    }
}
