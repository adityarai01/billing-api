<?php
namespace App\Services;

use App\Models\HeldBill;
use App\Models\HeldBillItem;
use App\Enums\HeldBillStatusEnum;
use App\Events\HeldBillCreated;
use App\Events\HeldBillConverted;
use Illuminate\Support\Facades\DB;

class HeldBillService
{
    public function __construct(private SaleService $saleService) {}

    private function generateHoldNo(int $organizationId): string
    {
        $last = HeldBill::where('organization_id', $organizationId)->orderByDesc('id')->value('hold_no');
        $num  = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) $num = ((int) $m[1]) + 1;
        return 'HOLD-' . str_pad($num, 4, '0', STR_PAD_LEFT);
    }

    public function holdBill(int $organizationId, array $data, ?int $userId = null): HeldBill
    {
        return DB::transaction(function () use ($organizationId, $data, $userId) {
            $subtotal = array_sum(array_map(fn($i) => ($i['qty'] ?? 0) * ($i['unit_price'] ?? 0), $data['items']));

            $bill = HeldBill::create([
                'organization_id' => $organizationId,
                'customer_id'     => $data['customer_id'] ?? null,
                'hold_no'         => $this->generateHoldNo($organizationId),
                'hold_date'       => now(),
                'subtotal'        => $subtotal,
                'discount_amount' => 0,
                'grand_total'     => $subtotal,
                'remarks'         => $data['remarks'] ?? null,
                'status'          => HeldBillStatusEnum::Held->value,
                'created_by'      => $userId,
            ]);

            foreach ($data['items'] as $item) {
                HeldBillItem::create([
                    'organization_id'    => $organizationId,
                    'held_bill_id'       => $bill->id,
                    'product_id'         => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'],
                    'batch_id'           => $item['batch_id'] ?? null,
                    'product_name'       => $item['product_name'] ?? null,
                    'variant_name'       => $item['variant_name'] ?? null,
                    'batch_no'           => $item['batch_no'] ?? null,
                    'qty'                => $item['qty'],
                    'unit_price'         => $item['unit_price'] ?? 0,
                    'discount_amount'    => $item['discount_amount'] ?? 0,
                    'gst_amount'         => $item['gst_amount'] ?? 0,
                    'total_amount'       => ($item['qty'] ?? 0) * ($item['unit_price'] ?? 0),
                ]);
            }

            event(new HeldBillCreated($bill));
            return $bill->fresh();
        });
    }

    public function recallBill(int $organizationId, int $id): HeldBill
    {
        return HeldBill::where('organization_id', $organizationId)->where('id', $id)
            ->where('status', HeldBillStatusEnum::Held->value)
            ->with('heldBillItems')
            ->firstOrFail();
    }

    public function cancelHeldBill(int $organizationId, int $id): HeldBill
    {
        $bill = HeldBill::where('organization_id', $organizationId)->where('id', $id)
            ->where('status', HeldBillStatusEnum::Held->value)->firstOrFail();
        $bill->update(['status' => HeldBillStatusEnum::Cancelled->value, 'deleted' => 1]);
        return $bill->fresh();
    }

    public function convertToSale(int $organizationId, int $heldBillId, array $extraData, ?int $userId = null): \App\Models\Sale
    {
        return DB::transaction(function () use ($organizationId, $heldBillId, $extraData, $userId) {
            $bill = HeldBill::where('organization_id', $organizationId)->where('id', $heldBillId)
                ->where('status', HeldBillStatusEnum::Held->value)->with('heldBillItems')->firstOrFail();

            $items = $bill->heldBillItems->map(fn($i) => [
                'product_id'         => $i->product_id,
                'product_variant_id' => $i->product_variant_id,
                'batch_id'           => $i->batch_id,
                'product_name'       => $i->product_name,
                'variant_name'       => $i->variant_name,
                'batch_no'           => $i->batch_no,
                'qty'                => $i->qty,
                'unit_price'         => $i->unit_price,
                'discount_amount'    => $i->discount_amount,
                'gst_amount'         => $i->gst_amount,
            ])->toArray();

            $saleData = array_merge($extraData, [
                'customer_id'  => $extraData['customer_id'] ?? $bill->customer_id,
                'invoice_date' => $extraData['invoice_date'] ?? now(),
                'items'        => $items,
            ]);

            $sale = $this->saleService->createSale($organizationId, $saleData, $userId);
            $bill->update(['status' => HeldBillStatusEnum::ConvertedToSale->value]);

            event(new HeldBillConverted($bill));
            return $sale;
        });
    }

    public function searchHeldBills(int $organizationId, array $filters): array
    {
        $query = HeldBill::where('organization_id', $organizationId)->where('deleted', 0);

        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['customer_id'])) $query->where('customer_id', $filters['customer_id']);
        if (!empty($filters['date_from'])) $query->whereDate('hold_date', '>=', $filters['date_from']);
        if (!empty($filters['date_to'])) $query->whereDate('hold_date', '<=', $filters['date_to']);

        $perPage = $filters['per_page'] ?? 20;
        return $query
            ->with(['customer', 'heldBillItems' => fn($q) => $q->select(['id', 'held_bill_id', 'product_name', 'qty'])])
            ->withCount('heldBillItems as item_count')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->toArray();
    }
}
