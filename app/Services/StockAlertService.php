<?php
namespace App\Services;

use App\Models\ProductVariant;
use App\Enums\NotificationTypeEnum;
use App\Enums\NotificationPriorityEnum;
use App\Enums\NotificationSourceTypeEnum;

class StockAlertService
{
    public function __construct(
        private NotificationService $notificationService,
        private NotificationSettingService $settingService
    ) {}

    public function checkAndAlert(int $organizationId, int $productVariantId): void
    {
        $variant = ProductVariant::find($productVariantId);
        if (!$variant) return;

        $stock = (float) ($variant->stock_qty ?? $variant->available_stock ?? 0);

        if ($stock <= 0) {
            $this->createOutOfStockNotification($organizationId, $variant);
        } else {
            $threshold = $this->getThreshold($organizationId, $variant);
            if ($threshold !== null && $stock <= $threshold) {
                $this->createLowStockNotification($organizationId, $variant, $stock);
            }
        }
    }

    private function getThreshold(int $organizationId, $variant): ?float
    {
        $variantThreshold = $variant->low_stock_alert ?? null;
        if ($variantThreshold !== null && $variantThreshold > 0) {
            return (float) $variantThreshold;
        }
        return $this->settingService->getThreshold($organizationId, NotificationTypeEnum::LowStock->value);
    }

    public function createOutOfStockNotification(int $organizationId, $variant): void
    {
        if (!$this->settingService->isEnabled($organizationId, NotificationTypeEnum::OutOfStock->value)) return;

        $sourceType = NotificationSourceTypeEnum::ProductVariant->value;
        $sourceId   = $variant->id;

        if ($this->notificationService->hasDuplicateUnread($organizationId, $sourceType, $sourceId, NotificationTypeEnum::OutOfStock->value)) return;

        $name = $variant->product->name ?? 'Unknown Product';
        $this->notificationService->createForAdmins($organizationId, [
            'title'             => "{$name} is out of stock",
            'message'           => "{$name} stock is 0. Please reorder immediately.",
            'notification_type' => NotificationTypeEnum::OutOfStock->value,
            'source_type'       => $sourceType,
            'source_id'         => $sourceId,
            'priority'          => NotificationPriorityEnum::Critical->value,
            'action_url'        => '/stock',
        ]);
    }

    public function createLowStockNotification(int $organizationId, $variant, float $stock): void
    {
        if (!$this->settingService->isEnabled($organizationId, NotificationTypeEnum::LowStock->value)) return;

        $sourceType = NotificationSourceTypeEnum::ProductVariant->value;
        $sourceId   = $variant->id;

        if ($this->notificationService->hasDuplicateUnread($organizationId, $sourceType, $sourceId, NotificationTypeEnum::LowStock->value)) return;

        $name = $variant->product->name ?? 'Unknown Product';
        $this->notificationService->createForAdmins($organizationId, [
            'title'             => "{$name} stock is low",
            'message'           => "{$name} available stock is {$stock}. Please reorder soon.",
            'notification_type' => NotificationTypeEnum::LowStock->value,
            'source_type'       => $sourceType,
            'source_id'         => $sourceId,
            'priority'          => NotificationPriorityEnum::High->value,
            'action_url'        => '/low-stock',
        ]);
    }
}
