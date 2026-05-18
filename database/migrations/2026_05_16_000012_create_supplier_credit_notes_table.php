<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_credit_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->string('supplier_credit_note_no', 50);
            $table->string('supplier_reference_no', 100)->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->date('credit_note_date');
            $table->tinyInteger('credit_note_type')->default(1)->comment('1=SchemeDiscount,2=RateDifference,3=Shortage,4=DamageClaim,5=ManualAdjustment');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('used_amount', 12, 2)->default(0);
            $table->decimal('balance_amount', 12, 2)->default(0);
            $table->tinyInteger('credit_status')->default(1)->comment('1=Open,2=PartiallyAdjusted,3=Adjusted,4=Cancelled');
            $table->text('reason')->nullable();
            $table->text('remarks')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('deleted')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'supplier_credit_note_no'], 'scn_org_no_unique');
            $table->index(['organization_id', 'supplier_id']);
            $table->index(['organization_id', 'purchase_id']);
            $table->index(['organization_id', 'credit_note_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_credit_notes');
    }
};
