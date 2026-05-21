<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->string('name', 100);
            $table->string('code', 20);
            $table->tinyInteger('component_type')
                ->comment('1=Earning,2=Deduction');
            $table->tinyInteger('calculation_type')
                ->default(1)
                ->comment('1=Fixed,2=PercentageOfBasic,3=PerDay');
            $table->decimal('default_value', 12, 2)->default(0);
            $table->tinyInteger('is_taxable')->default(0)->comment('1=Yes,0=No');
            $table->tinyInteger('is_mandatory')->default(0)->comment('1=Yes,0=No');
            $table->integer('sort_order')->default(0);
            $table->tinyInteger('status')->default(1)->comment('1=Active,0=Inactive');
            $table->tinyInteger('deleted')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'component_type']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_components');
    }
};
