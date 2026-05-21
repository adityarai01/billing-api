<?php
namespace App\Services;

use App\Events\PromotionCreated;
use App\Events\PromotionDeleted;
use App\Events\PromotionUpdated;
use App\Models\Promotion;
use App\Models\PromotionBuyGetRule;
use App\Models\PromotionComboItem;
use App\Models\PromotionCondition;
use App\Models\PromotionFreeItem;
use App\Models\PromotionTarget;
use Illuminate\Support\Facades\DB;

class PromotionService
{
    public function __construct(private PromotionCacheService $cacheService) {}

    public function create(array $data): Promotion
    {
        $promotion = Promotion::create($data);
        $this->cacheService->clearPromotionCache($data['organization_id']);
        event(new PromotionCreated($promotion));
        return $promotion;
    }

    public function createPromotionWithRules(array $data): Promotion
    {
        return DB::transaction(function () use ($data) {
            $promotion = Promotion::create(array_except($data, ['targets', 'conditions', 'buy_get_rules', 'combo_items', 'free_items']));

            if (!empty($data['targets'])) {
                foreach ($data['targets'] as $target) {
                    PromotionTarget::create(array_merge($target, [
                        'organization_id' => $data['organization_id'],
                        'promotion_id'    => $promotion->id,
                    ]));
                }
            }

            if (!empty($data['conditions'])) {
                foreach ($data['conditions'] as $condition) {
                    PromotionCondition::create(array_merge($condition, [
                        'organization_id' => $data['organization_id'],
                        'promotion_id'    => $promotion->id,
                    ]));
                }
            }

            if (!empty($data['buy_get_rules'])) {
                foreach ($data['buy_get_rules'] as $rule) {
                    PromotionBuyGetRule::create(array_merge($rule, [
                        'organization_id' => $data['organization_id'],
                        'promotion_id'    => $promotion->id,
                    ]));
                }
            }

            if (!empty($data['combo_items'])) {
                foreach ($data['combo_items'] as $item) {
                    PromotionComboItem::create(array_merge($item, [
                        'organization_id' => $data['organization_id'],
                        'promotion_id'    => $promotion->id,
                    ]));
                }
            }

            if (!empty($data['free_items'])) {
                foreach ($data['free_items'] as $item) {
                    PromotionFreeItem::create(array_merge($item, [
                        'organization_id' => $data['organization_id'],
                        'promotion_id'    => $promotion->id,
                    ]));
                }
            }

            $this->cacheService->clearPromotionCache($data['organization_id']);
            event(new PromotionCreated($promotion));

            return $promotion->load(['targets', 'conditions', 'buyGetRules', 'comboItems', 'freeItems']);
        });
    }

    public function update(int $id, array $data): Promotion
    {
        return DB::transaction(function () use ($id, $data) {
            $promotion = Promotion::findOrFail($id);

            // Strip relational keys — they are not columns in the promotions table
            $promotion->update(array_except($data, ['targets', 'conditions', 'buy_get_rules', 'combo_items', 'free_items']));

            // Sync buy_get_rules if provided
            if (array_key_exists('buy_get_rules', $data)) {
                $promotion->buyGetRules()->delete();
                foreach ($data['buy_get_rules'] as $rule) {
                    PromotionBuyGetRule::create(array_merge($rule, [
                        'organization_id' => $promotion->organization_id,
                        'promotion_id'    => $promotion->id,
                    ]));
                }
            }

            // Sync free_items if provided
            if (array_key_exists('free_items', $data)) {
                $promotion->freeItems()->delete();
                foreach ($data['free_items'] as $item) {
                    PromotionFreeItem::create(array_merge($item, [
                        'organization_id' => $promotion->organization_id,
                        'promotion_id'    => $promotion->id,
                    ]));
                }
            }

            // Sync targets if provided
            if (array_key_exists('targets', $data)) {
                $promotion->targets()->delete();
                foreach ($data['targets'] as $target) {
                    PromotionTarget::create(array_merge($target, [
                        'organization_id' => $promotion->organization_id,
                        'promotion_id'    => $promotion->id,
                    ]));
                }
            }

            $this->cacheService->clearPromotionCache($promotion->organization_id);
            $this->cacheService->forgetPromotionDetailsCache($promotion->organization_id, $id);
            event(new PromotionUpdated($promotion->fresh()));
            return $promotion->fresh()->load(['targets', 'buyGetRules', 'freeItems']);
        });
    }

    public function delete(int $id): void
    {
        $promotion = Promotion::findOrFail($id);
        $promotion->update(['deleted' => 1]);
        $this->cacheService->clearPromotionCache($promotion->organization_id);
        event(new PromotionDeleted($promotion));
    }

