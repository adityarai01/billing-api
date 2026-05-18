<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('purchase_no', 50);
            $table->string('supplier_invoice_no', 100)->nullable();
            $table->date('purchase_date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->tinyInteger('discount_type')->nullable()->comment('1=Percentage,2=Fixed');
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
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
            $table->tinyInteger('purchase_status')->default(1)->comment('1=Draft,2=Completed,3=Cancelled');
            $table->text('remarks')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('deleted')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'purchase_no']);
            $table->index(['organization_id', 'supplier_id']);
            $table->index(['organization_id', 'purchase_date']);
            $table->index(['organization_id', 'payment_status']);
            $table->index(['organization_id', 'purchase_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
