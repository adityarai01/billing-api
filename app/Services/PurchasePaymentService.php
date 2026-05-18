<?php
namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Supplier;

class PurchasePaymentService
{
    public function createPayment(int $organizationId, array $data): PurchasePayment
    {
        $payment = PurchasePayment::create(array_merge($data, ['organization_id' => $organizationId]));
        $this->updatePurchasePaymentStatus($data['purchase_id']);

        if (!empty($payment->supplier_id) && (float) $payment->amount > 0) {
            Supplier::where('id', $payment->supplier_id)->decrement('current_balance', (float) $payment->amount);
        }

        return $payment;
    }

    public function updatePurchasePaymentStatus(int $purchaseId): void
    {
        $purchase = Purchase::find($purchaseId);
        if (!$purchase) return;
        $paidAmount = PurchasePayment::where('purchase_id', $purchaseId)->where('deleted', 0)->sum('amount');
        $due = max(0, $purchase->grand_total - $paidAmount);
        $status = 1; // Unpaid
        if ($paidAmount >= $purchase->grand_total) $status = 3; // Paid
        elseif ($paidAmount > 0) $status = 2; // Partial
        $purchase->update(['paid_amount' => $paidAmount, 'due_amount' => $due, 'payment_status' => $status]);
    }

    public function searchPayments(int $organizationId, array $filters = []): array
    {
        $query = PurchasePayment::with(['purchase', 'supplier'])
            ->where('organization_id', $organizationId)
            ->where('deleted', 0);
        if (!empty($filters['purchase_id'])) $query->where('purchase_id', $filters['purchase_id']);
        if (!empty($filters['supplier_id'])) $query->where('supplier_id', $filters['supplier_id']);
        if (!empty($filters['from_date'])) $query->whereDate('payment_date', '>=', $filters['from_date']);
        if (!empty($filters['to_date'])) $query->whereDate('payment_date', '<=', $filters['to_date']);
        return $query->orderByDesc('id')->get()->toArray();
    }
}
