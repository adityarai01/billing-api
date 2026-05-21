<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_salary_structure_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('salary_structure_id');
            $table->unsignedBigInteger('component_id');
            $table->tinyInteger('component_type')->comment('1=Earning,2=Deduction');
            $table->tinyInteger('calculation_type')->default(1)->comment('1=Fixed,2=PercentageOfBasic,3=PerDay');
            $table->decimal('value', 12, 2)->default(0);
            $table->decimal('calculated_amount', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['salary_structure_id']);
            $table->index(['organization_id', 'component_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_salary_structure_items');
    }
};
