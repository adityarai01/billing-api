<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('purchase_return_id');
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->unsignedBigInteger('purchase_item_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_variant_id');
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->string('product_name', 255)->nullable();
            $table->string('variant_name', 255)->nullable();
            $table->string('sku', 100)->nullable();
            $table->string('barcode', 100)->nullable();
            $table->string('batch_no', 100)->nullable();
            $table->decimal('purchased_qty', 12, 3)->default(0);
            $table->decimal('already_returned_qty', 12, 3)->default(0);
            $table->decimal('return_qty', 12, 3)->default(0);
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('mrp', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('taxable_amount', 12, 2)->default(0);
            $table->decimal('gst_percent', 5, 2)->default(0);
            $table->decimal('cgst_amount', 12, 2)->default(0);
            $table->decimal('sgst_amount', 12, 2)->default(0);
            $table->decimal('igst_amount', 12, 2)->default(0);
            $table->decimal('gst_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('reason')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('deleted')->default(0);
            $table->timestamps();

            $table->index(['organization_id', 'purchase_return_id']);
            $table->index(['organization_id', 'purchase_id']);
            $table->index(['organization_id', 'product_variant_id']);
            $table->index(['organization_id', 'batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
    }
};
