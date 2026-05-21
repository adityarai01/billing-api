<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_buy_get_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->unsignedBigInteger('promotion_id')->nullable()->index();
            $table->decimal('buy_qty', 12, 3)->default(0);
            $table->decimal('get_qty', 12, 3)->default(0);
            $table->tinyInteger('buy_target_type')->comment('1=Product,2=ProductVariant,3=Category,4=Brand');
            $table->unsignedBigInteger('buy_target_id')->nullable();
            $table->tinyInteger('get_target_type')->comment('1=SameProduct,2=Product,3=ProductVariant,4=Category,5=Brand');
            $table->unsignedBigInteger('get_target_id')->nullable();
            $table->tinyInteger('is_same_product')->default(1)->comment('0=No,1=Yes');
            $table->decimal('max_free_qty', 12, 3)->nullable();
            $table->tinyInteger('auto_add_free_item')->default(1)->comment('0=No,1=Yes');
            $table->tinyInteger('allow_cashier_select')->default(0)->comment('0=No,1=Yes');
            $table->timestamps();

            $table->index(['organization_id', 'promotion_id']);
            $table->index(['organization_id', 'buy_target_type']);
            $table->index(['organization_id', 'buy_target_id']);
            $table->index(['organization_id', 'get_target_type']);
            $table->index(['organization_id', 'get_target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_buy_get_rules');
    }
};
