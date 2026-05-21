<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_coupons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->unsignedBigInteger('promotion_id')->nullable()->index();
            $table->string('coupon_code', 100);
            $table->integer('usage_limit')->nullable();
            $table->integer('used_count')->default(0);
            $table->integer('per_customer_limit')->nullable();
            $table->datetime('start_date')->nullable();
            $table->datetime('end_date')->nullable();
            $table->decimal('min_bill_amount', 12, 2)->default(0);
            $table->decimal('max_discount_amount', 12, 2)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('deleted')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'coupon_code']);
            $table->index(['organization_id', 'promotion_id']);
            $table->index(['organization_id', 'coupon_code']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_coupons');
    }
};
