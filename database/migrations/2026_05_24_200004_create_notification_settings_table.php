<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->tinyInteger('notification_type');
            $table->tinyInteger('is_enabled')->default(1);
            $table->tinyInteger('notify_admin')->default(1);
            $table->tinyInteger('notify_cashier')->default(0);
            $table->tinyInteger('notify_inventory_staff')->default(1);
            $table->tinyInteger('notify_manager')->default(1);
            $table->decimal('threshold_value', 12, 3)->nullable();
            $table->integer('before_days')->nullable();
            $table->tinyInteger('show_popup')->default(1);
            $table->tinyInteger('send_email')->default(0);
            $table->tinyInteger('send_whatsapp')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->unique(['organization_id', 'notification_type']);
            $table->index(['organization_id', 'notification_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
