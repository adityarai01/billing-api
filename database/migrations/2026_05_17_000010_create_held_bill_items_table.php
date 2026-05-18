<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('held_bill_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('held_bill_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_variant_id');
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->string('product_name')->nullable();
            $table->string('variant_name')->nullable();
            $table->string('batch_no', 100)->nullable();
            $table->decimal('qty', 12, 3)->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('gst_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['organization_id', 'held_bill_id']);
            $table->index(['organization_id', 'product_variant_id']);
            $table->index(['organization_id', 'batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('held_bill_items');
    }
};
