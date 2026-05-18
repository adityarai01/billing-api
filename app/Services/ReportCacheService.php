<?php
namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ReportCacheService
{
    public function clearPurchaseCache(int $organizationId): void
    {
        // Clear all purchase-related keys using tags or manual pattern
        Cache::forget("org:{$organizationId}:purchases:list");
    }

    public function clearStockCache(int $organizationId): void
    {
        // We flush by re-writing with short TTL or use tag-based flush
        // With Redis tags support:
        // Cache::tags(["org:{$organizationId}", "stock"])->flush();
    }

    public function clearSupplierCache(int $organizationId): void
    {
        // Clears supplier list cache prefix
        Cache::forget("org:{$organizationId}:suppliers:list");
    }

    public function rebuildStockCache(int $organizationId): void
    {
        // Intentionally clear so next request rebuilds
        $this->clearStockCache($organizationId);
    }

    public function rememberCurrentStockReport(int $organizationId, array $filters, callable $callback): mixed
    {
        $key = "org:{$organizationId}:stock:current:" . md5(json_encode($filters));
        return Cache::remember($key, 300, $callback);
    }

    public function rememberLowStockReport(int $organizationId, array $filters, callable $callback): mixed
    {
        $key = "org:{$organizationId}:stock:low:" . md5(json_encode($filters));
        return Cache::remember($key, 300, $callback);
    }

    public function rememberNearExpiryReport(int $organizationId, array $filters, callable $callback): mixed
    {
        $key = "org:{$organizationId}:stock:near-expiry:" . md5(json_encode($filters));
        return Cache::remember($key, 600, $callback);
    }
}
