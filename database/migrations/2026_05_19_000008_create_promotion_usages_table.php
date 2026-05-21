<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->unsignedBigInteger('promotion_id')->nullable()->index();
            $table->unsignedBigInteger('promotion_coupon_id')->nullable()->index();
            $table->unsignedBigInteger('sale_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('coupon_code', 100)->nullable();
            $table->tinyInteger('discount_level')->comment('1=ItemLevel,2=InvoiceLevel');
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('free_item_qty', 12, 3)->default(0);
            $table->datetime('used_at');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'promotion_id']);
            $table->index(['organization_id', 'promotion_coupon_id']);
            $table->index(['organization_id', 'sale_id']);
            $table->index(['organization_id', 'customer_id']);
            $table->index(['organization_id', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_usages');
    }
};
