<?php
namespace App\Services;

use App\Models\StockLedger;

class StockLedgerService
{
    public function createLedger(array $data): StockLedger
    {
        return StockLedger::create($data);
    }

    public function searchLedger(int $organizationId, array $filters = []): array
    {
        $query = StockLedger::with(['product', 'productVariant'])
            ->where('organization_id', $organizationId);
        if (!empty($filters['product_variant_id'])) $query->where('product_variant_id', $filters['product_variant_id']);
        if (!empty($filters['product_id'])) $query->where('product_id', $filters['product_id']);
        if (!empty($filters['batch_id'])) $query->where('batch_id', $filters['batch_id']);
        if (!empty($filters['transaction_type'])) $query->where('transaction_type', $filters['transaction_type']);
        if (!empty($filters['from_date'])) $query->whereDate('created_at', '>=', $filters['from_date']);
        if (!empty($filters['to_date'])) $query->whereDate('created_at', '<=', $filters['to_date']);
        return $query->orderByDesc('id')->limit(200)->get()->toArray();
    }
}
