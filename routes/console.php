<?php

use App\Jobs\NearExpiryAlertJob;
use App\Jobs\LowStockAlertJob;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run near-expiry check daily at 09:00 for all organizations
Schedule::call(function () {
    $orgIds = User::distinct()->pluck('organization_id')->filter()->unique();
    foreach ($orgIds as $orgId) {
        NearExpiryAlertJob::dispatch($orgId);
    }
})->dailyAt('09:00')->name('near-expiry-check');

// Run low-stock sweep daily at 09:30 for all organizations
Schedule::call(function () {
    $orgIds = User::distinct()->pluck('organization_id')->filter()->unique();
    foreach ($orgIds as $orgId) {
        LowStockAlertJob::dispatch($orgId);
    }
})->dailyAt('09:30')->name('low-stock-check');
