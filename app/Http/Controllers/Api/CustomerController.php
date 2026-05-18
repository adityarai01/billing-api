<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Services\CustomerService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use ApiResponseTrait;
    public function __construct(private CustomerService $service) {}
    private function orgId(Request $r): int { return $r->attributes->get('organization_id'); }
    private function userId(Request $r): ?int { return $r->attributes->get('user_id'); }

    public function create(CustomerRequest $request): JsonResponse
    {
        $data     = array_merge($request->validated(), ['created_by' => $this->userId($request)]);
        $customer = $this->service->create($this->orgId($request), $data);
        return $this->successResponse($customer, 'Customer created successfully', 201);
    }

    public function update(CustomerRequest $request): JsonResponse
    {
        $data     = array_merge($request->validated(), ['updated_by' => $this->userId($request)]);
        $customer = $this->service->update($this->orgId($request), (int) $request->input('id'), $data);
        return $this->successResponse($customer, 'Customer updated successfully');
    }

    public function delete(Request $request): JsonResponse
    {
        $this->service->delete($this->orgId($request), (int) $request->input('id'));
        return $this->successResponse(null, 'Customer deleted successfully');
    }

    public function search(Request $request): JsonResponse
    {
        $result = $this->service->search($this->orgId($request), $request->all());
        return $this->successResponse($result, 'Customers fetched successfully');
    }

    public function details(Request $request, int $id): JsonResponse
    {
        $customer = $this->service->details($this->orgId($request), $id);
        return $this->successResponse($customer, 'Customer details fetched');
    }

    public function quickSearch(Request $request): JsonResponse
    {
        $keyword  = $request->input('keyword', '');
        $result   = $this->service->quickSearch($keyword, $this->orgId($request));
        return $this->successResponse($result, 'Customer search result');
    }
}
