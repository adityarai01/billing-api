<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_shifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('shift_id');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->tinyInteger('deleted')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'user_id']);
            $table->index(['organization_id', 'shift_id']);
            $table->index(['organization_id', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_shifts');
    }
};
