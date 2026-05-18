<?php
namespace App\Services;

use App\Models\Expense;

class ExpenseService
{
    public function create(int $organizationId, array $data): Expense
    {
        return Expense::create(array_merge($data, [
            'organization_id' => $organizationId,
        ]));
    }

    public function update(int $organizationId, int $id, array $data): Expense
    {
        $expense = $this->findOrFail($organizationId, $id);
        $expense->update($data);
        return $expense->fresh(['category']);
    }

    public function delete(int $organizationId, int $id): void
    {
        $expense = $this->findOrFail($organizationId, $id);
        $expense->update([
            'deleted' => 1,
            'status' => 0,
        ]);
    }

    public function search(int $organizationId, array $filters = []): array
    {
        $query = Expense::query()
            ->with('category')
            ->where('organization_id', $organizationId)
            ->where('deleted', 0);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', '%' . $search . '%')
                    ->orWhere('reference_no', 'like', '%' . $search . '%')
                    ->orWhere('remarks', 'like', '%' . $search . '%');
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['payment_mode'])) {
            $query->where('payment_mode', $filters['payment_mode']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('expense_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('expense_date', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('expense_date')->orderByDesc('id')->get()->toArray();
    }

    public function findOrFail(int $organizationId, int $id): Expense
    {
        $expense = Expense::query()
            ->where('organization_id', $organizationId)
            ->where('id', $id)
            ->where('deleted', 0)
            ->first();

        if (!$expense) {
            abort(404, 'Expense not found');
        }

        return $expense;
    }
}
