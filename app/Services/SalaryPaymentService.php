<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\SalaryPayment;

class SalaryPaymentService
{
    public function search(int $orgId, array $filters = []): array
    {
        $query = SalaryPayment::where('organization_id', $orgId)->where('deleted', 0)
            ->with('user:id,name,employee_code');

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['pay_year'])) {
            $query->where('pay_year', $filters['pay_year']);
        }
        if (!empty($filters['pay_month'])) {
            $query->where('pay_month', $filters['pay_month']);
        }
        if (!empty($filters['from_date'])) {
            $query->where('payment_date', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->where('payment_date', '<=', $filters['to_date']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 30), 100);
        $total   = $query->count();
        $records = $query->orderByDesc('payment_date')->paginate($perPage);

        return ['record' => $records->items(), 'total_data' => $total];
    }

    public function recordPayment(int $orgId, array $data, int $createdBy): SalaryPayment
    {
        $payment = SalaryPayment::create(array_merge($data, [
            'organization_id' => $orgId,
            'created_by'      => $createdBy,
        ]));

        // Mark payroll as paid
        Payroll::where('id', $data['payroll_id'])->update(['status' => 4]);

        return $payment;
    }

    public function delete(int $orgId, int $id): void
    {
        SalaryPayment::where('organization_id', $orgId)->where('id', $id)->update(['deleted' => 1]);
    }
}
