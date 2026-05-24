<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_items', 'purchase_unit_id')) {
                $table->unsignedBigInteger('purchase_unit_id')->nullable()->after('barcode');
            }
            if (!Schema::hasColumn('purchase_items', 'purchase_unit_name')) {
                $table->string('purchase_unit_name', 100)->nullable()->after('purchase_unit_id');
            }
            if (!Schema::hasColumn('purchase_items', 'conversion_qty')) {
                $table->decimal('conversion_qty', 12, 3)->default(1)->after('purchase_unit_name');
            }
            if (!Schema::hasColumn('purchase_items', 'base_qty')) {
                $table->decimal('base_qty', 12, 3)->default(0)->after('conversion_qty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn(['purchase_unit_id', 'purchase_unit_name', 'conversion_qty', 'base_qty']);
        });
    }
};
