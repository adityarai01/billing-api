<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_combo_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->unsignedBigInteger('promotion_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->decimal('required_qty', 12, 3)->default(1);
            $table->timestamps();

            $table->index(['organization_id', 'promotion_id']);
            $table->index(['organization_id', 'product_variant_id']);
            $table->index(['organization_id', 'category_id']);
            $table->index(['organization_id', 'brand_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_combo_items');
    }
};
