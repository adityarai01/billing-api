<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_code', 50)->nullable()->after('organization_id');
            $table->string('designation', 100)->nullable()->after('employee_code');
            $table->string('department', 100)->nullable()->after('designation');
            $table->tinyInteger('employment_type')->nullable()
                ->comment('1=FullTime,2=PartTime,3=Contract,4=DailyWage,5=Intern')
                ->after('department');
            $table->date('joining_date')->nullable()->after('employment_type');
            $table->date('leaving_date')->nullable()->after('joining_date');
            $table->string('aadhar_no', 20)->nullable()->after('leaving_date');
            $table->string('pan_no', 20)->nullable()->after('aadhar_no');
            $table->string('bank_name', 100)->nullable()->after('pan_no');
            $table->string('account_holder_name', 150)->nullable()->after('bank_name');
            $table->string('account_no', 50)->nullable()->after('account_holder_name');
            $table->string('ifsc_code', 20)->nullable()->after('account_no');
            $table->tinyInteger('login_enabled')->default(1)
                ->comment('1=Yes,0=No')
                ->after('ifsc_code');

            $table->index(['organization_id', 'employee_code']);
            $table->index(['organization_id', 'department']);
            $table->index(['organization_id', 'employment_type']);
            $table->index(['organization_id', 'joining_date']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'employee_code', 'designation', 'department', 'employment_type',
                'joining_date', 'leaving_date', 'aadhar_no', 'pan_no',
                'bank_name', 'account_holder_name', 'account_no', 'ifsc_code', 'login_enabled',
            ]);
        });
    }
};
