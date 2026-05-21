<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_salary_structures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('gross_salary', 12, 2)->default(0);
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->tinyInteger('is_current')->default(1)->comment('1=Current,0=Historical');
            $table->text('remarks')->nullable();
            $table->tinyInteger('deleted')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'user_id']);
            $table->index(['organization_id', 'user_id', 'is_current']);
            $table->index(['organization_id', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_salary_structures');
    }
};
