<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('user_id');
            $table->string('module', 50)->comment('e.g. sales, purchase, attendance');
            $table->string('action', 50)->comment('e.g. create, update, delete, login');
            $table->string('description', 255)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_type', 100)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('meta')->nullable()->comment('JSON extra data');
            $table->timestamp('logged_at')->useCurrent();

            $table->index(['organization_id', 'user_id']);
            $table->index(['organization_id', 'module']);
            $table->index(['organization_id', 'logged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_activity_logs');
    }
};
