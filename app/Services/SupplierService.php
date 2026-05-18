<?php
namespace App\Services;

use App\Models\Supplier;
use Illuminate\Support\Facades\Cache;

class SupplierService
{
    public function create(int $organizationId, array $data): Supplier
    {
        return Supplier::create(array_merge($data, ['organization_id' => $organizationId]));
    }

    public function update(int $organizationId, int $id, array $data): Supplier
    {
        $supplier = $this->findOrFail($organizationId, $id);
        $supplier->update($data);
        $this->clearCache($organizationId, $id);
        return $supplier->fresh();
    }

    public function delete(int $organizationId, int $id): void
    {
        $supplier = $this->findOrFail($organizationId, $id);
        $supplier->update(['deleted' => 1, 'status' => 0]);
        $this->clearCache($organizationId, $id);
    }

    public function search(int $organizationId, array $filters = []): array
    {
        $query = Supplier::where('organization_id', $organizationId)->where('deleted', 0);
        if (!empty($filters['search'])) {
            $q = $filters['search'];
            $query->where(function ($b) use ($q) {
                $b->where('name', 'like', "%{$q}%")
                  ->orWhere('mobile_no', 'like', "%{$q}%")
                  ->orWhere('gstin', 'like', "%{$q}%");
            });
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        return $query->orderByDesc('id')->get()->toArray();
    }

    public function details(int $organizationId, int $id): Supplier
    {
        $cacheKey = "org:{$organizationId}:supplier:{$id}:details";
        return Cache::remember($cacheKey, 600, fn () => $this->findOrFail($organizationId, $id));
    }

    public function findOrFail(int $organizationId, int $id): Supplier
    {
        $supplier = Supplier::where('id', $id)->where('organization_id', $organizationId)->where('deleted', 0)->first();
        if (!$supplier) abort(404, 'Supplier not found');
        return $supplier;
    }

    private function clearCache(int $organizationId, int $id): void
    {
        Cache::forget("org:{$organizationId}:supplier:{$id}:details");
    }
}
