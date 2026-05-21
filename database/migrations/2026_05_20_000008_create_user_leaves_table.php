<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_leaves', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('leave_type_id');
            $table->date('from_date');
            $table->date('to_date');
            $table->decimal('total_days', 5, 1)->default(1);
            $table->tinyInteger('status')
                ->default(1)
                ->comment('1=Pending,2=Approved,3=Rejected,4=Cancelled');
            $table->text('reason')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->tinyInteger('deleted')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'user_id']);
            $table->index(['organization_id', 'leave_type_id']);
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'from_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_leaves');
    }
};
