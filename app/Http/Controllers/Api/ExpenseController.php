<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExpenseService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private ExpenseService $service) {}

    private function orgId(Request $request): int
    {
        return (int) $request->attributes->get('organization_id');
    }

    public function search(Request $request): JsonResponse
    {
        $data = $this->service->search($this->orgId($request), $request->all());
        return $this->successResponse($data, 'Expenses fetched');
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0'],
            'expense_date' => ['required', 'date'],
            'payment_mode' => ['required', 'integer', 'in:1,2,3,4,5,6'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string'],
        ]);

        $expense = $this->service->create($this->orgId($request), $validated);
        return $this->successResponse($expense->fresh(['category']), 'Expense created', 201);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0'],
            'expense_date' => ['required', 'date'],
            'payment_mode' => ['required', 'integer', 'in:1,2,3,4,5,6'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string'],
        ]);

        $expense = $this->service->update($this->orgId($request), (int) $validated['id'], $validated);
        return $this->successResponse($expense, 'Expense updated');
    }

    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $this->service->delete($this->orgId($request), (int) $request->input('id'));
        return $this->successResponse(null, 'Expense deleted');
    }
}
