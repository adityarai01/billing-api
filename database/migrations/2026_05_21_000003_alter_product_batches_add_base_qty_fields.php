<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('product_batches', 'base_unit_id')) {
                $table->unsignedBigInteger('base_unit_id')->nullable()->after('supplier_id');
            }
            if (!Schema::hasColumn('product_batches', 'base_unit_name')) {
                $table->string('base_unit_name', 100)->nullable()->after('base_unit_id');
            }
            if (!Schema::hasColumn('product_batches', 'opening_qty_base')) {
                $table->decimal('opening_qty_base', 12, 3)->default(0)->after('opening_qty');
            }
            if (!Schema::hasColumn('product_batches', 'available_qty_base')) {
                $table->decimal('available_qty_base', 12, 3)->default(0)->after('available_qty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropColumn(['base_unit_id', 'base_unit_name', 'opening_qty_base', 'available_qty_base']);
        });
    }
};
