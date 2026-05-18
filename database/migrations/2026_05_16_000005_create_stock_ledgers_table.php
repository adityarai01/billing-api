<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_ledgers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_variant_id');
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->tinyInteger('transaction_type')->comment('1=OpeningStock,2=Purchase,3=Sale,4=SaleReturn,5=PurchaseReturn,6=StockAdjustment,7=Damage,8=Expired');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->decimal('in_qty', 12, 3)->default(0);
            $table->decimal('out_qty', 12, 3)->default(0);
            $table->decimal('balance_qty', 12, 3)->default(0);
            $table->decimal('rate', 12, 2)->default(0);
            $table->decimal('stock_value', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'product_id']);
            $table->index(['organization_id', 'product_variant_id']);
            $table->index(['organization_id', 'batch_id']);
            $table->index(['organization_id', 'transaction_type']);
            $table->index(['organization_id', 'reference_id']);
            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ledgers');
    }
};
