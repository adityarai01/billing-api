<?php
namespace App\Services;

use App\Models\NotificationSetting;
use App\Enums\NotificationTypeEnum;
use Illuminate\Support\Facades\Cache;

class NotificationSettingService
{
    private function cacheKey(int $orgId): string
    {
        return "org:{$orgId}:notification-settings";
    }

    public function getSettings(int $organizationId): array
    {
        return Cache::remember($this->cacheKey($organizationId), 1800, function () use ($organizationId) {
            $existing = NotificationSetting::where('organization_id', $organizationId)
                ->get()->keyBy('notification_type')->toArray();

            $all = [];
            foreach (NotificationTypeEnum::cases() as $type) {
                if (isset($existing[$type->value])) {
                    $all[] = $existing[$type->value];
                } else {
                    $all[] = $this->defaultSetting($organizationId, $type->value);
                }
            }
            return $all;
        });
    }

    private function defaultSetting(int $orgId, int $type): array
    {
        return [
            'organization_id'        => $orgId,
            'notification_type'      => $type,
            'is_enabled'             => 1,
            'notify_admin'           => 1,
            'notify_cashier'         => 0,
            'notify_inventory_staff' => 1,
            'notify_manager'         => 1,
            'threshold_value'        => null,
            'before_days'            => null,
            'show_popup'             => 1,
            'send_email'             => 0,
            'send_whatsapp'          => 0,
            'status'                 => 1,
        ];
    }

    public function updateSettings(int $organizationId, array $settings): void
    {
        foreach ($settings as $setting) {
            NotificationSetting::updateOrCreate(
                ['organization_id' => $organizationId, 'notification_type' => $setting['notification_type']],
                array_merge($setting, ['organization_id' => $organizationId])
            );
        }
        Cache::forget($this->cacheKey($organizationId));
    }

    public function isEnabled(int $organizationId, int $notificationType): bool
    {
        $s = NotificationSetting::where('organization_id', $organizationId)
            ->where('notification_type', $notificationType)->first();
        return $s ? (bool) $s->is_enabled : true;
    }

    public function shouldShowPopup(int $organizationId, int $notificationType): bool
    {
        $s = NotificationSetting::where('organization_id', $organizationId)
            ->where('notification_type', $notificationType)->first();
        return $s ? (bool) $s->show_popup : true;
    }

    public function getThreshold(int $organizationId, int $notificationType): ?float
    {
        $s = NotificationSetting::where('organization_id', $organizationId)
            ->where('notification_type', $notificationType)->first();
        return $s?->threshold_value;
    }
}
