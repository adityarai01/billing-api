<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            if (!Schema::hasColumn('product_variants', 'base_unit_id')) {
                $table->unsignedBigInteger('base_unit_id')->nullable()->after('unit_id');
            }
            if (!Schema::hasColumn('product_variants', 'base_unit_name')) {
                $table->string('base_unit_name', 100)->nullable()->after('base_unit_id');
            }
            if (!Schema::hasColumn('product_variants', 'available_stock_base_qty')) {
                $table->decimal('available_stock_base_qty', 12, 3)->default(0)->after('stock_qty');
            }
            if (!Schema::hasColumn('product_variants', 'opening_stock_base_qty')) {
                $table->decimal('opening_stock_base_qty', 12, 3)->default(0)->after('available_stock_base_qty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['base_unit_id', 'base_unit_name', 'available_stock_base_qty', 'opening_stock_base_qty']);
        });
    }
};
