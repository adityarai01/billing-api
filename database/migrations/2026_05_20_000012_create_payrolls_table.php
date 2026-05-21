<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('pay_year');
            $table->integer('pay_month')->comment('1-12');
            $table->integer('working_days')->default(0);
            $table->decimal('present_days', 5, 2)->default(0);
            $table->decimal('absent_days', 5, 2)->default(0);
            $table->decimal('half_days', 5, 2)->default(0);
            $table->decimal('paid_leave_days', 5, 2)->default(0);
            $table->decimal('unpaid_leave_days', 5, 2)->default(0);
            $table->decimal('overtime_hours', 5, 2)->default(0);
            $table->decimal('gross_salary', 12, 2)->default(0);
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('total_earnings', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('advance_deduction', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2)->default(0);
            $table->tinyInteger('status')->default(1)->comment('1=Draft,2=Generated,3=Approved,4=Paid');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->tinyInteger('deleted')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'user_id', 'pay_year', 'pay_month']);
            $table->index(['organization_id', 'user_id']);
            $table->index(['organization_id', 'pay_year', 'pay_month']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
