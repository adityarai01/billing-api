<?php
namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Enums\CustomerLedgerTypeEnum;
use App\Enums\CustomerBalanceTypeEnum;

class CustomerLedgerService
{
    public function createLedger(array $data): CustomerLedger
    {
        $lastBalance = CustomerLedger::where('organization_id', $data['organization_id'])
            ->where('customer_id', $data['customer_id'])
            ->orderByDesc('id')
            ->value('balance_amount') ?? 0;

        $balance = $lastBalance + ($data['debit_amount'] ?? 0) - ($data['credit_amount'] ?? 0);

        $ledger = CustomerLedger::create(array_merge($data, ['balance_amount' => round($balance, 2)]));
        $this->recalculateCustomerBalance($data['customer_id']);
        return $ledger;
    }

    public function createSaleLedger(int $saleId): void
    {
        $sale = Sale::findOrFail($saleId);
        if (!$sale->customer_id) return;

        $this->createLedger([
            'organization_id'  => $sale->organization_id,
            'customer_id'      => $sale->customer_id,
            'transaction_date' => $sale->invoice_date,
            'transaction_type' => CustomerLedgerTypeEnum::Sale->value,
            'reference_id'     => $sale->id,
            'reference_no'     => $sale->invoice_no,
            'debit_amount'     => $sale->grand_total,
            'credit_amount'    => 0,
            'remarks'          => 'Sale: ' . $sale->invoice_no,
        ]);
    }

    public function createPaymentLedger(int $salePaymentId): void
    {
        $payment = SalePayment::with('sale')->findOrFail($salePaymentId);
        if (!$payment->customer_id) return;

        $this->createLedger([
            'organization_id'  => $payment->organization_id,
            'customer_id'      => $payment->customer_id,
            'transaction_date' => $payment->payment_date,
            'transaction_type' => CustomerLedgerTypeEnum::SalePayment->value,
            'reference_id'     => $payment->sale_id,
            'reference_no'     => $payment->sale?->invoice_no,
            'debit_amount'     => 0,
            'credit_amount'    => $payment->amount,
            'remarks'          => 'Payment for: ' . ($payment->sale?->invoice_no ?? ''),
        ]);
    }

    public function createCreditNoteAdjustmentLedger(int $saleId, int $creditNoteId, float $amount): void
    {
        $sale = Sale::findOrFail($saleId);
        if (!$sale->customer_id) return;

        $this->createLedger([
            'organization_id'  => $sale->organization_id,
            'customer_id'      => $sale->customer_id,
            'transaction_date' => now(),
            'transaction_type' => CustomerLedgerTypeEnum::CreditNoteAdjustment->value,
            'reference_id'     => $saleId,
            'reference_no'     => $sale->invoice_no,
            'debit_amount'     => 0,
            'credit_amount'    => $amount,
            'remarks'          => 'Credit Note Adjustment for: ' . $sale->invoice_no,
        ]);
    }

    public function recalculateCustomerBalance(int $customerId): void
    {
        $lastLedger = CustomerLedger::where('customer_id', $customerId)
            ->orderByDesc('id')
            ->first();

        if (!$lastLedger) {
            Customer::where('id', $customerId)->update(['current_balance' => 0, 'balance_type' => CustomerBalanceTypeEnum::Receivable->value]);
            return;
        }

        $balance = $lastLedger->balance_amount;
        $type    = $balance >= 0 ? CustomerBalanceTypeEnum::Receivable->value : CustomerBalanceTypeEnum::Payable->value;

        Customer::where('id', $customerId)->update([
            'current_balance' => abs($balance),
            'balance_type'    => $type,
        ]);
    }

    public function getCustomerLedger(int $organizationId, array $filters): array
    {
        $query = CustomerLedger::where('organization_id', $organizationId);

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('transaction_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('transaction_date', '<=', $filters['date_to']);
        }

        $perPage = $filters['per_page'] ?? 20;
        return $query->with('customer')->orderBy('id')->paginate($perPage)->toArray();
    }
}
