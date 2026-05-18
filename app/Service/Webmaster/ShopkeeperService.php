<?php

namespace App\Service\Webmaster;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ShopkeeperService
{
    private const CACHE_TTL   = 3600;
    private const VERSION_KEY = 'webmaster:shopkeepers:version';
    private const CACHE_ITEM  = 'webmaster:shopkeeper:%d';

    public function list(int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $version = (int) Cache::get(self::VERSION_KEY, 0);
        $key = "webmaster:shopkeepers:v{$version}:page:{$page}:per_page:{$perPage}";

        return Cache::remember($key, self::CACHE_TTL, fn () =>
            User::with('organization')
                ->where('user_type', User::TYPE_SHOP_OWNER)
                ->where('deleted', 0)
                ->orderByDesc('id')
                ->paginate($perPage)
        );
    }

    public function findOrFail(int $id): User
    {
        $key = \sprintf(self::CACHE_ITEM, $id);

        return Cache::remember($key, self::CACHE_TTL, fn () =>
            User::with('organization')
                ->where('user_type', User::TYPE_SHOP_OWNER)
                ->where('deleted', 0)
                ->findOrFail($id)
        );
    }

    public function create(array $data): User
    {
        $data['user_type'] = User::TYPE_SHOP_OWNER;
        $shopkeeper = User::create($data);
        $this->invalidateListCache();
        return $shopkeeper->load('organization');
    }

    public function update(User $shopkeeper, array $data): User
    {
        $shopkeeper->update($data);
        $this->invalidateItemCache((int) $shopkeeper->id);
        $this->invalidateListCache();
        return $shopkeeper->fresh('organization');
    }

    public function delete(User $shopkeeper): void
    {
        $shopkeeper->update(['deleted' => 1]);
        $this->invalidateItemCache((int) $shopkeeper->id);
        $this->invalidateListCache();
    }

    private function invalidateItemCache(int $id): void
    {
        Cache::forget(\sprintf(self::CACHE_ITEM, $id));
    }

    private function invalidateListCache(): void
    {
        Cache::forever(self::VERSION_KEY, ((int) Cache::get(self::VERSION_KEY, 0)) + 1);
    }
}
