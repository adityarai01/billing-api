<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->unsignedBigInteger('promotion_id')->nullable()->index();
            $table->tinyInteger('target_type')->comment('1=Product,2=ProductVariant,3=Category,4=Brand,5=Customer,6=PaymentMode');
            $table->unsignedBigInteger('target_id')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'promotion_id']);
            $table->index(['organization_id', 'target_type']);
            $table->index(['organization_id', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_targets');
    }
};
