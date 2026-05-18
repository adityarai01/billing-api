<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->index();
            $table->unsignedBigInteger('supplier_id')->nullable()->index();
            $table->string('batch_no', 100)->index();
            $table->date('mfg_date')->nullable();
            $table->date('expiry_date')->nullable()->index();
            $table->decimal('purchase_price', 15, 4)->default(0);
            $table->decimal('mrp', 15, 4)->default(0);
            $table->decimal('selling_price', 15, 4)->default(0);
            $table->decimal('opening_qty', 15, 4)->default(0);
            $table->decimal('available_qty', 15, 4)->default(0);
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('deleted')->default(0);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_batches');
    }
};
