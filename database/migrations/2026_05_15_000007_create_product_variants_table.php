<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('unit_id')->nullable()->index();
            $table->string('sku', 100)->nullable()->index();
            $table->string('barcode', 100)->nullable()->index();
            $table->string('variant_name')->nullable();
            $table->decimal('purchase_price', 15, 4)->default(0);
            $table->decimal('selling_price', 15, 4)->default(0);
            $table->decimal('wholesale_price', 15, 4)->default(0);
            $table->decimal('mrp', 15, 4)->default(0);
            $table->decimal('stock_qty', 15, 4)->default(0);
            $table->decimal('low_stock_alert', 15, 4)->default(0);
            $table->string('image')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('deleted')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'sku'], 'uq_org_sku');
            $table->unique(['organization_id', 'barcode'], 'uq_org_barcode');

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('unit_id')->references('id')->on('units')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
