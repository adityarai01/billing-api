<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('module', 100);        // e.g. pos_billing
            $table->string('module_label', 150);  // e.g. POS Billing
            $table->string('action', 50);         // view | create | edit | delete | export | print | approve
            $table->string('display_name', 150);  // e.g. View POS Billing
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['module', 'action']);
            $table->index('module');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
