<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('payroll_id');
            $table->unsignedBigInteger('component_id');
            $table->string('component_name', 100);
            $table->tinyInteger('component_type')->comment('1=Earning,2=Deduction');
            $table->decimal('value', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['payroll_id']);
            $table->index(['organization_id', 'component_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
