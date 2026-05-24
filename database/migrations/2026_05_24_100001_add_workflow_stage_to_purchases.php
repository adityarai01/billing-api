<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // 0=direct, 1=purchase_request, 2=purchase_order, 3=goods_received
            $table->tinyInteger('workflow_stage')->default(0)->after('purchase_status');
            $table->string('pr_no')->nullable()->after('workflow_stage');       // PR number
            $table->string('po_no')->nullable()->after('pr_no');                // PO number
            $table->integer('requested_by')->nullable()->after('po_no');
            $table->integer('approved_by')->nullable()->after('requested_by');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->timestamp('received_at')->nullable()->after('approved_at');
            $table->text('rejection_reason')->nullable()->after('received_at');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['workflow_stage', 'pr_no', 'po_no', 'requested_by', 'approved_by', 'approved_at', 'received_at', 'rejection_reason']);
        });
    }
};
