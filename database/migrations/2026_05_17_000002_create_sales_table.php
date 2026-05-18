<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('invoice_no', 50);
            $table->dateTime('invoice_date');
            $table->tinyInteger('invoice_type')->default(1)->comment('1=POS,2=TaxInvoice,3=Estimate,4=Proforma');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('item_discount_amount', 12, 2)->default(0);
            $table->tinyInteger('invoice_discount_type')->nullable()->comment('1=Percentage,2=Fixed');
            $table->decimal('invoice_discount_value', 12, 2)->default(0);
            $table->decimal('invoice_discount_amount', 12, 2)->default(0);
            $table->string('coupon_code', 50)->nullable();
            $table->decimal('coupon_discount_amount', 12, 2)->default(0);
            $table->decimal('promotion_discount_amount', 12, 2)->default(0);
            $table->decimal('total_discount_amount', 12, 2)->default(0);
            $table->decimal('taxable_amount', 12, 2)->default(0);
            $table->decimal('cgst_amount', 12, 2)->default(0);
            $table->decimal('sgst_amount', 12, 2)->default(0);
            $table->decimal('igst_amount', 12, 2)->default(0);
            $table->decimal('gst_amount', 12, 2)->default(0);
            $table->decimal('other_charges', 12, 2)->default(0);
            $table->decimal('round_off', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('due_amount', 12, 2)->default(0);
            $table->tinyInteger('payment_status')->default(1)->comment('1=Unpaid,2=Partial,3=Paid');
            $table->tinyInteger('sale_status')->default(1)->comment('1=Draft,2=Completed,3=Cancelled,4=Returned,5=PartiallyReturned');
            $table->tinyInteger('stock_status')->default(1)->comment('1=StockDeducted,2=NotDeducted,3=Reversed');
            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('deleted')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'invoice_no']);
            $table->index(['organization_id', 'customer_id']);
            $table->index(['organization_id', 'invoice_date']);
            $table->index(['organization_id', 'payment_status']);
            $table->index(['organization_id', 'sale_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
