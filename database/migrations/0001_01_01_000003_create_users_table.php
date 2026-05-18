<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('organization_id')->nullable();

            $table->string('name', 150);
            $table->string('email', 150)->nullable();
            $table->string('mobile_no', 20);

            $table->string('password');

            $table->tinyInteger('user_type')
                ->comment('1=SuperAdmin,2=ShopOwner,3=Cashier,4=InventoryManager,5=Accountant,6=Staff');

            $table->unsignedBigInteger('role_id')->nullable();

            $table->string('profile_image', 255)->nullable();

            $table->tinyInteger('gender')
                ->nullable()
                ->comment('1=Male,2=Female,3=Other');

            $table->date('dob')->nullable();

            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('pincode', 10)->nullable();

            $table->dateTime('last_login_at')->nullable();
            $table->string('last_login_ip', 100)->nullable();

            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('mobile_verified_at')->nullable();

            $table->rememberToken();

            $table->tinyInteger('status')
                ->default(1)
                ->comment('1=Active,0=Inactive');

            $table->tinyInteger('deleted')->default(0);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->nullOnDelete();

            $table->unique(['organization_id', 'mobile_no']);
            $table->unique(['organization_id', 'email']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
