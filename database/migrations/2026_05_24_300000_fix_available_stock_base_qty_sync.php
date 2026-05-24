<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Sync available_stock_base_qty = stock_qty for all non-batch variants where they differ
        DB::statement('UPDATE product_variants SET available_stock_base_qty = stock_qty WHERE available_stock_base_qty != stock_qty');
    }

    public function down(): void {}
};
