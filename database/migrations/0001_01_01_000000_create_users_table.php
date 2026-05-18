<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();

            $table->string('shop_name', 150);
            $table->string('business_name', 150)->nullable();
            $table->string('owner_name', 150);

            $table->tinyInteger('shop_type')
                ->comment('1=Medical Store,2=Cloth Store,3=Grocery Store,4=General Retail Store');

            $table->string('mobile_no', 20);
            $table->string('alternate_mobile_no', 20)->nullable();
            $table->string('email', 150)->nullable();

            $table->string('gstin', 20)->nullable();
            $table->string('pan_no', 20)->nullable();

            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('state_code', 10)->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('country', 100)->default('India');

            $table->string('logo', 255)->nullable();
            $table->string('signature', 255)->nullable();

            $table->string('invoice_prefix', 20)->default('INV');
            $table->unsignedBigInteger('invoice_start_no')->default(1);

            $table->string('currency', 10)->default('INR');
            $table->string('timezone', 100)->default('Asia/Kolkata');

            $table->tinyInteger('subscription_status')
                ->default(1)
                ->comment('1=Trial,2=Active,3=Expired,4=Suspended');

            $table->date('trial_start_date')->nullable();
            $table->date('trial_end_date')->nullable();

            $table->tinyInteger('status')
                ->default(1)
                ->comment('1=Active,0=Inactive');

            $table->tinyInteger('deleted')->default(0);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
