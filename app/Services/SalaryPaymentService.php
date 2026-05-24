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
        $payroll = Payroll::where('organization_id', $orgId)
            ->where('id', $data['payroll_id'] ?? 0)
            ->where('deleted', 0)
            ->firstOrFail();

        $existingPayment = SalaryPayment::where('organization_id', $orgId)
            ->where('payroll_id', $payroll->id)
            ->where('deleted', 0)
            ->first();

        if ($existingPayment) {
            return $existingPayment->load('user:id,name,employee_code');
        }

        $payment = SalaryPayment::create([
            'organization_id' => $orgId,
            'payroll_id'      => $payroll->id,
            'user_id'         => $payroll->user_id,
            'pay_year'        => $payroll->pay_year,
            'pay_month'       => $payroll->pay_month,
            'amount'          => (float) ($data['amount'] ?? $payroll->net_salary),
            'payment_mode'    => $data['payment_mode'] ?? 1,
            'reference_no'    => $data['reference_no'] ?? null,
            'payment_date'    => $data['payment_date'] ?? now()->toDateString(),
            'remarks'         => $data['remarks'] ?? null,
            'created_by'      => $createdBy,
        ]);

        // Mark payroll as paid
        $payroll->update(['status' => 4, 'updated_by' => $createdBy]);

        return $payment->load('user:id,name,employee_code');
    }

    public function delete(int $orgId, int $id): void
    {
        $payment = SalaryPayment::where('organization_id', $orgId)
            ->where('id', $id)
            ->where('deleted', 0)
            ->first();

        if (!$payment) {
            return;
        }

        $payrollId = $payment->payroll_id;
        $payment->update(['deleted' => 1]);

        $hasActivePayments = SalaryPayment::where('organization_id', $orgId)
            ->where('payroll_id', $payrollId)
            ->where('deleted', 0)
            ->exists();

        if (!$hasActivePayments) {
            Payroll::where('organization_id', $orgId)
                ->where('id', $payrollId)
                ->update(['status' => 3]);
        }
    }
}
