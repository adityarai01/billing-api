<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_ledgers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->dateTime('transaction_date');
            $table->tinyInteger('transaction_type')->comment('1=OpeningBalance,2=Sale,3=SalePayment,4=SalesReturn,5=CreditNote,6=CreditNoteAdjustment,7=ManualAdjustment');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->decimal('debit_amount', 12, 2)->default(0);
            $table->decimal('credit_amount', 12, 2)->default(0);
            $table->decimal('balance_amount', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'customer_id']);
            $table->index(['organization_id', 'transaction_date']);
            $table->index(['organization_id', 'transaction_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_ledgers');
    }
};