    public function search(array $filters): array
    {
        $query = Promotion::where('organization_id', $filters['organization_id'])
            ->where('deleted', 0);

        if (!empty($filters['keyword'])) {
            $query->where('name', 'like', '%' . $filters['keyword'] . '%');
        }
        if (isset($filters['promotion_type'])) {
            $query->where('promotion_type', $filters['promotion_type']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['from_date'])) {
            $query->whereDate('start_date', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('end_date', '<=', $filters['to_date']);
        }

        $perPage = $filters['per_page'] ?? 20;
        $result  = $query->orderByDesc('priority')->orderByDesc('id')->paginate($perPage);

        return [
            'record' => $result->items(),
            'total'  => $result->total(),
            'pages'  => $result->lastPage(),
        ];
    }

    public function details(int $id): Promotion
    {
        return Promotion::with(['targets', 'coupons', 'buyGetRules', 'comboItems', 'freeItems', 'conditions'])
            ->findOrFail($id);
    }

    public function changeStatus(int $id, int $status): Promotion
    {
        $promotion = Promotion::findOrFail($id);
        $promotion->update(['status' => $status]);
        $this->cacheService->clearPromotionCache($promotion->organization_id);
        return $promotion->fresh();
    }

    public function getActivePromotions(int $organizationId): array
    {
        return $this->cacheService->rememberActivePromotions($organizationId);
    }

    public function addTarget(int $promotionId, array $data): PromotionTarget
    {
        $promotion = Promotion::findOrFail($promotionId);
        $target = PromotionTarget::create([
            'organization_id' => $promotion->organization_id,
            'promotion_id'    => $promotionId,
            'target_type'     => $data['target_type'],
            'target_id'       => $data['target_id'] ?? null,
        ]);
        $this->cacheService->clearPromotionCache($promotion->organization_id);
        return $target;
    }

    public function removeTarget(int $targetId): void
    {
        $target = PromotionTarget::findOrFail($targetId);
        $orgId  = $target->organization_id;
        $target->delete();
        $this->cacheService->clearPromotionCache($orgId);
    }

    public function addBuyGetRule(int $promotionId, array $data): PromotionBuyGetRule
    {
        $promotion = Promotion::findOrFail($promotionId);
        $rule = PromotionBuyGetRule::create(array_merge($data, [
            'organization_id' => $promotion->organization_id,
            'promotion_id'    => $promotionId,
        ]));
        $this->cacheService->clearPromotionCache($promotion->organization_id);
        return $rule;
    }

    public function updateBuyGetRule(int $ruleId, array $data): PromotionBuyGetRule
    {
        $rule = PromotionBuyGetRule::findOrFail($ruleId);
        $rule->update($data);
        $this->cacheService->clearPromotionCache($rule->organization_id);
        return $rule->fresh();
    }

    public function removeBuyGetRule(int $ruleId): void
    {
        $rule = PromotionBuyGetRule::findOrFail($ruleId);
        $orgId = $rule->organization_id;
        $rule->delete();
        $this->cacheService->clearPromotionCache($orgId);
    }

    public function addFreeItem(int $promotionId, array $data): PromotionFreeItem
    {
        $promotion = Promotion::findOrFail($promotionId);
        $item = PromotionFreeItem::create(array_merge($data, [
            'organization_id' => $promotion->organization_id,
            'promotion_id'    => $promotionId,
        ]));
        $this->cacheService->clearPromotionCache($promotion->organization_id);
        return $item;
    }

    public function removeFreeItem(int $itemId): void
    {
        $item  = PromotionFreeItem::findOrFail($itemId);
        $orgId = $item->organization_id;
        $item->delete();
        $this->cacheService->clearPromotionCache($orgId);
    }

    public function addComboItem(int $promotionId, array $data): PromotionComboItem
    {
        $promotion = Promotion::findOrFail($promotionId);
        $item = PromotionComboItem::create(array_merge($data, [
            'organization_id' => $promotion->organization_id,
            'promotion_id'    => $promotionId,
        ]));
        $this->cacheService->clearPromotionCache($promotion->organization_id);
        return $item;
    }

    public function removeComboItem(int $itemId): void
    {
        $item  = PromotionComboItem::findOrFail($itemId);
        $orgId = $item->organization_id;
        $item->delete();
        $this->cacheService->clearPromotionCache($orgId);
    }
}

function array_except(array $array, array $keys): array
{
    return array_diff_key($array, array_flip($keys));
}
