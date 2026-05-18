<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('organization_invoice_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->unique();
            $table->tinyInteger('invoice_template')->default(1);
            $table->tinyInteger('thermal_paper_size')->default(1);
            $table->boolean('print_after_sale')->default(false);
            $table->boolean('show_logo_on_invoice')->default(true);
            $table->boolean('show_gst_on_invoice')->default(true);
            $table->boolean('show_discount_on_invoice')->default(true);
            $table->boolean('show_hsn_on_invoice')->default(true);
            $table->boolean('show_batch_on_invoice')->default(false);
            $table->boolean('show_expiry_on_invoice')->default(false);
            $table->boolean('show_terms_on_invoice')->default(false);
            $table->boolean('show_signature_on_invoice')->default(false);
            $table->text('terms_conditions')->nullable();
            $table->text('invoice_footer_message')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_invoice_settings');
    }
};
