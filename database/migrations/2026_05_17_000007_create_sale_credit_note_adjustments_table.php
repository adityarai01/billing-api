<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sale_credit_note_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('credit_note_id');
            $table->decimal('adjusted_amount', 12, 2)->default(0);
            $table->dateTime('adjustment_date');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'sale_id'], 'scna_org_sale_idx');
            $table->index(['organization_id', 'customer_id'], 'scna_org_customer_idx');
            $table->index(['organization_id', 'credit_note_id'], 'scna_org_cn_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_credit_note_adjustments');
    }
};
