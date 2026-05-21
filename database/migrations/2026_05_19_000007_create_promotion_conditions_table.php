<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_conditions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->unsignedBigInteger('promotion_id')->nullable()->index();
            $table->tinyInteger('condition_type')->comment('1=MinBillAmount,2=MinQty,3=CustomerGroup,4=PaymentMode,5=FirstPurchase,6=DateRange,7=TimeRange,8=DayOfWeek');
            $table->string('operator', 20)->nullable()->comment('>=,<=,=,IN,BETWEEN');
            $table->text('condition_value')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'promotion_id']);
            $table->index(['organization_id', 'condition_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_conditions');
    }
};
