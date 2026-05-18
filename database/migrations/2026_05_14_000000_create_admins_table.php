<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);
            $table->string('email', 150)->nullable()->unique();
            $table->string('mobile_no', 20)->unique();
            $table->string('password');

            $table->string('profile_image', 255)->nullable();

            $table->dateTime('last_login_at')->nullable();
            $table->string('last_login_ip', 100)->nullable();

            $table->rememberToken();

            $table->tinyInteger('status')
                ->default(1)
                ->comment('1=Active,0=Inactive');

            $table->tinyInteger('deleted')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
