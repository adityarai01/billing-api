<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_variant_id');
            $table->unsignedBigInteger('unit_id');
            $table->string('unit_name_snapshot', 100)->nullable();
            $table->decimal('conversion_qty', 12, 3)->default(1);
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('mrp', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->decimal('wholesale_price', 12, 2)->default(0);
            $table->string('barcode', 100)->nullable();
            $table->tinyInteger('is_base_unit')->default(0);
            $table->tinyInteger('is_default_purchase_unit')->default(0);
            $table->tinyInteger('is_default_sale_unit')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('deleted')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'product_id']);
            $table->index(['organization_id', 'product_variant_id']);
            $table->index(['organization_id', 'unit_id']);
            $table->index(['organization_id', 'barcode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_units');
    }
};
