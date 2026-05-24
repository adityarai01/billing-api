<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * All module × action combinations for the billing system.
     * ShopOwner/SuperAdmin bypass permission checks entirely.
     * These are seeded once globally (not per-org).
     */
    private const MODULES = [
        ['key' => 'dashboard',    'label' => 'Dashboard',       'actions' => ['view']],
        ['key' => 'pos_billing',  'label' => 'POS Billing',      'actions' => ['view', 'create', 'edit', 'delete', 'print']],
        ['key' => 'products',     'label' => 'Products',         'actions' => ['view', 'create', 'edit', 'delete', 'export']],
        ['key' => 'inventory',    'label' => 'Inventory',        'actions' => ['view', 'create', 'edit', 'delete', 'export', 'approve']],
        ['key' => 'purchase',     'label' => 'Purchase',         'actions' => ['view', 'create', 'edit', 'delete', 'export', 'print', 'approve']],
        ['key' => 'customers',    'label' => 'Customers',        'actions' => ['view', 'create', 'edit', 'delete', 'export']],
        ['key' => 'offers',       'label' => 'Offers & Coupons', 'actions' => ['view', 'create', 'edit', 'delete']],
        ['key' => 'accounts',     'label' => 'Accounts',         'actions' => ['view', 'create', 'edit', 'delete', 'export']],
        ['key' => 'reports',      'label' => 'Reports',          'actions' => ['view', 'export', 'print']],
        ['key' => 'staff',        'label' => 'Staff / HR',       'actions' => ['view', 'create', 'edit', 'delete']],
        ['key' => 'roles',        'label' => 'Roles & Permissions', 'actions' => ['view', 'create', 'edit', 'delete']],
        ['key' => 'settings',     'label' => 'Settings',         'actions' => ['view', 'edit']],
    ];

    private const ACTION_LABELS = [
        'view'    => 'View',
        'create'  => 'Create',
        'edit'    => 'Edit',
        'delete'  => 'Delete',
        'export'  => 'Export',
        'print'   => 'Print',
        'approve' => 'Approve',
    ];

    public function run(): void
    {
        $sort = 0;
        foreach (self::MODULES as $module) {
            foreach ($module['actions'] as $action) {
                $label = (self::ACTION_LABELS[$action] ?? ucfirst($action)) . ' ' . $module['label'];
                Permission::updateOrCreate(
                    ['module' => $module['key'], 'action' => $action],
                    [
                        'module_label' => $module['label'],
                        'display_name' => $label,
                        'sort_order'   => $sort++,
                    ]
                );
            }
        }
    }
}
