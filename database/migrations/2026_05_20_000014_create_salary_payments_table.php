<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('payroll_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('pay_year');
            $table->integer('pay_month');
            $table->decimal('amount', 12, 2)->default(0);
            $table->tinyInteger('payment_mode')
                ->comment('1=Cash,2=BankTransfer,3=UPI,4=Cheque');
            $table->string('reference_no', 100)->nullable();
            $table->date('payment_date');
            $table->text('remarks')->nullable();
            $table->tinyInteger('deleted')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'user_id']);
            $table->index(['organization_id', 'payroll_id']);
            $table->index(['organization_id', 'payment_date']);
            $table->index(['organization_id', 'pay_year', 'pay_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
    }
};
