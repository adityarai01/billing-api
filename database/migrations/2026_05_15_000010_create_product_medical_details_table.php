<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_medical_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->unique();
            $table->string('generic_name')->nullable();
            $table->text('salt_composition')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('medicine_type', 50)->nullable();
            $table->string('dosage_form', 50)->nullable();
            $table->tinyInteger('prescription_required')->default(0);
            $table->text('storage_instruction')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('deleted')->default(0);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_medical_details');
    }
};
