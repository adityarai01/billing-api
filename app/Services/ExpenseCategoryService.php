<?php
namespace App\Services;

use App\Models\ExpenseCategory;

class ExpenseCategoryService
{
    public function create(int $organizationId, array $data): ExpenseCategory
    {
        return ExpenseCategory::create(array_merge($data, [
            'organization_id' => $organizationId,
        ]));
    }

    public function update(int $organizationId, int $id, array $data): ExpenseCategory
    {
        $category = $this->findOrFail($organizationId, $id);
        $category->update($data);
        return $category->fresh();
    }

    public function delete(int $organizationId, int $id): void
    {
        $category = $this->findOrFail($organizationId, $id);
        $category->update([
            'deleted' => 1,
            'status' => 0,
        ]);
    }

    public function search(int $organizationId, array $filters = []): array
    {
        $query = ExpenseCategory::query()
            ->where('organization_id', $organizationId)
            ->where('deleted', 0)
            ->withCount(['expenses' => fn($q) => $q->where('deleted', 0)])
            ->withSum(['expenses' => fn($q) => $q->where('deleted', 0)], 'amount');

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->orderBy('name')->get()->toArray();
    }

    public function findOrFail(int $organizationId, int $id): ExpenseCategory
    {
        $category = ExpenseCategory::query()
            ->where('organization_id', $organizationId)
            ->where('id', $id)
            ->where('deleted', 0)
            ->first();

        if (!$category) {
            abort(404, 'Expense category not found');
        }

        return $category;
    }
}
