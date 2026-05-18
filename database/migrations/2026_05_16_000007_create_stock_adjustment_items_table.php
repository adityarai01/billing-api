<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('stock_adjustment_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_variant_id');
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->string('product_name', 255)->nullable();
            $table->string('variant_name', 255)->nullable();
            $table->string('batch_no', 100)->nullable();
            $table->decimal('old_qty', 12, 3)->default(0);
            $table->decimal('adjustment_qty', 12, 3)->default(0);
            $table->decimal('new_qty', 12, 3)->default(0);
            $table->decimal('rate', 12, 2)->default(0);
            $table->decimal('stock_value', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'stock_adjustment_id']);
            $table->index(['organization_id', 'product_id']);
            $table->index(['organization_id', 'product_variant_id']);
            $table->index(['organization_id', 'batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
    }
};
