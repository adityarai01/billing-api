<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sale_invoice_discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('sale_id');
            $table->tinyInteger('discount_source')->comment('1=ManualInvoiceDiscount,2=Coupon,3=BillOffer,4=CustomerOffer,5=PaymentModeOffer,6=Loyalty');
            $table->unsignedBigInteger('promotion_id')->nullable();
            $table->string('coupon_code', 50)->nullable();
            $table->tinyInteger('discount_type')->nullable()->comment('1=Percentage,2=Fixed');
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'sale_id']);
            $table->index(['organization_id', 'promotion_id']);
            $table->index(['organization_id', 'coupon_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_invoice_discounts');
    }
};
