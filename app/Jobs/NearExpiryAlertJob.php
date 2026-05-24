<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\AppNotification;
use App\Enums\NotificationTypeEnum;
use App\Enums\NotificationPriorityEnum;
use App\Enums\NotificationSourceTypeEnum;
use App\Services\NotificationService;
use App\Services\NotificationSettingService;
use Illuminate\Support\Facades\DB;

class NearExpiryAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $organizationId) {}

    public function handle(NotificationService $notificationService, NotificationSettingService $settingService): void
    {
        if (!$settingService->isEnabled($this->organizationId, NotificationTypeEnum::NearExpiry->value)) return;

        $beforeDays = DB::table('notification_settings')
            ->where('organization_id', $this->organizationId)
            ->where('notification_type', NotificationTypeEnum::NearExpiry->value)
            ->value('before_days') ?? 30;

        $expiresBefore = now()->addDays($beforeDays);

        $batches = DB::table('product_batches as pb')
            ->join('product_variants as pv', 'pb.product_variant_id', '=', 'pv.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            ->where('p.organization_id', $this->organizationId)
            ->where('p.deleted', 0)->where('pv.deleted', 0)
            ->where('pb.available_qty', '>', 0)
            ->whereNotNull('pb.expiry_date')
            ->where('pb.expiry_date', '<=', $expiresBefore->toDateString())
            ->where('pb.expiry_date', '>=', now()->toDateString())
            ->select('pb.id', 'pb.batch_no', 'pb.expiry_date', 'p.name as product_name')
            ->get();

        foreach ($batches as $batch) {
            $sourceType = NotificationSourceTypeEnum::ProductBatch->value;
            if ($notificationService->hasDuplicateUnread($this->organizationId, $sourceType, $batch->id, NotificationTypeEnum::NearExpiry->value)) continue;

            $daysLeft = now()->diffInDays($batch->expiry_date);
            $notificationService->createForAdmins($this->organizationId, [
                'title'             => "{$batch->product_name} expiring soon",
                'message'           => "Batch {$batch->batch_no} of {$batch->product_name} expires on {$batch->expiry_date} ({$daysLeft} days left).",
                'notification_type' => NotificationTypeEnum::NearExpiry->value,
                'source_type'       => $sourceType,
                'source_id'         => $batch->id,
                'priority'          => NotificationPriorityEnum::High->value,
                'action_url'        => '/expiry-report',
            ]);
        }
    }
}
