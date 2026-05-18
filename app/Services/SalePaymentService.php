<?php
namespace App\Services;

use App\Models\Sale;
use App\Models\SalePayment;
use App\Enums\SalePaymentStatusEnum;
use App\Enums\SalePaymentModeEnum;

class SalePaymentService
{
    public function __construct(private CustomerLedgerService $ledgerService) {}

    public function createPaymentsForSale(int $organizationId, int $saleId, array $payments, ?int $customerId = null, ?int $createdBy = null): void
    {
        foreach ($payments as $payment) {
            if (($payment['amount'] ?? 0) <= 0) continue;

            $p = SalePayment::create([
                'organization_id' => $organizationId,
                'sale_id'         => $saleId,
                'customer_id'     => $customerId,
                'payment_mode'    => $payment['payment_mode'],
                'amount'          => $payment['amount'],
                'reference_no'    => $payment['reference_no'] ?? null,
                'payment_date'    => $payment['payment_date'] ?? now(),
                'remarks'         => $payment['remarks'] ?? null,
                'created_by'      => $createdBy,
            ]);

            if ($payment['payment_mode'] != SalePaymentModeEnum::Credit->value &&
                $payment['payment_mode'] != SalePaymentModeEnum::CreditNote->value &&
                $customerId) {
                $this->ledgerService->createPaymentLedger($p->id);
            }
        }

        $this->updateSalePaidDueStatus($saleId);
    }

    public function updateSalePaidDueStatus(int $saleId): void
    {
        $sale      = Sale::findOrFail($saleId);
        $paidTotal = SalePayment::where('sale_id', $saleId)->where('deleted', 0)->sum('amount');
        $due       = max(0, $sale->grand_total - $paidTotal);

        $status = SalePaymentStatusEnum::Unpaid->value;
        if ($paidTotal >= $sale->grand_total) $status = SalePaymentStatusEnum::Paid->value;
        elseif ($paidTotal > 0)              $status = SalePaymentStatusEnum::Partial->value;

        $sale->update([
            'paid_amount'    => round($paidTotal, 2),
            'due_amount'     => round($due, 2),
            'payment_status' => $status,
        ]);
    }

    public function searchPayments(int $organizationId, array $filters): array
    {
        $query = SalePayment::where('organization_id', $organizationId)->where('deleted', 0);

        if (!empty($filters['sale_id'])) $query->where('sale_id', $filters['sale_id']);
        if (!empty($filters['customer_id'])) $query->where('customer_id', $filters['customer_id']);
        if (!empty($filters['payment_mode'])) $query->where('payment_mode', $filters['payment_mode']);
        if (!empty($filters['date_from'])) $query->whereDate('payment_date', '>=', $filters['date_from']);
        if (!empty($filters['date_to'])) $query->whereDate('payment_date', '<=', $filters['date_to']);

        $perPage = $filters['per_page'] ?? 20;
        return $query->with(['sale', 'customer'])->orderByDesc('id')->paginate($perPage)->toArray();
    }
}
