<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_variant_id');
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->string('product_name')->nullable();
            $table->string('variant_name')->nullable();
            $table->string('sku', 100)->nullable();
            $table->string('barcode', 100)->nullable();
            $table->string('batch_no', 100)->nullable();
            $table->decimal('qty', 12, 3)->default(0);
            $table->decimal('mrp', 12, 2)->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('gross_amount', 12, 2)->default(0);
            $table->tinyInteger('discount_type')->nullable()->comment('1=Percentage,2=Fixed');
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->unsignedBigInteger('promotion_id')->nullable();
            $table->decimal('promotion_discount_amount', 12, 2)->default(0);
            $table->decimal('total_discount_amount', 12, 2)->default(0);
            $table->decimal('taxable_amount', 12, 2)->default(0);
            $table->decimal('gst_percent', 5, 2)->default(0);
            $table->decimal('cgst_percent', 5, 2)->default(0);
            $table->decimal('sgst_percent', 5, 2)->default(0);
            $table->decimal('igst_percent', 5, 2)->default(0);
            $table->decimal('cgst_amount', 12, 2)->default(0);
            $table->decimal('sgst_amount', 12, 2)->default(0);
            $table->decimal('igst_amount', 12, 2)->default(0);
            $table->decimal('gst_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('profit_amount', 12, 2)->default(0);
            $table->tinyInteger('is_free_item')->default(0);
            $table->decimal('returned_qty', 12, 3)->default(0);
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('deleted')->default(0);
            $table->timestamps();

            $table->index(['organization_id', 'sale_id']);
            $table->index(['organization_id', 'product_id']);
            $table->index(['organization_id', 'product_variant_id']);
            $table->index(['organization_id', 'batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
