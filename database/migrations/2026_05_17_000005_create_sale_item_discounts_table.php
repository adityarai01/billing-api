<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sale_item_discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('sale_item_id');
            $table->tinyInteger('discount_source')->comment('1=Manual,2=ProductOffer,3=CategoryOffer,4=BrandOffer,5=BuyXGetY,6=ComboOffer');
            $table->unsignedBigInteger('promotion_id')->nullable();
            $table->tinyInteger('discount_type')->nullable()->comment('1=Percentage,2=Fixed,3=FreeItem');
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'sale_id']);
            $table->index(['organization_id', 'sale_item_id']);
            $table->index(['organization_id', 'promotion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_item_discounts');
    }
};
