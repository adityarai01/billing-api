<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->tinyInteger('payment_mode')->comment('1=Cash,2=UPI,3=Card,4=BankTransfer,5=Cheque,6=Credit,7=CreditNote');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('reference_no', 100)->nullable();
            $table->dateTime('payment_date');
            $table->text('remarks')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('deleted')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'sale_id']);
            $table->index(['organization_id', 'customer_id']);
            $table->index(['organization_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};
