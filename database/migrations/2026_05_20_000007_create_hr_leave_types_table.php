<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_leave_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->string('name', 100);
            $table->string('code', 10);
            $table->integer('allowed_days_per_year')->default(0);
            $table->tinyInteger('is_paid')->default(1)->comment('1=Paid,0=Unpaid');
            $table->tinyInteger('carry_forward')->default(0)->comment('1=Yes,0=No');
            $table->integer('max_carry_forward_days')->default(0);
            $table->tinyInteger('status')->default(1)->comment('1=Active,0=Inactive');
            $table->tinyInteger('deleted')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->unique(['organization_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_leave_types');
    }
};
