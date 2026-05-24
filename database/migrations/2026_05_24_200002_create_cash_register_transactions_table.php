<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_register_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('cash_register_id');
            $table->unsignedBigInteger('user_id');
            $table->tinyInteger('transaction_type')->comment('1=OpeningCash,2=CashSale,3=CashRefund,4=CashIn,5=CashOut,6=Expense,7=ClosingAdjustment,8=UPISale,9=CardSale,10=BankTransferSale,11=CreditSale');
            $table->tinyInteger('source_type')->nullable()->comment('1=Sale,2=SalesReturn,3=Expense,4=ManualCashIn,5=ManualCashOut,6=Opening,7=Closing');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->tinyInteger('payment_mode')->nullable()->comment('1=Cash,2=UPI,3=Card,4=BankTransfer,5=Cheque,6=Credit');
            $table->string('reason')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'cash_register_id'], 'crt_org_register_idx');
            $table->index(['organization_id', 'user_id'], 'crt_org_user_idx');
            $table->index(['organization_id', 'transaction_type'], 'crt_org_type_idx');
            $table->index(['organization_id', 'source_type', 'source_id'], 'crt_org_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_register_transactions');
    }
};
