<?php
namespace App\Services;

use App\Enums\SaleStatusEnum;
use App\Enums\UserType;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SalesReportCacheService
{
    public function clearSalesCache(int $organizationId): void
    {
        Cache::forget("org:{$organizationId}:sales:list");
        Cache::forget("org:{$organizationId}:sales:daily-summary");
        Cache::forget("org:{$organizationId}:sales:payment-mode-summary");
    }

    public function clearCustomerCache(int $organizationId): void
    {
        Cache::forget("org:{$organizationId}:customers:list");
    }

    public function rememberSalesList(int $organizationId, array $filters, callable $callback): mixed
    {
        $key = "org:{$organizationId}:sales:list:" . md5(json_encode($filters));
        return Cache::remember($key, 300, $callback);
    }

    public function rememberSaleDetails(int $organizationId, int $saleId, callable $callback): mixed
    {
        $key = "org:{$organizationId}:sale:{$saleId}:details";
        return Cache::remember($key, 600, $callback);
    }

    public function rememberDailySalesSummary(int $organizationId, array $filters, callable $callback): mixed
    {
        $key = "org:{$organizationId}:sales:daily-summary:" . md5(json_encode($filters));
        return Cache::remember($key, 300, $callback);
    }

    public function rememberPaymentModeSummary(int $organizationId, array $filters, callable $callback): mixed
    {
        $key = "org:{$organizationId}:sales:payment-mode-summary:" . md5(json_encode($filters));
        return Cache::remember($key, 300, $callback);
    }

    public function getSalesReport(int $organizationId, array $filters): array
    {
        $query = Sale::where('organization_id', $organizationId)
            ->where('deleted', 0)
            ->with(['customer:id,name', 'creator:id,name']);

        if (!empty($filters['date_from']))      $query->whereDate('invoice_date', '>=', $filters['date_from']);
        if (!empty($filters['date_to']))        $query->whereDate('invoice_date', '<=', $filters['date_to']);
        if (!empty($filters['customer_id']))    $query->where('customer_id', $filters['customer_id']);
        if (!empty($filters['payment_status'])) $query->where('payment_status', $filters['payment_status']);
        if (!empty($filters['sale_status']))    $query->where('sale_status', $filters['sale_status']);
        if (!empty($filters['created_by']))     $query->where('created_by', $filters['created_by']);

        $perPage = $filters['per_page'] ?? 20;
        return $query->orderByDesc('invoice_date')->paginate($perPage)->toArray();
    }

    public function getDailySummary(int $organizationId, array $filters): array
    {
        $date = !empty($filters['date']) ? (string) $filters['date'] : now()->toDateString();
        $selectedCashierId = !empty($filters['created_by']) ? (int) $filters['created_by'] : null;

        return $this->rememberDailySalesSummary($organizationId, [
            'date' => $date,
            'created_by' => $selectedCashierId,
        ], function () use ($organizationId, $date, $selectedCashierId) {
            $baseQuery = $this->dailySalesBaseQuery($organizationId, $date);
            $filteredQuery = clone $baseQuery;

            if ($selectedCashierId !== null) {
                $filteredQuery->where('sales.created_by', $selectedCashierId);
            }

            $summary = $this->buildSummaryPayload(clone $filteredQuery);
            $cashiers = $this->buildCashierBreakdown(clone $baseQuery);
            $cashierOptions = $this->buildCashierOptions($organizationId, $cashiers);
            $selectedCashierName = $this->resolveSelectedCashierName($selectedCashierId, $cashiers, $cashierOptions);
            $invoices = $this->buildInvoiceRows(clone $filteredQuery);

            $summary['cashier_count'] = count($cashiers);

            return [
                'date' => $date,
                'selected_cashier_id' => $selectedCashierId,
                'selected_cashier_name' => $selectedCashierName,
                'summary' => $summary,
                'cashier_options' => $cashierOptions,
                'cashiers' => $cashiers,
                'invoices' => $invoices,
            ];
        });
    }

    public function getSalesItemReport(int $organizationId, array $filters): array
    {
        $query = SaleItem::where('organization_id', $organizationId)->where('deleted', 0)
            ->with(['sale', 'product', 'productVariant']);

        if (!empty($filters['product_id']))         $query->where('product_id', $filters['product_id']);
        if (!empty($filters['product_variant_id']))  $query->where('product_variant_id', $filters['product_variant_id']);
        if (!empty($filters['date_from']))           $query->whereHas('sale', fn($q) => $q->whereDate('invoice_date', '>=', $filters['date_from']));
        if (!empty($filters['date_to']))             $query->whereHas('sale', fn($q) => $q->whereDate('invoice_date', '<=', $filters['date_to']));

        $perPage = $filters['per_page'] ?? 20;
        return $query->orderByDesc('id')->paginate($perPage)->toArray();
    }

    public function getPaymentModeReport(int $organizationId, array $filters): array
    {
        $query = SalePayment::where('organization_id', $organizationId)->where('deleted', 0);
        if (!empty($filters['date_from'])) $query->whereDate('payment_date', '>=', $filters['date_from']);
        if (!empty($filters['date_to']))   $query->whereDate('payment_date', '<=', $filters['date_to']);

        return $query->selectRaw('payment_mode, SUM(amount) as total_amount, COUNT(*) as count')
            ->groupBy('payment_mode')->get()->toArray();
    }

    public function getCustomerDueReport(int $organizationId, array $filters): array
    {
        $query = Customer::where('organization_id', $organizationId)
            ->where('deleted', 0)
            ->where('current_balance', '>', 0)
            ->where('balance_type', 1);

        $perPage = $filters['per_page'] ?? 20;
        return $query->orderByDesc('current_balance')->paginate($perPage)->toArray();
    }

    public function getProfitReport(int $organizationId, array $filters): array
    {
        $query = SaleItem::where('organization_id', $organizationId)->where('deleted', 0)->where('is_free_item', 0)->with(['sale', 'product', 'productVariant']);
        if (!empty($filters['date_from'])) $query->whereHas('sale', fn($q) => $q->whereDate('invoice_date', '>=', $filters['date_from']));
        if (!empty($filters['date_to']))   $query->whereHas('sale', fn($q) => $q->whereDate('invoice_date', '<=', $filters['date_to']));

        $perPage = $filters['per_page'] ?? 20;
        return $query->orderByDesc('id')->paginate($perPage)->toArray();
    }

    public function getDiscountReport(int $organizationId, array $filters): array
    {
        $query = \App\Models\SaleItemDiscount::where('organization_id', $organizationId)->with(['sale', 'saleItem.product']);
        if (!empty($filters['date_from'])) $query->whereHas('sale', fn($q) => $q->whereDate('invoice_date', '>=', $filters['date_from']));
        if (!empty($filters['date_to']))   $query->whereHas('sale', fn($q) => $q->whereDate('invoice_date', '<=', $filters['date_to']));

        $perPage = $filters['per_page'] ?? 20;
        return $query->orderByDesc('id')->paginate($perPage)->toArray();
    }

    private function dailySalesBaseQuery(int $organizationId, string $date): Builder
    {
        return Sale::query()
            ->where('sales.organization_id', $organizationId)
            ->where('sales.deleted', 0)
            ->whereDate('sales.invoice_date', $date)
            ->whereIn('sales.sale_status', [
                SaleStatusEnum::Completed->value,
                SaleStatusEnum::Returned->value,
                SaleStatusEnum::PartiallyReturned->value,
            ]);
    }

    private function buildSummaryPayload(Builder $query): array
    {
        $totals = $query
            ->selectRaw('COUNT(*) as invoice_count')
            ->selectRaw('COUNT(DISTINCT customer_id) as unique_customers')
            ->selectRaw('COALESCE(SUM(grand_total), 0) as total_sales')
            ->selectRaw('COALESCE(SUM(total_discount_amount), 0) as total_discount')
            ->selectRaw('COALESCE(SUM(gst_amount), 0) as total_tax')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as total_paid')
            ->selectRaw('COALESCE(SUM(due_amount), 0) as total_due')
            ->first();

        $invoiceCount = (int) ($totals->invoice_count ?? 0);
        $totalSales = (float) ($totals->total_sales ?? 0);

        return [
            'invoice_count' => $invoiceCount,
            'unique_customers' => (int) ($totals->unique_customers ?? 0),
            'total_sales' => $totalSales,
            'total_discount' => (float) ($totals->total_discount ?? 0),
            'total_tax' => (float) ($totals->total_tax ?? 0),
            'total_paid' => (float) ($totals->total_paid ?? 0),
            'total_due' => (float) ($totals->total_due ?? 0),
            'avg_invoice_value' => $invoiceCount > 0 ? round($totalSales / $invoiceCount, 2) : 0.0,
        ];
    }

    private function buildCashierBreakdown(Builder $query): array
    {
        return $query
            ->leftJoin('users', 'users.id', '=', 'sales.created_by')
            ->groupBy('sales.created_by', 'users.name')
            ->orderByDesc(DB::raw('SUM(sales.grand_total)'))
            ->get([
                DB::raw('sales.created_by as id'),
                DB::raw("COALESCE(users.name, CASE WHEN sales.created_by IS NULL THEN 'Unknown' ELSE CONCAT('User #', sales.created_by) END) as name"),
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('COALESCE(SUM(sales.grand_total), 0) as total_sales'),
                DB::raw('COALESCE(SUM(sales.paid_amount), 0) as total_paid'),
                DB::raw('COALESCE(SUM(sales.due_amount), 0) as total_due'),
                DB::raw('COALESCE(AVG(sales.grand_total), 0) as avg_invoice_value'),
            ])
            ->map(fn($row) => [
                'id' => $row->id !== null ? (int) $row->id : null,
                'name' => (string) $row->name,
                'invoice_count' => (int) $row->invoice_count,
                'total_sales' => (float) $row->total_sales,
                'total_paid' => (float) $row->total_paid,
                'total_due' => (float) $row->total_due,
                'avg_invoice_value' => (float) $row->avg_invoice_value,
            ])
            ->values()
            ->all();
    }

    private function buildCashierOptions(int $organizationId, array $cashiers): array
    {
        $options = User::query()
            ->where('organization_id', $organizationId)
            ->where('deleted', 0)
            ->where('user_type', UserType::Cashier->value)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->mapWithKeys(fn($user) => [
                (int) $user->id => [
                    'id' => (int) $user->id,
                    'name' => (string) $user->name,
                ],
            ]);

        foreach ($cashiers as $cashier) {
            if ($cashier['id'] === null) {
                continue;
            }

            $options->put((int) $cashier['id'], [
                'id' => (int) $cashier['id'],
                'name' => (string) $cashier['name'],
            ]);
        }

        return $options
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    private function resolveSelectedCashierName(?int $selectedCashierId, array $cashiers, array $cashierOptions): ?string
    {
        if ($selectedCashierId === null) {
            return null;
        }

        foreach ($cashiers as $cashier) {
            if ((int) ($cashier['id'] ?? 0) === $selectedCashierId) {
                return (string) $cashier['name'];
            }
        }

        foreach ($cashierOptions as $cashierOption) {
            if ((int) ($cashierOption['id'] ?? 0) === $selectedCashierId) {
                return (string) $cashierOption['name'];
            }
        }

        return null;
    }

    private function buildInvoiceRows(Builder $query): array
    {
        return $query
            ->with(['customer:id,name', 'creator:id,name'])
            ->orderByDesc('sales.invoice_date')
            ->orderByDesc('sales.id')
            ->get([
                'sales.id',
                'sales.invoice_no',
                'sales.invoice_date',
                'sales.customer_id',
                'sales.created_by',
                'sales.payment_status',
                'sales.sale_status',
                'sales.grand_total',
                'sales.paid_amount',
                'sales.due_amount',
                'sales.total_discount_amount',
                'sales.gst_amount',
            ])
            ->map(fn($sale) => [
                'id' => (int) $sale->id,
                'invoice_no' => (string) $sale->invoice_no,
                'invoice_date' => $sale->invoice_date?->toDateTimeString(),
                'customer_name' => $sale->customer?->name ?: 'Walk-in',
                'cashier_id' => $sale->created_by !== null ? (int) $sale->created_by : null,
                'cashier_name' => $sale->creator?->name ?: ($sale->created_by ? 'User #' . $sale->created_by : 'Unknown'),
                'payment_status' => (int) $sale->payment_status,
                'sale_status' => (int) $sale->sale_status,
                'grand_total' => (float) $sale->grand_total,
                'paid_amount' => (float) $sale->paid_amount,
                'due_amount' => (float) $sale->due_amount,
                'total_discount_amount' => (float) $sale->total_discount_amount,
                'gst_amount' => (float) $sale->gst_amount,
            ])
            ->values()
            ->all();
    }
}
