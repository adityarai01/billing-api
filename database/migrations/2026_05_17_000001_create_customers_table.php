<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->string('name')->nullable();
            $table->string('mobile_no', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('gstin', 20)->nullable();
            $table->string('pan_no', 15)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('state_code', 10)->nullable();
            $table->string('pincode', 10)->nullable();
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->decimal('current_balance', 12, 2)->default(0);
            $table->tinyInteger('balance_type')->default(1)->comment('1=Receivable,2=Payable');
            $table->decimal('loyalty_points', 12, 2)->default(0);
            $table->tinyInteger('status')->default(1)->comment('1=Active,0=Inactive');
            $table->tinyInteger('deleted')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'name']);
            $table->index(['organization_id', 'mobile_no']);
            $table->index(['organization_id', 'gstin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
