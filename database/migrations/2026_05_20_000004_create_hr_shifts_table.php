<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_shifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->string('name', 100);
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('grace_minutes')->default(0)->comment('Late arrival grace in minutes');
            $table->integer('working_hours')->default(8);
            $table->string('working_days', 20)->default('1,2,3,4,5,6')->comment('Comma-separated day numbers 1=Mon..7=Sun');
            $table->tinyInteger('status')->default(1)->comment('1=Active,0=Inactive');
            $table->tinyInteger('deleted')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_shifts');
    }
};
