<?php
namespace App\Services;

use App\Models\DebitNote;
use App\Models\DebitNoteAdjustment;
use App\Models\PurchaseReturn;
use App\Events\DebitNoteAdjusted;
use Illuminate\Support\Facades\DB;

class DebitNoteService
{
    private function generateDebitNoteNo(int $organizationId): string
    {
        $last = DebitNote::where('organization_id', $organizationId)->orderByDesc('id')->value('debit_note_no');
        $num = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) $num = ((int) $m[1]) + 1;
        return 'DN-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    public function createFromPurchaseReturn(int $organizationId, PurchaseReturn $return): DebitNote
    {
        return DebitNote::create([
            'organization_id'    => $organizationId,
            'debit_note_no'      => $this->generateDebitNoteNo($organizationId),
            'supplier_id'        => $return->supplier_id,
            'purchase_id'        => $return->purchase_id,
            'purchase_return_id' => $return->id,
            'debit_note_date'    => $return->return_date,
            'debit_note_type'    => 1, // PurchaseReturn
            'total_amount'       => $return->grand_total,
            'used_amount'        => 0,
            'balance_amount'     => $return->grand_total,
            'debit_status'       => 1, // Open
        ]);
    }

    public function createManual(int $organizationId, array $data): DebitNote
    {
        return DebitNote::create(array_merge($data, [
            'organization_id' => $organizationId,
            'debit_note_no'   => $this->generateDebitNoteNo($organizationId),
            'balance_amount'  => $data['total_amount'],
            'used_amount'     => 0,
            'debit_status'    => 1,
        ]));
    }

    public function adjustDebitNote(int $organizationId, array $data): DebitNoteAdjustment
    {
        return DB::transaction(function () use ($organizationId, $data) {
            $note = DebitNote::where('id', $data['debit_note_id'])
                ->where('organization_id', $organizationId)
                ->whereIn('debit_status', [1, 2])
                ->firstOrFail();
            if ((float)$data['adjusted_amount'] > (float)$note->balance_amount) {
                abort(422, 'Adjusted amount exceeds balance amount');
            }
            $adj = DebitNoteAdjustment::create(array_merge($data, ['organization_id' => $organizationId]));
            $used = $note->used_amount + $data['adjusted_amount'];
            $balance = max(0, $note->total_amount - $used);
            $status = $balance <= 0 ? 3 : 2;
            $note->update(['used_amount' => $used, 'balance_amount' => $balance, 'debit_status' => $status]);
            event(new DebitNoteAdjusted($note));
            return $adj;
        });
    }

    public function searchDebitNotes(int $organizationId, array $filters = []): array
    {
        $query = DebitNote::with(['supplier'])->where('organization_id', $organizationId)->where('deleted', 0);
        if (!empty($filters['supplier_id'])) $query->where('supplier_id', $filters['supplier_id']);
        if (!empty($filters['debit_status'])) $query->where('debit_status', $filters['debit_status']);
        if (!empty($filters['search'])) {
            $q = $filters['search'];
            $query->where(fn ($b) => $b->where('debit_note_no', 'like', "%{$q}%"));
        }
        if (!empty($filters['from_date'])) $query->whereDate('debit_note_date', '>=', $filters['from_date']);
        if (!empty($filters['to_date'])) $query->whereDate('debit_note_date', '<=', $filters['to_date']);
        return $query->orderByDesc('id')->get()->toArray();
    }

    public function debitNoteDetails(int $organizationId, int $id): DebitNote
    {
        $note = DebitNote::with(['supplier', 'debitNoteAdjustments'])
            ->where('id', $id)->where('organization_id', $organizationId)->where('deleted', 0)->first();
        if (!$note) abort(404, 'Debit note not found');
        return $note;
    }
}
