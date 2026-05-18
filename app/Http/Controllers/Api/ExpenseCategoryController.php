<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExpenseCategoryService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private ExpenseCategoryService $service) {}

    private function orgId(Request $request): int
    {
        return (int) $request->attributes->get('organization_id');
    }

    public function search(Request $request): JsonResponse
    {
        $data = $this->service->search($this->orgId($request), $request->all());
        return $this->successResponse($data, 'Expense categories fetched');
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'icon' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        $category = $this->service->create($this->orgId($request), $validated);
        return $this->successResponse($category, 'Expense category created', 201);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:100'],
            'icon' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        $category = $this->service->update($this->orgId($request), (int) $validated['id'], $validated);
        return $this->successResponse($category, 'Expense category updated');
    }

    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $this->service->delete($this->orgId($request), (int) $request->input('id'));
        return $this->successResponse(null, 'Expense category deleted');
    }
}
