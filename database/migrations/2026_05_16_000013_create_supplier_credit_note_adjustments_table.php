<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_credit_note_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('supplier_credit_note_id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->unsignedBigInteger('purchase_payment_id')->nullable();
            $table->date('adjustment_date');
            $table->decimal('adjusted_amount', 12, 2)->default(0);
            $table->tinyInteger('adjustment_type')->default(1)->comment('1=AgainstPurchase,2=DueAdjustment,3=CashReceived');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'supplier_credit_note_id'], 'scna_org_scn_id_index');
            $table->index(['organization_id', 'supplier_id'], 'scna_org_supplier_id_index');
            $table->index(['organization_id', 'purchase_id'], 'scna_org_purchase_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_credit_note_adjustments');
    }
};
