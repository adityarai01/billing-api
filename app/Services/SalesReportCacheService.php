<?php
namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Customer;
use Illuminate\Support\Facades\Cache;

class SalesReportCacheService
{
    public function clearSalesCache(int $organizationId): void
    {
        Cache::forget("org:{$organizationId}:sales:list");
        Cache::forget("org:{$organizationId}:sales:daily-summary");
        Cache::forget("org:{$organizationId}:sales:payment-mode-summary");
    }

    public function clearCustomerCache(int $organizationId): void
    {
        Cache::forget("org:{$organizationId}:customers:list");
    }

    public function rememberSalesList(int $organizationId, array $filters, callable $callback): mixed
    {
        $key = "org:{$organizationId}:sales:list:" . md5(json_encode($filters));
        return Cache::remember($key, 300, $callback);
    }

    public function rememberSaleDetails(int $organizationId, int $saleId, callable $callback): mixed
    {
        $key = "org:{$organizationId}:sale:{$saleId}:details";
        return Cache::remember($key, 600, $callback);
    }

    public function rememberDailySalesSummary(int $organizationId, array $filters, callable $callback): mixed
    {
        $key = "org:{$organizationId}:sales:daily-summary:" . md5(json_encode($filters));
        return Cache::remember($key, 300, $callback);
    }

    public function rememberPaymentModeSummary(int $organizationId, array $filters, callable $callback): mixed
    {
        $key = "org:{$organizationId}:sales:payment-mode-summary:" . md5(json_encode($filters));
        return Cache::remember($key, 300, $callback);
    }

    public function getSalesReport(int $organizationId, array $filters): array
    {
        $query = Sale::where('organization_id', $organizationId)->where('deleted', 0)->with('customer');

        if (!empty($filters['date_from']))      $query->whereDate('invoice_date', '>=', $filters['date_from']);
        if (!empty($filters['date_to']))        $query->whereDate('invoice_date', '<=', $filters['date_to']);
        if (!empty($filters['customer_id']))    $query->where('customer_id', $filters['customer_id']);
        if (!empty($filters['payment_status'])) $query->where('payment_status', $filters['payment_status']);
        if (!empty($filters['sale_status']))    $query->where('sale_status', $filters['sale_status']);
        if (!empty($filters['created_by']))     $query->where('created_by', $filters['created_by']);

        $perPage = $filters['per_page'] ?? 20;
        return $query->orderByDesc('invoice_date')->paginate($perPage)->toArray();
    }

    public function getSalesItemReport(int $organizationId, array $filters): array
    {
        $query = SaleItem::where('organization_id', $organizationId)->where('deleted', 0)
            ->with(['sale', 'product', 'productVariant']);

        if (!empty($filters['product_id']))         $query->where('product_id', $filters['product_id']);
        if (!empty($filters['product_variant_id']))  $query->where('product_variant_id', $filters['product_variant_id']);
        if (!empty($filters['date_from']))           $query->whereHas('sale', fn($q) => $q->whereDate('invoice_date', '>=', $filters['date_from']));
        if (!empty($filters['date_to']))             $query->whereHas('sale', fn($q) => $q->whereDate('invoice_date', '<=', $filters['date_to']));

        $perPage = $filters['per_page'] ?? 20;
        return $query->orderByDesc('id')->paginate($perPage)->toArray();
    }

    public function getPaymentModeReport(int $organizationId, array $filters): array
    {
        $query = SalePayment::where('organization_id', $organizationId)->where('deleted', 0);
        if (!empty($filters['date_from'])) $query->whereDate('payment_date', '>=', $filters['date_from']);
        if (!empty($filters['date_to']))   $query->whereDate('payment_date', '<=', $filters['date_to']);

        return $query->selectRaw('payment_mode, SUM(amount) as total_amount, COUNT(*) as count')
            ->groupBy('payment_mode')->get()->toArray();
    }

    public function getCustomerDueReport(int $organizationId, array $filters): array
    {
        $query = Customer::where('organization_id', $organizationId)
            ->where('deleted', 0)
            ->where('current_balance', '>', 0)
            ->where('balance_type', 1);

        $perPage = $filters['per_page'] ?? 20;
        return $query->orderByDesc('current_balance')->paginate($perPage)->toArray();
    }

    public function getProfitReport(int $organizationId, array $filters): array
    {
        $query = SaleItem::where('organization_id', $organizationId)->where('deleted', 0)->where('is_free_item', 0)->with(['sale', 'product', 'productVariant']);
        if (!empty($filters['date_from'])) $query->whereHas('sale', fn($q) => $q->whereDate('invoice_date', '>=', $filters['date_from']));
        if (!empty($filters['date_to']))   $query->whereHas('sale', fn($q) => $q->whereDate('invoice_date', '<=', $filters['date_to']));

        $perPage = $filters['per_page'] ?? 20;
        return $query->orderByDesc('id')->paginate($perPage)->toArray();
    }

    public function getDiscountReport(int $organizationId, array $filters): array
    {
        $query = \App\Models\SaleItemDiscount::where('organization_id', $organizationId)->with(['sale', 'saleItem.product']);
        if (!empty($filters['date_from'])) $query->whereHas('sale', fn($q) => $q->whereDate('invoice_date', '>=', $filters['date_from']));
        if (!empty($filters['date_to']))   $query->whereHas('sale', fn($q) => $q->whereDate('invoice_date', '<=', $filters['date_to']));

        $perPage = $filters['per_page'] ?? 20;
        return $query->orderByDesc('id')->paginate($perPage)->toArray();
    }
}
