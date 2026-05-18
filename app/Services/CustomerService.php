<?php
namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Enums\CustomerLedgerTypeEnum;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function create(int $organizationId, array $data): Customer
    {
        return DB::transaction(function () use ($organizationId, $data) {
            $customer = Customer::create(array_merge($data, ['organization_id' => $organizationId]));

            if (!empty($data['opening_balance']) && $data['opening_balance'] > 0) {
                CustomerLedger::create([
                    'organization_id'  => $organizationId,
                    'customer_id'      => $customer->id,
                    'transaction_date' => now(),
                    'transaction_type' => CustomerLedgerTypeEnum::OpeningBalance->value,
                    'debit_amount'     => $data['opening_balance'],
                    'credit_amount'    => 0,
                    'balance_amount'   => $data['opening_balance'],
                    'remarks'          => 'Opening Balance',
                    'created_by'       => $data['created_by'] ?? null,
                ]);
                $customer->update(['current_balance' => $data['opening_balance']]);
            }

            return $customer;
        });
    }

    public function update(int $organizationId, int $id, array $data): Customer
    {
        $customer = Customer::where('organization_id', $organizationId)->where('id', $id)->where('deleted', 0)->firstOrFail();
        $customer->update($data);
        return $customer->fresh();
    }

    public function delete(int $organizationId, int $id): bool
    {
        $customer = Customer::where('organization_id', $organizationId)->where('id', $id)->where('deleted', 0)->firstOrFail();
        $customer->update(['deleted' => 1, 'status' => 0]);
        return true;
    }

    public function search(int $organizationId, array $filters): array
    {
        $query = Customer::where('organization_id', $organizationId)->where('deleted', 0);

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('mobile_no', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('gstin', 'like', "%{$s}%");
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = $filters['per_page'] ?? 20;
        return $query->orderBy('name')->paginate($perPage)->toArray();
    }

    public function details(int $organizationId, int $id): Customer
    {
        return Customer::where('organization_id', $organizationId)->where('id', $id)->where('deleted', 0)->firstOrFail();
    }

    public function quickSearch(string $keyword, int $organizationId): array
    {
        return Customer::where('organization_id', $organizationId)
            ->where('deleted', 0)
            ->where('status', 1)
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('mobile_no', 'like', "%{$keyword}%");
            })
            ->select('id', 'name', 'mobile_no', 'current_balance', 'balance_type', 'loyalty_points')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
