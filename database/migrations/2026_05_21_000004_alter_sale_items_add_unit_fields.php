<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_items', 'sale_unit_id')) {
                $table->unsignedBigInteger('sale_unit_id')->nullable()->after('barcode');
            }
            if (!Schema::hasColumn('sale_items', 'sale_unit_name')) {
                $table->string('sale_unit_name', 100)->nullable()->after('sale_unit_id');
            }
            if (!Schema::hasColumn('sale_items', 'conversion_qty')) {
                $table->decimal('conversion_qty', 12, 3)->default(1)->after('sale_unit_name');
            }
            if (!Schema::hasColumn('sale_items', 'base_qty')) {
                $table->decimal('base_qty', 12, 3)->default(0)->after('conversion_qty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['sale_unit_id', 'sale_unit_name', 'conversion_qty', 'base_qty']);
        });
    }
};
