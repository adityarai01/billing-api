<?php
namespace App\Services;

use App\Models\ProductVariant;
use App\Models\ProductBatch;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function increaseStock(array $data): void
    {
        // $data: organization_id, product_variant_id, batch_id (nullable), qty
        if (!empty($data['batch_id'])) {
            ProductBatch::where('id', $data['batch_id'])
                ->increment('available_qty', $data['qty']);
            $this->recalculateVariantStockFromBatches($data['product_variant_id']);
        } else {
            ProductVariant::where('id', $data['product_variant_id'])->increment('stock_qty', $data['qty']);
            ProductVariant::where('id', $data['product_variant_id'])
                ->update(['available_stock_base_qty' => DB::raw('stock_qty')]);
        }
        $this->recalculateProductStock($data['product_id'] ?? $this->getProductIdFromVariant($data['product_variant_id']));
    }

    public function decreaseStock(array $data): void
    {
        if (!empty($data['batch_id'])) {
            ProductBatch::where('id', $data['batch_id'])
                ->decrement('available_qty', $data['qty']);
            $this->recalculateVariantStockFromBatches($data['product_variant_id']);
        } else {
            ProductVariant::where('id', $data['product_variant_id'])->decrement('stock_qty', $data['qty']);
            ProductVariant::where('id', $data['product_variant_id'])
                ->update(['available_stock_base_qty' => DB::raw('stock_qty')]);
        }
        $this->recalculateProductStock($data['product_id'] ?? $this->getProductIdFromVariant($data['product_variant_id']));
    }

    public function recalculateVariantStock(int $productVariantId): void
    {
        $this->recalculateVariantStockFromBatches($productVariantId);
    }

    private function recalculateVariantStockFromBatches(int $productVariantId): void
    {
        $totalBatchStock = ProductBatch::where('product_variant_id', $productVariantId)
            ->where('deleted', 0)
            ->sum('available_qty');
        ProductVariant::where('id', $productVariantId)->update(['stock_qty' => $totalBatchStock]);
    }

    public function recalculateProductStock(int $productId): void
    {
        $totalStock = ProductVariant::where('product_id', $productId)
            ->where('deleted', 0)
            ->sum('stock_qty');
        // Store on product if there's a stock column — skip if not present
        // Product::where('id', $productId)->update(['current_stock' => $totalStock]);
    }

    private function getProductIdFromVariant(int $variantId): int
    {
        return ProductVariant::where('id', $variantId)->value('product_id') ?? 0;
    }

    public function getCurrentStockReport(int $organizationId, array $filters = []): array
    {
        $cacheKey = "org:{$organizationId}:stock:current:" . md5(json_encode($filters));
        return Cache::remember($cacheKey, 30, function () use ($organizationId, $filters) {
            $query = DB::table('product_variants as pv')
                ->join('products as p', 'p.id', '=', 'pv.product_id')
                ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
                ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
                ->leftJoin('units as u', 'u.id', '=', 'pv.unit_id')
                ->where('p.organization_id', $organizationId)
                ->where('p.deleted', 0)
                ->where('pv.deleted', 0)
                ->select([
                    'p.id as product_id',
                    'pv.id as product_variant_id',
                    'p.name as product_name',
                    'pv.variant_name',
                    'pv.sku',
                    'pv.barcode',
                    'c.name as category_name',
                    'b.name as brand_name',
                    'u.name as unit_name',
                    'pv.stock_qty as available_stock',
                    'pv.low_stock_alert as low_stock_alert_qty',
                    'pv.purchase_price',
                    'pv.selling_price',
                    DB::raw('CAST(pv.stock_qty * pv.purchase_price AS DECIMAL(12,2)) as stock_value'),
                    DB::raw("CASE WHEN pv.stock_qty <= 0 THEN 'Out of Stock' WHEN pv.stock_qty <= pv.low_stock_alert THEN 'Low Stock' ELSE 'In Stock' END as stock_status"),
                ]);
            if (!empty($filters['category_id'])) $query->where('p.category_id', $filters['category_id']);
            if (!empty($filters['brand_id'])) $query->where('p.brand_id', $filters['brand_id']);
            if (isset($filters['stock_status'])) {
                if ($filters['stock_status'] === 'out') $query->where('pv.stock_qty', '<=', 0);
                elseif ($filters['stock_status'] === 'low') $query->whereRaw('pv.stock_qty > 0 AND pv.stock_qty <= pv.low_stock_alert');
                elseif ($filters['stock_status'] === 'in') $query->whereRaw('pv.stock_qty > pv.low_stock_alert');
            }
            return $query->orderBy('p.name')->get()->toArray();
        });
    }

    public function getBatchStockReport(int $organizationId, array $filters = []): array
    {
        $cacheKey = "org:{$organizationId}:stock:batch:" . md5(json_encode($filters));
        return Cache::remember($cacheKey, 300, function () use ($organizationId, $filters) {
            $today = now()->toDateString();
            $nearExpiryDate = now()->addDays(30)->toDateString();
            $query = DB::table('product_batches as pb')
                ->join('product_variants as pv', 'pv.id', '=', 'pb.product_variant_id')
                ->join('products as p', 'p.id', '=', 'pv.product_id')
                ->where('p.organization_id', $organizationId)
                ->where('pb.deleted', 0)
                ->where('pb.status', 1)
                ->where('pb.available_qty', '>', 0)
                ->select([
                    'p.name as product_name',
                    'pv.variant_name',
                    'pb.batch_no',
                    'pb.mfg_date',
                    'pb.expiry_date',
                    'pb.available_qty',
                    'pb.mrp',
                    'pb.selling_price',
                    DB::raw("CASE WHEN pb.expiry_date < '{$today}' THEN 'Expired' WHEN pb.expiry_date <= '{$nearExpiryDate}' THEN 'Near Expiry' ELSE 'Valid' END as expiry_status"),
                ]);
            return $query->orderBy('pb.expiry_date')->get()->toArray();
        });
    }

    public function getLowStockReport(int $organizationId, array $filters = []): array
    {
        $cacheKey = "org:{$organizationId}:stock:low:" . md5(json_encode($filters));
        return Cache::remember($cacheKey, 300, function () use ($organizationId, $filters) {
            return DB::table('product_variants as pv')
                ->join('products as p', 'p.id', '=', 'pv.product_id')
                ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
                ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
                ->leftJoin('units as u', 'u.id', '=', 'pv.unit_id')
                ->where('p.organization_id', $organizationId)
                ->where('p.deleted', 0)
                ->where('pv.deleted', 0)
                ->whereRaw('pv.stock_qty <= pv.low_stock_alert')
                ->select([
                    'p.name as product_name',
                    'pv.variant_name',
                    'pv.sku',
                    'pv.barcode',
                    'pv.stock_qty as available_stock',
                    'pv.low_stock_alert as low_stock_alert_qty',
                    'c.name as category_name',
                    'b.name as brand_name',
                    'u.name as unit_name',
                    DB::raw("CASE WHEN pv.stock_qty <= 0 THEN 'Out of Stock' ELSE 'Low Stock' END as stock_status"),
                ])
                ->orderBy('pv.stock_qty')
                ->get()->toArray();
        });
    }

    public function getNearExpiryReport(int $organizationId, array $filters = []): array
    {
        $days = (int) ($filters['days'] ?? 30);
        $cacheKey = "org:{$organizationId}:stock:near-expiry:" . md5(json_encode($filters));
        return Cache::remember($cacheKey, 600, function () use ($organizationId, $days) {
            $today = now()->toDateString();
            $limitDate = now()->addDays($days)->toDateString();
            return DB::table('product_batches as pb')
                ->join('product_variants as pv', 'pv.id', '=', 'pb.product_variant_id')
                ->join('products as p', 'p.id', '=', 'pv.product_id')
                ->leftJoin('suppliers as s', 's.id', '=', 'pb.supplier_id')
                ->where('p.organization_id', $organizationId)
                ->where('pb.deleted', 0)
                ->where('pb.status', 1)
                ->where('pb.available_qty', '>', 0)
                ->whereNotNull('pb.expiry_date')
                ->whereRaw("pb.expiry_date <= ?", [$limitDate])
                ->select([
                    'p.name as product_name',
                    'pv.variant_name',
                    'pb.batch_no',
                    'pb.mfg_date',
                    'pb.expiry_date',
                    'pb.available_qty',
                    's.name as supplier_name',
                    DB::raw("DATEDIFF(pb.expiry_date, '{$today}') as days_left"),
                    DB::raw("CASE WHEN pb.expiry_date < '{$today}' THEN 'Expired' WHEN pb.expiry_date <= '{$limitDate}' THEN 'Near Expiry' ELSE 'Valid' END as expiry_status"),
                ])
                ->orderBy('pb.expiry_date')
                ->get()->toArray();
        });
    }
}
