<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_ledgers', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_ledgers', 'unit_id')) {
                $table->unsignedBigInteger('unit_id')->nullable()->after('batch_id');
            }
            if (!Schema::hasColumn('stock_ledgers', 'unit_name')) {
                $table->string('unit_name', 100)->nullable()->after('unit_id');
            }
            if (!Schema::hasColumn('stock_ledgers', 'conversion_qty')) {
                $table->decimal('conversion_qty', 12, 3)->default(1)->after('unit_name');
            }
            if (!Schema::hasColumn('stock_ledgers', 'base_in_qty')) {
                $table->decimal('base_in_qty', 12, 3)->default(0)->after('in_qty');
            }
            if (!Schema::hasColumn('stock_ledgers', 'base_out_qty')) {
                $table->decimal('base_out_qty', 12, 3)->default(0)->after('out_qty');
            }
            if (!Schema::hasColumn('stock_ledgers', 'base_balance_qty')) {
                $table->decimal('base_balance_qty', 12, 3)->default(0)->after('balance_qty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_ledgers', function (Blueprint $table) {
            $table->dropColumn(['unit_id', 'unit_name', 'conversion_qty', 'base_in_qty', 'base_out_qty', 'base_balance_qty']);
        });
    }
};
