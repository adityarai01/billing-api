<?php

namespace App\Service\Webmaster;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ShopService
{
    private const CACHE_TTL            = 3600;
    private const VERSION_KEY          = 'webmaster:shops:version';
    private const CACHE_ITEM           = 'webmaster:shop:%d';
    private const SHOPKEEPER_VERSION_KEY = 'webmaster:shopkeepers:version';
    private const SHOPKEEPER_CACHE_ITEM  = 'webmaster:shopkeeper:%d';

    public function list(int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $version = (int) Cache::get(self::VERSION_KEY, 0);
        $key = "webmaster:shops:v{$version}:page:{$page}:per_page:{$perPage}";

        return Cache::remember($key, self::CACHE_TTL, fn () =>
            Organization::with('shopkeeper')
                ->where('deleted', 0)
                ->orderByDesc('id')
                ->paginate($perPage)
        );
    }

    public function findOrFail(int $id): Organization
    {
        $key = \sprintf(self::CACHE_ITEM, $id);

        return Cache::remember($key, self::CACHE_TTL, fn () =>
            Organization::with('shopkeeper')
                ->where('deleted', 0)
                ->findOrFail($id)
        );
    }

    public function create(array $data): Organization
    {
        $shopkeeperData = $data['shopkeeper'];
        unset($data['shopkeeper']);

        return DB::transaction(function () use ($data, $shopkeeperData) {
            $shop = Organization::create($data);

            $shop->users()->create(array_merge($shopkeeperData, [
                'user_type' => User::TYPE_SHOP_OWNER,
            ]));

            $this->invalidateListCache();
            $this->invalidateShopkeeperListCache();

            return $shop->load('shopkeeper');
        });
    }

    public function update(Organization $shop, array $data): Organization
    {
        $shopkeeperData = $data['shopkeeper'] ?? null;
        unset($data['shopkeeper']);

        return DB::transaction(function () use ($shop, $data, $shopkeeperData) {
            $shopkeeperId = $shop->shopkeeper?->id;

            if (!empty($data)) {
                $shop->update($data);
            }

            if ($shopkeeperData) {
                $shopkeeper = $shop->shopkeeper;

                if ($shopkeeper) {
                    // Remove password key if null so hashed cast is not triggered
                    if (array_key_exists('password', $shopkeeperData) && $shopkeeperData['password'] === null) {
                        unset($shopkeeperData['password']);
                    }
                    unset($shopkeeperData['password_confirmation']);
                    $shopkeeper->update($shopkeeperData);
                } else {
                    $shopkeeper = $shop->users()->create(array_merge($shopkeeperData, [
                        'user_type' => User::TYPE_SHOP_OWNER,
                    ]));
                    $shopkeeperId = $shopkeeper->id;
                }
            }

            $this->invalidateItemCache((int) $shop->id);
            $this->invalidateListCache();
            if ($shopkeeperId) {
                $this->invalidateShopkeeperItemCache((int) $shopkeeperId);
            }
            $this->invalidateShopkeeperListCache();

            return $shop->fresh('shopkeeper');
        });
    }

    public function delete(Organization $shop): void
    {
        DB::transaction(function () use ($shop) {
            $shopkeeperId = $shop->shopkeeper()->value('id');

            $shop->update(['deleted' => 1]);

            $shop->users()
                ->where('user_type', User::TYPE_SHOP_OWNER)
                ->update(['deleted' => 1]);

            $this->invalidateItemCache((int) $shop->id);
            $this->invalidateListCache();
            if ($shopkeeperId) {
                $this->invalidateShopkeeperItemCache((int) $shopkeeperId);
            }
            $this->invalidateShopkeeperListCache();
        });
    }

    private function invalidateItemCache(int $id): void
    {
        Cache::forget(\sprintf(self::CACHE_ITEM, $id));
    }

    private function invalidateListCache(): void
    {
        Cache::forever(self::VERSION_KEY, ((int) Cache::get(self::VERSION_KEY, 0)) + 1);
    }

    private function invalidateShopkeeperItemCache(int $id): void
    {
        Cache::forget(\sprintf(self::SHOPKEEPER_CACHE_ITEM, $id));
    }

    private function invalidateShopkeeperListCache(): void
    {
        Cache::forever(self::SHOPKEEPER_VERSION_KEY, ((int) Cache::get(self::SHOPKEEPER_VERSION_KEY, 0)) + 1);
    }
}
