<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('user_id');
            $table->date('attendance_date');
            $table->tinyInteger('status')
                ->comment('1=Present,2=Absent,3=HalfDay,4=Late,5=OnLeave,6=Holiday,7=WeeklyOff');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->decimal('working_hours', 5, 2)->default(0);
            $table->decimal('overtime_hours', 5, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->tinyInteger('deleted')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'user_id', 'attendance_date']);
            $table->index(['organization_id', 'user_id']);
            $table->index(['organization_id', 'attendance_date']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_attendance');
    }
};
