<?php
namespace App\Services;

use App\Models\DebitNoteAdjustment;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Models\SupplierCreditNoteAdjustment;
use Illuminate\Support\Collection;

class SupplierLedgerService
{
    public function supplierLedger(int $organizationId, int $supplierId, array $filters = []): array
    {
        $supplier = Supplier::query()
            ->where('organization_id', $organizationId)
            ->where('id', $supplierId)
            ->where('deleted', 0)
            ->first();

        if (!$supplier) {
            abort(404, 'Supplier not found');
        }

        $entries = collect();
        $entries = $entries
            ->concat($this->purchaseEntries($organizationId, $supplierId, $filters))
            ->concat($this->paymentEntries($organizationId, $supplierId, $filters))
            ->concat($this->purchaseReturnEntries($organizationId, $supplierId, $filters))
            ->concat($this->debitNoteAdjustmentEntries($organizationId, $supplierId, $filters))
            ->concat($this->supplierCreditAdjustmentEntries($organizationId, $supplierId, $filters));

        $openingSigned = ((int) ($supplier->balance_type ?? 1) === 2 ? -1 : 1) * (float) ($supplier->opening_balance ?? 0);

        if (abs($openingSigned) > 0.0001) {
            $entries->prepend([
                'id' => 'opening-' . $supplierId,
                'transaction_date' => optional($supplier->created_at)->toDateString() ?? now()->toDateString(),
                'transaction_type' => 'Opening Balance',
                'reference_no' => 'OPENING',
                'debit_amount' => $openingSigned >= 0 ? abs($openingSigned) : 0,
                'credit_amount' => $openingSigned < 0 ? abs($openingSigned) : 0,
                'remarks' => 'Opening balance',
                'sort_order' => 0,
            ]);
        }

        $sorted = $entries
            ->sortBy([
                ['transaction_date', 'asc'],
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        $runningBalance = 0.0;
        $final = $sorted->map(function (array $entry) use (&$runningBalance) {
            $runningBalance += (float) $entry['debit_amount'] - (float) $entry['credit_amount'];
            $entry['balance_amount'] = round(abs($runningBalance), 2);
            $entry['balance_type'] = $runningBalance >= 0 ? 1 : 2;
            unset($entry['sort_order']);
            return $entry;
        })->values();

        $totalPurchase = $final->where('transaction_type', 'Purchase')->sum('debit_amount');
        $totalPaid = $final->filter(fn(array $entry) => in_array($entry['transaction_type'], [
            'Payment',
            'Purchase Return',
            'Debit Note Adjustment',
            'Supplier Credit Adjustment',
            'Opening Balance',
        ], true))->sum('credit_amount');

        return [
            'supplier' => $supplier,
            'summary' => [
                'total_purchase' => round($totalPurchase, 2),
                'total_paid' => round($totalPaid, 2),
                'balance_amount' => round(abs($runningBalance), 2),
                'balance_type' => $runningBalance >= 0 ? 1 : 2,
                'transaction_count' => $final->count(),
            ],
            'entries' => $final->all(),
        ];
    }

    private function purchaseEntries(int $organizationId, int $supplierId, array $filters): Collection
    {
        $query = Purchase::query()
            ->where('organization_id', $organizationId)
            ->where('supplier_id', $supplierId)
            ->where('deleted', 0);

        if (!empty($filters['date_from'])) {
            $query->whereDate('purchase_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('purchase_date', '<=', $filters['date_to']);
        }

        return $query->get()->map(fn(Purchase $purchase) => [
            'id' => 'purchase-' . $purchase->id,
            'transaction_date' => optional($purchase->purchase_date)->toDateString() ?? null,
            'transaction_type' => 'Purchase',
            'reference_no' => $purchase->purchase_no,
            'debit_amount' => (float) $purchase->grand_total,
            'credit_amount' => 0,
            'remarks' => $purchase->supplier_invoice_no ? 'Supplier Inv: ' . $purchase->supplier_invoice_no : ($purchase->remarks ?? ''),
            'sort_order' => 10,
        ]);
    }

    private function paymentEntries(int $organizationId, int $supplierId, array $filters): Collection
    {
        $query = PurchasePayment::query()
            ->with('purchase:id,purchase_no')
            ->where('organization_id', $organizationId)
            ->where('supplier_id', $supplierId)
            ->where('deleted', 0);

        if (!empty($filters['date_from'])) {
            $query->whereDate('payment_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('payment_date', '<=', $filters['date_to']);
        }

        return $query->get()->map(fn(PurchasePayment $payment) => [
            'id' => 'payment-' . $payment->id,
            'transaction_date' => optional($payment->payment_date)->toDateString() ?? null,
            'transaction_type' => 'Payment',
            'reference_no' => $payment->reference_no ?: ($payment->purchase?->purchase_no ?? 'PAY-' . $payment->id),
            'debit_amount' => 0,
            'credit_amount' => (float) $payment->amount,
            'remarks' => $payment->remarks ?? 'Purchase payment',
            'sort_order' => 20,
        ]);
    }

    private function purchaseReturnEntries(int $organizationId, int $supplierId, array $filters): Collection
    {
        $query = PurchaseReturn::query()
            ->with('purchase:id,purchase_no')
            ->where('organization_id', $organizationId)
            ->where('supplier_id', $supplierId)
            ->where('deleted', 0);

        if (!empty($filters['date_from'])) {
            $query->whereDate('return_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('return_date', '<=', $filters['date_to']);
        }

        return $query->get()->map(fn(PurchaseReturn $return) => [
            'id' => 'return-' . $return->id,
            'transaction_date' => optional($return->return_date)->toDateString() ?? null,
            'transaction_type' => 'Purchase Return',
            'reference_no' => $return->return_no,
            'debit_amount' => 0,
            'credit_amount' => (float) $return->grand_total,
            'remarks' => $return->purchase?->purchase_no ? 'Against ' . $return->purchase->purchase_no : ($return->reason ?? ''),
            'sort_order' => 30,
        ]);
    }

    private function debitNoteAdjustmentEntries(int $organizationId, int $supplierId, array $filters): Collection
    {
        $query = DebitNoteAdjustment::query()
            ->with('debitNote:id,debit_note_no,supplier_id')
            ->where('organization_id', $organizationId)
            ->whereHas('debitNote', fn($builder) => $builder->where('supplier_id', $supplierId));

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->get()->map(fn(DebitNoteAdjustment $adjustment) => [
            'id' => 'debit-adjustment-' . $adjustment->id,
            'transaction_date' => optional($adjustment->created_at)->toDateString() ?? null,
            'transaction_type' => 'Debit Note Adjustment',
            'reference_no' => $adjustment->debitNote?->debit_note_no ?? ('DNA-' . $adjustment->id),
            'debit_amount' => 0,
            'credit_amount' => (float) $adjustment->adjusted_amount,
            'remarks' => $adjustment->remarks ?? 'Adjusted against purchase',
            'sort_order' => 40,
        ]);
    }

    private function supplierCreditAdjustmentEntries(int $organizationId, int $supplierId, array $filters): Collection
    {
        $query = SupplierCreditNoteAdjustment::query()
            ->with('supplierCreditNote:id,supplier_credit_note_no,supplier_id')
            ->where('organization_id', $organizationId)
            ->whereHas('supplierCreditNote', fn($builder) => $builder->where('supplier_id', $supplierId));

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->get()->map(fn(SupplierCreditNoteAdjustment $adjustment) => [
            'id' => 'credit-adjustment-' . $adjustment->id,
            'transaction_date' => optional($adjustment->created_at)->toDateString() ?? null,
            'transaction_type' => 'Supplier Credit Adjustment',
            'reference_no' => $adjustment->supplierCreditNote?->supplier_credit_note_no ?? ('SCA-' . $adjustment->id),
            'debit_amount' => 0,
            'credit_amount' => (float) $adjustment->adjusted_amount,
            'remarks' => $adjustment->remarks ?? 'Supplier credit adjusted',
            'sort_order' => 50,
        ]);
    }
}
