<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ProductBatch;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Supplier;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ApiResponseTrait;

    public function stats(Request $request): JsonResponse
    {
        $orgId     = $request->attributes->get('organization_id');
        $today     = today()->toDateString();
        $thisMonth = now()->month;
        $thisYear  = now()->year;

        // ── Today Stats ──────────────────────────────────────────────────────
        $todaySales = Sale::where('organization_id', $orgId)
            ->whereDate('invoice_date', $today)
            ->where('sale_status', '!=', 3)
            ->sum('grand_total');

        $todayProfit = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.organization_id', $orgId)
            ->whereDate('sales.invoice_date', $today)
            ->where('sales.sale_status', '!=', 3)
            ->where('sale_items.is_free_item', 0)
            ->sum('sale_items.profit_amount');

        $todayPurchase = Purchase::where('organization_id', $orgId)
            ->whereDate('purchase_date', $today)
            ->where('purchase_status', 2)
            ->sum('grand_total');

        $todayExpense = Expense::where('organization_id', $orgId)
            ->whereDate('expense_date', $today)
            ->where('deleted', 0)
            ->sum('amount');

        // ── Totals ───────────────────────────────────────────────────────────
        $dueAmount = Sale::where('organization_id', $orgId)
            ->where('due_amount', '>', 0)
            ->where('sale_status', '!=', 3)
            ->sum('due_amount');

        $totalCustomers = Customer::where('organization_id', $orgId)->where('deleted', 0)->count();
        $totalSuppliers = Supplier::where('organization_id', $orgId)->where('deleted', 0)->count();

        $lowStock = ProductVariant::where('organization_id', $orgId)
            ->where('deleted', 0)
            ->where('status', 1)
            ->where('low_stock_alert', '>', 0)
            ->whereColumn('stock_qty', '<=', 'low_stock_alert')
            ->count();

        $nearExpiry = ProductBatch::where('organization_id', $orgId)
            ->where('deleted', 0)
            ->where('available_qty', '>', 0)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '>=', $today)
            ->where('expiry_date', '<=', now()->addDays(90)->toDateString())
            ->count();

        $expiredProducts = ProductBatch::where('organization_id', $orgId)
            ->where('deleted', 0)
            ->where('available_qty', '>', 0)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', $today)
            ->count();

        $totalInvoices = Sale::where('organization_id', $orgId)
            ->whereMonth('invoice_date', $thisMonth)
            ->whereYear('invoice_date', $thisYear)
            ->where('sale_status', '!=', 3)
            ->count();

        $monthlyRevenue = Sale::where('organization_id', $orgId)
            ->whereMonth('invoice_date', $thisMonth)
            ->whereYear('invoice_date', $thisYear)
            ->where('sale_status', '!=', 3)
            ->sum('grand_total');

        // ── Recent Invoices ───────────────────────────────────────────────────
        $recentInvoices = Sale::where('organization_id', $orgId)
            ->where('sale_status', '!=', 3)
            ->with('customer:id,name')
            ->select('id', 'invoice_no', 'customer_id', 'grand_total', 'payment_status', 'sale_status', 'invoice_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn($s) => [
                'id'             => $s->id,
                'invoice_no'     => $s->invoice_no,
                'customer'       => $s->customer?->name ?? 'Walk-in',
                'amount'         => (float) $s->grand_total,
                'payment_status' => $s->payment_status,
                'sale_status'    => $s->sale_status,
                'date'           => $s->invoice_date?->toDateString(),
            ]);

        // ── Top Selling Products (this month) ─────────────────────────────────
        $topProducts = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.organization_id', $orgId)
            ->whereMonth('sales.invoice_date', $thisMonth)
            ->whereYear('sales.invoice_date', $thisYear)
            ->where('sales.sale_status', '!=', 3)
            ->where('sale_items.is_free_item', 0)
            ->select(
                'sale_items.product_id',
                'sale_items.product_name',
                DB::raw('SUM(sale_items.qty) as total_qty'),
                DB::raw('SUM(sale_items.total_amount) as total_revenue')
            )
            ->groupBy('sale_items.product_id', 'sale_items.product_name')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'id'      => $r->product_id,
                'name'    => $r->product_name,
                'sold'    => round((float) $r->total_qty, 2),
                'revenue' => (float) $r->total_revenue,
            ]);

        // ── Low Stock Alerts ──────────────────────────────────────────────────
        $lowStockAlerts = ProductVariant::where('organization_id', $orgId)
            ->where('deleted', 0)
            ->where('low_stock_alert', '>', 0)
            ->whereColumn('stock_qty', '<=', 'low_stock_alert')
            ->with('product:id,name')
            ->select('id', 'product_id', 'stock_qty', 'low_stock_alert')
            ->limit(10)
            ->get()
            ->map(fn($v) => [
                'id'        => $v->id,
                'name'      => $v->product?->name ?? 'Unknown',
                'stock'     => (float) $v->stock_qty,
                'min_stock' => (float) $v->low_stock_alert,
            ]);

        // ── Near Expiry Alerts ────────────────────────────────────────────────
        $nearExpiryAlerts = ProductBatch::where('organization_id', $orgId)
            ->where('deleted', 0)
            ->where('available_qty', '>', 0)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '>=', $today)
            ->where('expiry_date', '<=', now()->addDays(90)->toDateString())
            ->with('product:id,name')
            ->select('id', 'product_id', 'batch_no', 'expiry_date', 'available_qty')
            ->orderBy('expiry_date')
            ->limit(10)
            ->get()
            ->map(fn($b) => [
                'id'        => $b->id,
                'name'      => $b->product?->name ?? 'Unknown',
                'batch'     => $b->batch_no,
                'expiry'    => $b->expiry_date?->toDateString(),
                'days_left' => (int) now()->diffInDays($b->expiry_date),
                'qty'       => (float) $b->available_qty,
            ]);

        // ── Monthly Sales Chart (last 12 months) ──────────────────────────────
        $salesChart = [];
        for ($i = 11; $i >= 0; $i--) {
            $d  = now()->subMonths($i);
            $m  = $d->month;
            $y  = $d->year;

            $mSales = Sale::where('organization_id', $orgId)
                ->whereMonth('invoice_date', $m)->whereYear('invoice_date', $y)
                ->where('sale_status', '!=', 3)->sum('grand_total');

            $mPurchase = Purchase::where('organization_id', $orgId)
                ->whereMonth('purchase_date', $m)->whereYear('purchase_date', $y)
                ->where('purchase_status', 2)->sum('grand_total');

            $salesChart[] = [
                'month'    => $d->format('M Y'),
                'sales'    => (float) $mSales,
                'purchase' => (float) $mPurchase,
            ];
        }

        // ── Payment Mode Distribution (this month) ───────────────────────────
        $paymentModes = SalePayment::join('sales', 'sale_payments.sale_id', '=', 'sales.id')
            ->where('sales.organization_id', $orgId)
            ->whereMonth('sales.invoice_date', $thisMonth)
            ->whereYear('sales.invoice_date', $thisYear)
            ->where('sales.sale_status', '!=', 3)
            ->select('sale_payments.payment_mode', DB::raw('SUM(sale_payments.amount) as total'))
            ->groupBy('sale_payments.payment_mode')
            ->get()
            ->map(fn($p) => [
                'name'  => ucfirst($p->payment_mode ?? 'other'),
                'value' => (float) $p->total,
            ])
            ->values();

        return $this->successResponse([
            'today_sales'        => (float) $todaySales,
            'today_profit'       => (float) $todayProfit,
            'today_purchase'     => (float) $todayPurchase,
            'today_expense'      => (float) $todayExpense,
            'due_amount'         => (float) $dueAmount,
            'total_customers'    => $totalCustomers,
            'total_suppliers'    => $totalSuppliers,
            'low_stock'          => $lowStock,
            'near_expiry'        => $nearExpiry,
            'expired_products'   => $expiredProducts,
            'total_invoices'     => $totalInvoices,
            'monthly_revenue'    => (float) $monthlyRevenue,
            'recent_invoices'    => $recentInvoices,
            'top_products'       => $topProducts,
            'low_stock_alerts'   => $lowStockAlerts,
            'near_expiry_alerts' => $nearExpiryAlerts,
            'sales_chart'        => $salesChart,
            'payment_modes'      => $paymentModes,
        ], 'Dashboard stats');
    }
}
