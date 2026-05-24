<?php
namespace App\Services;

use App\Models\AppNotification;
use App\Models\NotificationSetting;
use App\Enums\NotificationTypeEnum;
use App\Enums\NotificationPriorityEnum;
use Illuminate\Support\Facades\Cache;

class NotificationService
{
    private function cacheKey(int $orgId, ?int $userId, string $type): string
    {
        $u = $userId ?? 'admin';
        return "org:{$orgId}:notifications:{$type}:user:{$u}";
    }

    public function createNotification(int $organizationId, array $data): AppNotification
    {
        $n = AppNotification::create(array_merge($data, ['organization_id' => $organizationId]));
        $this->clearCache($organizationId, $data['user_id'] ?? null);
        return $n;
    }

    public function createForAdmins(int $organizationId, array $data): AppNotification
    {
        $data['user_id'] = null;
        return $this->createNotification($organizationId, $data);
    }

    public function createForUser(int $organizationId, int $userId, array $data): AppNotification
    {
        $data['user_id'] = $userId;
        return $this->createNotification($organizationId, $data);
    }

    public function unreadCount(int $organizationId, ?int $userId): int
    {
        $key = $this->cacheKey($organizationId, $userId, 'unread-count');
        return Cache::remember($key, 60, function () use ($organizationId, $userId) {
            $q = AppNotification::where('organization_id', $organizationId)
                ->where('is_read', 0)->where('deleted', 0)->where('status', 1);
            if ($userId) {
                $q->where(function ($b) use ($userId) {
                    $b->where('user_id', $userId)->orWhereNull('user_id');
                });
            }
            return $q->count();
        });
    }

    public function unreadList(int $organizationId, ?int $userId, int $limit = 20): array
    {
        $key = $this->cacheKey($organizationId, $userId, 'unread');
        return Cache::remember($key, 60, function () use ($organizationId, $userId, $limit) {
            $q = AppNotification::where('organization_id', $organizationId)
                ->where('is_read', 0)->where('deleted', 0)->where('status', 1);
            if ($userId) {
                $q->where(function ($b) use ($userId) {
                    $b->where('user_id', $userId)->orWhereNull('user_id');
                });
            }
            return $q->orderByDesc('priority')->orderByDesc('created_at')
                ->limit($limit)->get()->toArray();
        });
    }

    public function markAsRead(int $organizationId, int $notificationId, int $userId): bool
    {
        $n = AppNotification::where('id', $notificationId)
            ->where('organization_id', $organizationId)->first();
        if (!$n) return false;
        $n->update(['is_read' => 1, 'read_at' => now()]);
        $this->clearCache($organizationId, $userId);
        return true;
    }

    public function markAllAsRead(int $organizationId, ?int $userId): void
    {
        $q = AppNotification::where('organization_id', $organizationId)
            ->where('is_read', 0)->where('deleted', 0);
        if ($userId) {
            $q->where(function ($b) use ($userId) {
                $b->where('user_id', $userId)->orWhereNull('user_id');
            });
        }
        $q->update(['is_read' => 1, 'read_at' => now()]);
        $this->clearCache($organizationId, $userId);
    }

    public function searchNotifications(int $organizationId, array $filters, ?int $userId = null): array
    {
        $q = AppNotification::where('organization_id', $organizationId)->where('deleted', 0);
        if ($userId) {
            $q->where(function ($b) use ($userId) {
                $b->where('user_id', $userId)->orWhereNull('user_id');
            });
        }
        if (!empty($filters['notification_type'])) {
            $q->where('notification_type', $filters['notification_type']);
        }
        if (!empty($filters['priority'])) {
            $q->where('priority', $filters['priority']);
        }
        if (isset($filters['is_read']) && $filters['is_read'] !== '') {
            $q->where('is_read', $filters['is_read']);
        }
        if (!empty($filters['date_from'])) {
            $q->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $q->whereDate('created_at', '<=', $filters['date_to']);
        }
        $perPage = $filters['per_page'] ?? 20;
        $page    = $filters['page'] ?? 1;
        $result  = $q->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);
        return [
            'record'     => $result->items(),
            'page'       => $result->currentPage(),
            'total_page' => $result->lastPage(),
            'total_data' => $result->total(),
        ];
    }

    public function deleteNotification(int $organizationId, int $id): void
    {
        AppNotification::where('id', $id)->where('organization_id', $organizationId)
            ->update(['deleted' => 1]);
    }

    public function hasDuplicateUnread(int $organizationId, int $sourceType, int $sourceId, int $notificationType): bool
    {
        return AppNotification::where('organization_id', $organizationId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('notification_type', $notificationType)
            ->where('is_read', 0)
            ->where('deleted', 0)
            ->exists();
    }

    public function clearCache(int $organizationId, ?int $userId): void
    {
        Cache::forget($this->cacheKey($organizationId, $userId, 'unread-count'));
        Cache::forget($this->cacheKey($organizationId, $userId, 'unread'));
        Cache::forget($this->cacheKey($organizationId, null, 'unread-count'));
        Cache::forget($this->cacheKey($organizationId, null, 'unread'));
    }
}
