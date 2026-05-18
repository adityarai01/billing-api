<?php
namespace App\Services;

use App\Models\SupplierCreditNote;
use App\Models\SupplierCreditNoteAdjustment;
use App\Models\Supplier;
use App\Events\SupplierCreditNoteAdjusted;
use Illuminate\Support\Facades\DB;

class SupplierCreditNoteService
{
    private function generateCreditNoteNo(int $organizationId): string
    {
        $last = SupplierCreditNote::where('organization_id', $organizationId)->orderByDesc('id')->value('supplier_credit_note_no');
        $num = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) $num = ((int) $m[1]) + 1;
        return 'SCN-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    public function createCreditNote(int $organizationId, array $data): SupplierCreditNote
    {
        return SupplierCreditNote::create(array_merge($data, [
            'organization_id'         => $organizationId,
            'supplier_credit_note_no' => $this->generateCreditNoteNo($organizationId),
            'balance_amount'          => $data['total_amount'],
            'used_amount'             => 0,
            'credit_status'           => 1, // Open
        ]));
    }

    public function adjustCreditNote(int $organizationId, array $data): SupplierCreditNoteAdjustment
    {
        return DB::transaction(function () use ($organizationId, $data) {
            $note = SupplierCreditNote::where('id', $data['supplier_credit_note_id'])
                ->where('organization_id', $organizationId)
                ->whereIn('credit_status', [1, 2])
                ->firstOrFail();
            if ((float)$data['adjusted_amount'] > (float)$note->balance_amount) {
                abort(422, 'Adjusted amount exceeds balance amount');
            }
            $adj = SupplierCreditNoteAdjustment::create(array_merge($data, ['organization_id' => $organizationId]));
            $used = $note->used_amount + $data['adjusted_amount'];
            $balance = max(0, $note->total_amount - $used);
            $status = $balance <= 0 ? 3 : 2;
            $note->update(['used_amount' => $used, 'balance_amount' => $balance, 'credit_status' => $status]);

            if (!empty($note->supplier_id)) {
                Supplier::where('id', $note->supplier_id)->decrement('current_balance', $data['adjusted_amount']);
            }

            event(new SupplierCreditNoteAdjusted($note));
            return $adj;
        });
    }

    public function searchCreditNotes(int $organizationId, array $filters = []): array
    {
        $query = SupplierCreditNote::with(['supplier'])->where('organization_id', $organizationId)->where('deleted', 0);
        if (!empty($filters['supplier_id'])) $query->where('supplier_id', $filters['supplier_id']);
        if (!empty($filters['credit_status'])) $query->where('credit_status', $filters['credit_status']);
        if (!empty($filters['search'])) {
            $q = $filters['search'];
            $query->where(fn ($b) => $b->where('supplier_credit_note_no', 'like', "%{$q}%"));
        }
        if (!empty($filters['from_date'])) $query->whereDate('credit_note_date', '>=', $filters['from_date']);
        if (!empty($filters['to_date'])) $query->whereDate('credit_note_date', '<=', $filters['to_date']);
        return $query->orderByDesc('id')->get()->toArray();
    }

    public function creditNoteDetails(int $organizationId, int $id): SupplierCreditNote
    {
        $note = SupplierCreditNote::with(['supplier', 'supplierCreditNoteAdjustments'])
            ->where('id', $id)->where('organization_id', $organizationId)->where('deleted', 0)->first();
        if (!$note) abort(404, 'Credit note not found');
        return $note;
    }
}
