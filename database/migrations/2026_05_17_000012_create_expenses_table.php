<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->index();
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->string('title', 150);
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('expense_date');
            $table->tinyInteger('payment_mode')->default(1)->comment('1=Cash,2=UPI,3=Card,4=Bank Transfer,5=Cheque,6=Credit');
            $table->string('reference_no', 100)->nullable();
            $table->text('remarks')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('deleted')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'expense_date']);
            $table->index(['organization_id', 'payment_mode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
