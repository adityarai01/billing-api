<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('user_id')->nullable()->comment('null = org-wide/admin broadcast');
            $table->string('title');
            $table->text('message');
            $table->tinyInteger('notification_type');
            $table->tinyInteger('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->tinyInteger('priority')->default(2)->comment('1=Low,2=Medium,3=High,4=Critical');
            $table->string('action_url')->nullable();
            $table->tinyInteger('is_read')->default(0);
            $table->dateTime('read_at')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('deleted')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'user_id']);
            $table->index(['organization_id', 'notification_type']);
            $table->index(['organization_id', 'is_read']);
            $table->index(['organization_id', 'priority']);
            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
    }
};
