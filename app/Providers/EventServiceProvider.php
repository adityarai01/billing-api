<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \App\Events\PurchaseCreated::class => [
            \App\Listeners\ClearPurchaseCache::class,
            \App\Listeners\ClearStockCache::class,
            \App\Listeners\DispatchLowStockAlert::class,
            \App\Listeners\DispatchNearExpiryAlert::class,
            \App\Listeners\RebuildStockReportCache::class,
        ],
        \App\Events\PurchaseReturned::class => [
            \App\Listeners\ClearPurchaseCache::class,
            \App\Listeners\ClearStockCache::class,
            \App\Listeners\RebuildStockReportCache::class,
        ],
        \App\Events\StockAdjusted::class => [
            \App\Listeners\ClearStockCache::class,
            \App\Listeners\DispatchLowStockAlert::class,
            \App\Listeners\RebuildStockReportCache::class,
        ],
        \App\Events\DebitNoteAdjusted::class => [
            \App\Listeners\ClearPurchaseCache::class,
        ],
        \App\Events\SupplierCreditNoteAdjusted::class => [
            \App\Listeners\ClearPurchaseCache::class,
        ],

        // Sales Module Events
        \App\Events\SaleCreated::class => [
            \App\Listeners\ClearSalesCache::class,
            \App\Listeners\ClearStockCache::class,
            \App\Listeners\RebuildSalesReportCache::class,
            \App\Listeners\GenerateSaleInvoicePdf::class,
            \App\Listeners\SendInvoiceNotification::class,
        ],
        \App\Events\SaleCancelled::class => [
            \App\Listeners\ClearSalesCache::class,
            \App\Listeners\ClearStockCache::class,
            \App\Listeners\RebuildSalesReportCache::class,
        ],
        \App\Events\SalePaymentCreated::class => [
            \App\Listeners\ClearSalesCache::class,
        ],
        \App\Events\HeldBillCreated::class => [
            \App\Listeners\ClearSalesCache::class,
        ],
        \App\Events\HeldBillConverted::class => [
            \App\Listeners\ClearSalesCache::class,
            \App\Listeners\ClearStockCache::class,
        ],

        // Promotions Module Events
        \App\Events\PromotionCreated::class => [
            \App\Listeners\ClearPromotionCache::class,
            \App\Listeners\RebuildPromotionCache::class,
        ],
        \App\Events\PromotionUpdated::class => [
            \App\Listeners\ClearPromotionCache::class,
            \App\Listeners\RebuildPromotionCache::class,
        ],
        \App\Events\PromotionDeleted::class => [
            \App\Listeners\ClearPromotionCache::class,
        ],
        \App\Events\CouponUsed::class => [
            \App\Listeners\UpdatePromotionUsageCount::class,
            \App\Listeners\ClearPromotionCache::class,
        ],
        \App\Events\PromotionApplied::class => [
            \App\Listeners\UpdatePromotionUsageCount::class,
        ],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
