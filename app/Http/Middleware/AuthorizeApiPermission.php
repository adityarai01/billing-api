<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeApiPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $permissions = (array) $request->attributes->get('permissions', []);

        if (in_array('*', $permissions, true)) {
            return $next($request);
        }

        $abilities = $this->resolveAbilities($request);

        if ($abilities === [] || array_intersect($abilities, $permissions) !== []) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Forbidden. You do not have permission to perform this action.',
        ], 403);
    }

    private function resolveAbilities(Request $request): array
    {
        $uri = trim((string) ($request->route()?->uri() ?? $request->path()), '/');

        if (str_starts_with($uri, 'api/')) {
            $uri = substr($uri, 4);
        }

        if (!str_starts_with($uri, 'user/')) {
            return [];
        }

        $uri = substr($uri, 5);

        if ($uri === 'logout' || $uri === 'me') {
            return [];
        }

        if ($uri === 'dashboard/stats') {
            return ['dashboard.view'];
        }

        if ($uri === 'invoice-settings' || str_starts_with($uri, 'invoice-settings/')) {
            return ['settings.' . ($request->isMethod('get') ? 'view' : 'edit')];
        }

        if ($uri === 'notification-settings' || str_starts_with($uri, 'notification-settings/')) {
            return ['settings.' . ($request->isMethod('get') ? 'view' : 'edit')];
        }

        if (str_starts_with($uri, 'notifications/')) {
            return ['settings.' . ($this->isReadOnly($uri, $request) ? 'view' : 'edit')];
        }

        if ($uri === 'permissions/all' || str_starts_with($uri, 'roles/')) {
            return ['roles.' . $this->resolveAction($uri, $request, 'roles')];
        }

        if (str_starts_with($uri, 'hr/')) {
            return ['staff.' . $this->resolveAction($uri, $request, 'staff')];
        }

        if ($uri === 'products/upload-image') {
            return ['products.create', 'products.edit'];
        }

        if ($uri === 'customers/quick-search') {
            return ['customers.view', 'pos_billing.view', 'pos_billing.create'];
        }

        if (str_starts_with($uri, 'pos/promotions/')) {
            return ['pos_billing.create', 'pos_billing.edit'];
        }

        if ($this->containsAny($uri, ['pos/save-sale', 'pos/hold-bill', 'pos/convert-held-bill/'])) {
            return ['pos_billing.create', 'pos_billing.edit'];
        }

        $module = $this->resolveModule($uri);

        if ($module === null) {
            return [];
        }

        return [$module . '.' . $this->resolveAction($uri, $request, $module)];
    }

    private function resolveModule(string $uri): ?string
    {
        foreach ([
            'products' => ['categories', 'brands', 'units', 'products', 'product-variant-units'],
            'inventory' => ['stock-ledger', 'stock/', 'stock-adjustments'],
            'purchase' => ['suppliers', 'purchases', 'purchase-payments', 'purchase-returns', 'debit-notes', 'supplier-credit-notes'],
            'customers' => ['customers'],
            'pos_billing' => ['pos/', 'sales', 'sale-payments', 'held-bills', 'cash-register'],
            'offers' => ['promotions', 'coupons', 'promotion-usages'],
            'accounts' => ['customer-ledger', 'supplier-ledger', 'expense-categories', 'expenses'],
            'reports' => ['reports/'],
        ] as $module => $prefixes) {
            foreach ($prefixes as $prefix) {
                if ($uri === rtrim($prefix, '/') || str_starts_with($uri, $prefix)) {
                    return $module;
                }
            }
        }

        if (str_starts_with($uri, 'pos/promotions/')) {
            return 'pos_billing';
        }

        return null;
    }

    private function resolveAction(string $uri, Request $request, string $module): string
    {
        if ($module === 'dashboard' || $module === 'reports') {
            return 'view';
        }

        if ($module === 'settings') {
            return $this->isReadOnly($uri, $request) ? 'view' : 'edit';
        }

        if ($module === 'roles' && $uri === 'roles/permissions/save') {
            return 'edit';
        }

        if ($this->containsAny($uri, ['generate-pdf', '/pdf', '/thermal', '/a4-', 'send-whatsapp', 'send-email'])) {
            return 'print';
        }

        if ($this->containsAny($uri, ['approve', 'reject', 'receive'])) {
            return in_array($module, ['inventory', 'purchase'], true) ? 'approve' : 'edit';
        }

        if ($this->containsAny($uri, ['delete', 'remove'])) {
            return $module === 'settings' ? 'edit' : 'delete';
        }

        if ($this->containsAny($uri, ['create', 'request', 'open', 'bulk-create', 'hold-bill'])) {
            return 'create';
        }

        if ($this->containsAny($uri, [
            'update',
            'change-status',
            'process-return',
            'cancel',
            'adjust',
            'record',
            'apply',
            'mark',
            'bulk-mark',
            'assign',
            'save',
            'set-base-unit',
            'set-default',
            'convert-held-bill',
            'cash-in',
            'cash-out',
            'close',
            'generate',
        ])) {
            return 'edit';
        }

        if ($request->isMethod('post') && $this->containsAny($uri, ['search', 'summary', 'calculate', 'validate'])) {
            return 'view';
        }

        return $this->isReadOnly($uri, $request) ? 'view' : 'edit';
    }

    private function isReadOnly(string $uri, Request $request): bool
    {
        if ($request->isMethod('get')) {
            return true;
        }

        return $this->containsAny($uri, [
            'search',
            'details',
            'meta',
            'current-report',
            'batch-report',
            'low-stock-report',
            'near-expiry-report',
            'monthly-summary',
            'summary',
            'current',
            'unread-count',
            'unread',
            'validate',
            'product-search',
            'calculate',
            'variant/',
            'user-shifts',
        ]);
    }

    private function containsAny(string $uri, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($uri, $needle)) {
                return true;
            }
        }

        return false;
    }
}
