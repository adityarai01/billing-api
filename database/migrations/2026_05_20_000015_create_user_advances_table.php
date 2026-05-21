<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_advances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('recovered_amount', 12, 2)->default(0);
            $table->date('advance_date');
            $table->tinyInteger('status')
                ->default(1)
                ->comment('1=Pending,2=Approved,3=Rejected,4=Recovered');
            $table->text('reason')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->tinyInteger('recover_from_salary')->default(1)->comment('1=Yes,0=No');
            $table->integer('recovery_months')->default(1)->comment('Number of months to recover');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->tinyInteger('deleted')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'user_id']);
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'advance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_advances');
    }
};
