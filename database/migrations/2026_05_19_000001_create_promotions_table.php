<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->string('name');
            $table->string('code', 100)->nullable()->index();
            $table->text('description')->nullable();
            $table->tinyInteger('promotion_type')->default(1)->comment('1=ProductOffer,2=CategoryOffer,3=BrandOffer,4=Coupon,5=BuyXGetY,6=ComboOffer,7=FreeItem,8=BillOffer,9=CustomerOffer,10=PaymentModeOffer,11=Loyalty');
            $table->tinyInteger('discount_level')->default(1)->comment('1=ItemLevel,2=InvoiceLevel');
            $table->tinyInteger('apply_type')->default(1)->comment('1=AutoApply,2=ManualApply,3=CouponRequired');
            $table->tinyInteger('discount_type')->nullable()->comment('1=Percentage,2=Fixed,3=FreeItem,4=FixedComboPrice');
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('min_bill_amount', 12, 2)->default(0);
            $table->decimal('max_discount_amount', 12, 2)->nullable();
            $table->datetime('start_date')->nullable();
            $table->datetime('end_date')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('used_count')->default(0);
            $table->integer('per_customer_limit')->nullable();
            $table->integer('priority')->default(0);
            $table->tinyInteger('allow_multiple')->default(0)->comment('0=No,1=Yes');
            $table->tinyInteger('stackable')->default(0)->comment('0=No,1=Yes');
            $table->tinyInteger('auto_apply')->default(1)->comment('0=No,1=Yes');
            $table->tinyInteger('requires_coupon')->default(0)->comment('0=No,1=Yes');
            $table->tinyInteger('status')->default(1)->comment('1=Active,0=Inactive');
            $table->tinyInteger('deleted')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'promotion_type']);
            $table->index(['organization_id', 'discount_level']);
            $table->index(['organization_id', 'start_date']);
            $table->index(['organization_id', 'end_date']);
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
