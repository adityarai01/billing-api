<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierRequest;
use App\Services\SupplierService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    use ApiResponseTrait;
    public function __construct(private SupplierService $service) {}

    private function orgId(Request $r): int { return $r->attributes->get('organization_id'); }

    public function create(SupplierRequest $request): JsonResponse
    {
        $supplier = $this->service->create($this->orgId($request), $request->validated());
        return $this->successResponse($supplier, 'Supplier created successfully', 201);
    }

    public function update(SupplierRequest $request): JsonResponse
    {
        $supplier = $this->service->update($this->orgId($request), (int)$request->input('id'), $request->validated());
        return $this->successResponse($supplier, 'Supplier updated successfully');
    }

    public function delete(Request $request): JsonResponse
    {
        $this->service->delete($this->orgId($request), (int)$request->input('id'));
        return $this->successResponse(null, 'Supplier deleted successfully');
    }

    public function search(Request $request): JsonResponse
    {
        $result = $this->service->search($this->orgId($request), $request->all());
        return $this->successResponse($result, 'Suppliers fetched successfully');
    }

    public function details(Request $request, int $id): JsonResponse
    {
        $supplier = $this->service->details($this->orgId($request), $id);
        return $this->successResponse($supplier, 'Supplier details fetched');
    }
}
