<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('held_bills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('hold_no', 50);
            $table->dateTime('hold_date');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=Held,2=ConvertedToSale,3=Cancelled');
            $table->tinyInteger('deleted')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'hold_no']);
            $table->index(['organization_id', 'customer_id']);
            $table->index(['organization_id', 'hold_date']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('held_bills');
    }
};
