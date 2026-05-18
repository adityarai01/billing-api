<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->string('adjustment_no', 50);
            $table->date('adjustment_date');
            $table->tinyInteger('adjustment_type')->comment('1=Increase,2=Decrease');
            $table->tinyInteger('reason_type')->comment('1=Damage,2=Lost,3=Expired,4=Correction,5=OpeningStock,6=Other');
            $table->text('remarks')->nullable();
            $table->tinyInteger('approval_status')->default(1)->comment('1=Pending,2=Approved,3=Rejected');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('deleted')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'adjustment_no']);
            $table->index(['organization_id', 'adjustment_date']);
            $table->index(['organization_id', 'adjustment_type']);
            $table->index(['organization_id', 'approval_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
