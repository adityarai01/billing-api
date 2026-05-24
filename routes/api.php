<?php

use App\Http\Controllers\Webmaster\LoginController;
use App\Http\Controllers\Webmaster\ShopController;
use App\Http\Controllers\Webmaster\ShopkeeperController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\PurchasePaymentController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\StockAdjustmentController;
use App\Http\Controllers\Api\PurchaseReturnController;
use App\Http\Controllers\Api\DebitNoteController;
use App\Http\Controllers\Api\SupplierCreditNoteController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\PosController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\SaleInvoiceController;
use App\Http\Controllers\Api\SalePaymentController;
use App\Http\Controllers\Api\HeldBillController;
use App\Http\Controllers\Api\CustomerLedgerController;
use App\Http\Controllers\Api\ExpenseCategoryController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\SalesReportController;
use App\Http\Controllers\Api\SupplierLedgerController;
use App\Http\Controllers\Api\PromotionController;
use App\Http\Controllers\Api\PromotionCouponController;
use App\Http\Controllers\Api\PromotionUsageController;
use App\Http\Controllers\Api\PosPromotionController;
use App\Http\Controllers\Api\ProductVariantUnitController;
use App\Http\Controllers\Api\StaffUserController;
use App\Http\Controllers\Api\HrDepartmentController;
use App\Http\Controllers\Api\HrShiftController;
use App\Http\Controllers\Api\UserAttendanceController;
use App\Http\Controllers\Api\UserLeaveController;
use App\Http\Controllers\Api\SalaryComponentController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\SalaryPaymentController;
use App\Http\Controllers\Api\UserAdvanceController;
use App\Http\Controllers\Api\UserActivityLogController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\CashRegisterController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\NotificationSettingController;
use App\Http\Middleware\AuthorizeApiPermission;
use App\Http\Middleware\CheckApi;
use App\Http\Middleware\WebmasterAuth;
use Illuminate\Support\Facades\Route;

Route::prefix('webmaster')->name('webmaster.')->group(function () {

    // Public
    Route::post('login', [LoginController::class, 'login'])->name('login');

    // Protected
    Route::middleware(WebmasterAuth::class)->group(function () {
        Route::post('logout', [LoginController::class, 'logout'])->name('logout');
        Route::get('me', [LoginController::class, 'me'])->name('me');

        Route::apiResource('shops', ShopController::class);
        Route::apiResource('shopkeepers', ShopkeeperController::class);
    });

});



Route::prefix('user')->name('user.')->group(function () {

    // Public
    Route::post('login', [\App\Http\Controllers\Api\LoginController::class, 'login'])->name('login');

    // Protected
    Route::middleware([CheckApi::class, AuthorizeApiPermission::class])->group(function () {
        Route::post('logout', [\App\Http\Controllers\Api\LoginController::class, 'logout'])->name('logout');
        Route::post('me', [\App\Http\Controllers\Api\LoginController::class, 'me'])->name('me');
        Route::apiResource('categories', CategoryController::class)->except(['show']);
        Route::apiResource('brands', BrandController::class)->except(['show']);
        Route::apiResource('units', UnitController::class)->except(['show']);
        Route::get('products/meta', [ProductController::class, 'meta'])->name('products.meta');
        Route::post('products/upload-image', [ProductController::class, 'uploadImage'])->name('products.upload-image');
        Route::apiResource('products', ProductController::class);   

        // Product Variant Units
        Route::post('product-variant-units/create', [ProductVariantUnitController::class, 'create']);
        Route::post('product-variant-units/update', [ProductVariantUnitController::class, 'update']);
        Route::post('product-variant-units/delete', [ProductVariantUnitController::class, 'delete']);
        Route::get('product-variant-units/variant/{productVariantId}', [ProductVariantUnitController::class, 'listByVariant']);
        Route::post('product-variant-units/set-base-unit', [ProductVariantUnitController::class, 'setBaseUnit']);
        Route::post('product-variant-units/set-default-sale-unit', [ProductVariantUnitController::class, 'setDefaultSaleUnit']);
        Route::post('product-variant-units/set-default-purchase-unit', [ProductVariantUnitController::class, 'setDefaultPurchaseUnit']);
        Route::get('pos/product-variant/{productVariantId}/units', [ProductVariantUnitController::class, 'posVariantUnits']);
        Route::get('pos/product-variant/{productVariantId}/unit/{unitId}/price', [ProductVariantUnitController::class, 'posUnitPrice']);
        Route::get('pos/product-variant/{productVariantId}/unit/{unitId}/stock', [ProductVariantUnitController::class, 'posUnitStock']);

        // Suppliers
        Route::post('suppliers/create', [SupplierController::class, 'create']);
        Route::post('suppliers/update', [SupplierController::class, 'update']);
        Route::post('suppliers/delete', [SupplierController::class, 'delete']);
        Route::post('suppliers/search', [SupplierController::class, 'search']);
        Route::get('suppliers/details/{id}', [SupplierController::class, 'details']);
        Route::get('supplier-ledger/supplier/{supplier_id}', [SupplierLedgerController::class, 'supplierLedger']);

        // Purchases
        Route::post('purchases/create', [PurchaseController::class, 'create']);
        Route::post('purchases/update', [PurchaseController::class, 'update']);
        Route::post('purchases/search', [PurchaseController::class, 'search']);
        Route::get('purchases/details/{id}', [PurchaseController::class, 'details']);
        Route::post('purchases/cancel', [PurchaseController::class, 'cancel']);
        // Purchase Workflow (PR → PO → GRN)
        Route::post('purchases/request', [PurchaseController::class, 'createRequest']);
        Route::post('purchases/approve', [PurchaseController::class, 'approvePR']);
        Route::post('purchases/receive', [PurchaseController::class, 'receiveGoods']);
        Route::post('purchases/reject', [PurchaseController::class, 'rejectPR']);

        // Purchase Payments
        Route::post('purchase-payments/create', [PurchasePaymentController::class, 'create']);
        Route::post('purchase-payments/search', [PurchasePaymentController::class, 'search']);

        // Stock
        Route::post('stock-ledger/search', [StockController::class, 'ledgerSearch']);
        Route::post('stock/current-report', [StockController::class, 'currentReport']);
        Route::post('stock/batch-report', [StockController::class, 'batchReport']);
        Route::post('stock/low-stock-report', [StockController::class, 'lowStockReport']);
        Route::post('stock/near-expiry-report', [StockController::class, 'nearExpiryReport']);

        // Stock Adjustments
        Route::post('stock-adjustments/create', [StockAdjustmentController::class, 'create']);
        Route::post('stock-adjustments/approve', [StockAdjustmentController::class, 'approve']);
        Route::post('stock-adjustments/reject', [StockAdjustmentController::class, 'reject']);
        Route::post('stock-adjustments/search', [StockAdjustmentController::class, 'search']);
        Route::get('stock-adjustments/details/{id}', [StockAdjustmentController::class, 'details']);

        // Purchase Returns
        Route::post('purchase-returns/create', [PurchaseReturnController::class, 'create']);
        Route::post('purchase-returns/search', [PurchaseReturnController::class, 'search']);
        Route::get('purchase-returns/details/{id}', [PurchaseReturnController::class, 'details']);
        Route::post('purchase-returns/cancel', [PurchaseReturnController::class, 'cancel']);

        // Debit Notes
        Route::post('debit-notes/create', [DebitNoteController::class, 'create']);
        Route::post('debit-notes/search', [DebitNoteController::class, 'search']);
        Route::get('debit-notes/details/{id}', [DebitNoteController::class, 'details']);
        Route::post('debit-notes/adjust', [DebitNoteController::class, 'adjust']);

        // Supplier Credit Notes
        Route::post('supplier-credit-notes/create', [SupplierCreditNoteController::class, 'create']);
        Route::post('supplier-credit-notes/search', [SupplierCreditNoteController::class, 'search']);
        Route::get('supplier-credit-notes/details/{id}', [SupplierCreditNoteController::class, 'details']);
        Route::post('supplier-credit-notes/adjust', [SupplierCreditNoteController::class, 'adjust']);

        // Customers
        Route::post('customers/create', [CustomerController::class, 'create']);
        Route::post('customers/update', [CustomerController::class, 'update']);
        Route::post('customers/delete', [CustomerController::class, 'delete']);
        Route::post('customers/search', [CustomerController::class, 'search']);
        Route::get('customers/details/{id}', [CustomerController::class, 'details']);
        Route::post('customers/quick-search', [CustomerController::class, 'quickSearch']);

        // POS
        Route::post('pos/product-search', [PosController::class, 'productSearch']);
        Route::post('pos/calculate', [PosController::class, 'calculate']);
        Route::post('pos/validate-stock', [PosController::class, 'validateStock']);
        Route::post('pos/save-sale', [PosController::class, 'saveSale']);
        Route::post('pos/hold-bill', [PosController::class, 'holdBill']);
        Route::post('pos/recall-bill/{id}', [PosController::class, 'recallBill']);
        Route::post('pos/convert-held-bill/{id}', [PosController::class, 'convertHeldBill']);

        // Sales
        Route::post('sales/search', [SaleController::class, 'search']);
        Route::get('sales/details/{id}', [SaleController::class, 'details']);
        Route::post('sales/cancel', [SaleController::class, 'cancel']);
        Route::post('sales/process-return', [SaleController::class, 'processReturn']);
        Route::get('sales/invoice/{saleId}', [SaleInvoiceController::class, 'show']);
        Route::get('sales/invoice/{saleId}/preview', [SaleInvoiceController::class, 'preview']);
        Route::get('sales/invoice/{saleId}/thermal-80mm', [SaleInvoiceController::class, 'thermal80mm']);
        Route::get('sales/invoice/{saleId}/thermal-58mm', [SaleInvoiceController::class, 'thermal58mm']);
        Route::get('sales/invoice/{saleId}/a4-gst', [SaleInvoiceController::class, 'a4Gst']);
        Route::get('sales/invoice/{saleId}/simple', [SaleInvoiceController::class, 'simple']);
        Route::get('sales/invoice/{saleId}/pdf', [SaleInvoiceController::class, 'pdf']);
        Route::post('sales/invoice/{saleId}/generate-pdf', [SaleInvoiceController::class, 'generatePdf']);
        Route::post('sales/invoice/{saleId}/send-whatsapp', [SaleInvoiceController::class, 'sendWhatsapp']);
        Route::post('sales/invoice/{saleId}/send-email', [SaleInvoiceController::class, 'sendEmail']);
        Route::get('sales/thermal-invoice/{id}', [SaleController::class, 'thermalInvoice']);
        Route::get('sales/a4-invoice/{id}', [SaleController::class, 'a4Invoice']);
        Route::get('invoice-settings', [SaleInvoiceController::class, 'settings']);
        Route::post('invoice-settings/update', [SaleInvoiceController::class, 'updateSettings']);

        // Sale Payments
        Route::post('sale-payments/create', [SalePaymentController::class, 'create']);
        Route::post('sale-payments/search', [SalePaymentController::class, 'search']);

        // Held Bills
        Route::post('held-bills/search', [HeldBillController::class, 'search']);
        Route::get('held-bills/details/{id}', [HeldBillController::class, 'details']);
        Route::post('held-bills/cancel', [HeldBillController::class, 'cancel']);

        // Customer Ledger
        Route::post('customer-ledger/search', [CustomerLedgerController::class, 'search']);
        Route::get('customer-ledger/customer/{customer_id}', [CustomerLedgerController::class, 'customerLedger']);

        // Expenses
        Route::post('expense-categories/search', [ExpenseCategoryController::class, 'search']);
        Route::post('expense-categories/create', [ExpenseCategoryController::class, 'create']);
        Route::post('expense-categories/update', [ExpenseCategoryController::class, 'update']);
        Route::post('expense-categories/delete', [ExpenseCategoryController::class, 'delete']);
        Route::post('expenses/search', [ExpenseController::class, 'search']);
        Route::post('expenses/create', [ExpenseController::class, 'create']);
        Route::post('expenses/update', [ExpenseController::class, 'update']);
        Route::post('expenses/delete', [ExpenseController::class, 'delete']);

        // Promotions
        Route::post('promotions/create', [PromotionController::class, 'create']);
        Route::post('promotions/update', [PromotionController::class, 'update']);
        Route::post('promotions/delete', [PromotionController::class, 'delete']);
        Route::post('promotions/search', [PromotionController::class, 'search']);
        Route::get('promotions/details/{id}', [PromotionController::class, 'details']);
        Route::post('promotions/change-status', [PromotionController::class, 'changeStatus']);
        Route::get('promotions/active', [PromotionController::class, 'active']);
        // Promotion targets
        Route::post('promotions/add-target', [PromotionController::class, 'addTarget']);
        Route::post('promotions/remove-target', [PromotionController::class, 'removeTarget']);
        // Buy-Get rules
        Route::post('promotions/add-rule', [PromotionController::class, 'addBuyGetRule']);
        Route::post('promotions/update-rule', [PromotionController::class, 'updateBuyGetRule']);
        Route::post('promotions/remove-rule', [PromotionController::class, 'removeBuyGetRule']);
        // Free items
        Route::post('promotions/add-free-item', [PromotionController::class, 'addFreeItem']);
        Route::post('promotions/remove-free-item', [PromotionController::class, 'removeFreeItem']);
        // Combo items
        Route::post('promotions/add-combo-item', [PromotionController::class, 'addComboItem']);
        Route::post('promotions/remove-combo-item', [PromotionController::class, 'removeComboItem']);

        // Coupons
        Route::post('coupons/create', [PromotionCouponController::class, 'create']);
        Route::post('coupons/bulk-create', [PromotionCouponController::class, 'bulkCreate']);
        Route::post('coupons/update', [PromotionCouponController::class, 'update']);
        Route::post('coupons/delete', [PromotionCouponController::class, 'delete']);
        Route::post('coupons/search', [PromotionCouponController::class, 'search']);
        Route::get('coupons/details/{id}', [PromotionCouponController::class, 'details']);
        Route::post('coupons/validate', [PromotionCouponController::class, 'validate']);

        // POS Promotions
        Route::post('pos/promotions/calculate', [PosPromotionController::class, 'calculate']);
        Route::post('pos/promotions/apply-coupon', [PosPromotionController::class, 'applyCoupon']);
        Route::post('pos/promotions/remove-coupon', [PosPromotionController::class, 'removeCoupon']);
        Route::post('pos/promotions/free-item-options', [PosPromotionController::class, 'freeItemOptions']);
        Route::post('pos/promotions/select-free-item', [PosPromotionController::class, 'selectFreeItem']);

        // Promotion Usage History
        Route::post('promotion-usages/search', [PromotionUsageController::class, 'search']);
        Route::post('promotion-usages/summary', [PromotionUsageController::class, 'summary']);

        // Dashboard
        Route::get('dashboard/stats', [DashboardController::class, 'stats']);

        // HR - Dashboard
        Route::get('hr/dashboard-stats', [StaffUserController::class, 'dashboardStats']);

        // HR - Staff Users
        Route::post('hr/staff/search', [StaffUserController::class, 'search']);
        Route::get('hr/staff/details/{id}', [StaffUserController::class, 'details']);
        Route::post('hr/staff/create', [StaffUserController::class, 'create']);
        Route::post('hr/staff/update', [StaffUserController::class, 'update']);
        Route::post('hr/staff/delete', [StaffUserController::class, 'delete']);
        Route::post('hr/staff/change-status', [StaffUserController::class, 'changeStatus']);

        // HR - Departments & Designations
        Route::post('hr/departments/search', [HrDepartmentController::class, 'searchDepartments']);
        Route::post('hr/departments/create', [HrDepartmentController::class, 'createDepartment']);
        Route::post('hr/departments/update', [HrDepartmentController::class, 'updateDepartment']);
        Route::post('hr/departments/delete', [HrDepartmentController::class, 'deleteDepartment']);
        Route::post('hr/designations/search', [HrDepartmentController::class, 'searchDesignations']);
        Route::post('hr/designations/create', [HrDepartmentController::class, 'createDesignation']);
        Route::post('hr/designations/update', [HrDepartmentController::class, 'updateDesignation']);
        Route::post('hr/designations/delete', [HrDepartmentController::class, 'deleteDesignation']);

        // HR - Shifts
        Route::post('hr/shifts/search', [HrShiftController::class, 'search']);
        Route::post('hr/shifts/create', [HrShiftController::class, 'create']);
        Route::post('hr/shifts/update', [HrShiftController::class, 'update']);
        Route::post('hr/shifts/delete', [HrShiftController::class, 'delete']);
        Route::post('hr/shifts/assign', [HrShiftController::class, 'assignShift']);
        Route::post('hr/shifts/user-shifts', [HrShiftController::class, 'getUserShifts']);

        // HR - Attendance
        Route::post('hr/attendance/search', [UserAttendanceController::class, 'search']);
        Route::post('hr/attendance/mark', [UserAttendanceController::class, 'mark']);
        Route::post('hr/attendance/bulk-mark', [UserAttendanceController::class, 'bulkMark']);
        Route::post('hr/attendance/monthly-summary', [UserAttendanceController::class, 'monthlySummary']);
        Route::post('hr/attendance/delete', [UserAttendanceController::class, 'delete']);

        // HR - Leaves
        Route::post('hr/leave-types/search', [UserLeaveController::class, 'searchLeaveTypes']);
        Route::post('hr/leave-types/create', [UserLeaveController::class, 'createLeaveType']);
        Route::post('hr/leave-types/update', [UserLeaveController::class, 'updateLeaveType']);
        Route::post('hr/leave-types/delete', [UserLeaveController::class, 'deleteLeaveType']);
        Route::post('hr/leaves/search', [UserLeaveController::class, 'searchLeaves']);
        Route::post('hr/leaves/apply', [UserLeaveController::class, 'applyLeave']);
        Route::post('hr/leaves/update-status', [UserLeaveController::class, 'updateLeaveStatus']);
        Route::post('hr/leaves/delete', [UserLeaveController::class, 'deleteLeave']);

        // HR - Salary Components & Structures
        Route::post('hr/salary-components/search', [SalaryComponentController::class, 'search']);
        Route::post('hr/salary-components/create', [SalaryComponentController::class, 'create']);
        Route::post('hr/salary-components/update', [SalaryComponentController::class, 'update']);
        Route::post('hr/salary-components/delete', [SalaryComponentController::class, 'delete']);
        Route::get('hr/salary-structure/{userId}', [SalaryComponentController::class, 'getUserSalary']);
        Route::post('hr/salary-structure/save', [SalaryComponentController::class, 'saveUserSalary']);

        // HR - Payroll
        Route::post('hr/payroll/search', [PayrollController::class, 'search']);
        Route::post('hr/payroll/generate', [PayrollController::class, 'generate']);
        Route::get('hr/payroll/details/{id}', [PayrollController::class, 'details']);
        Route::post('hr/payroll/change-status', [PayrollController::class, 'changeStatus']);

        // HR - Salary Payments
        Route::post('hr/salary-payments/search', [SalaryPaymentController::class, 'search']);
        Route::post('hr/salary-payments/record', [SalaryPaymentController::class, 'record']);
        Route::post('hr/salary-payments/delete', [SalaryPaymentController::class, 'delete']);

        // HR - Advances
        Route::post('hr/advances/search', [UserAdvanceController::class, 'search']);
        Route::post('hr/advances/create', [UserAdvanceController::class, 'create']);
        Route::post('hr/advances/update-status', [UserAdvanceController::class, 'updateStatus']);
        Route::post('hr/advances/delete', [UserAdvanceController::class, 'delete']);

        // HR - Activity Logs
        Route::post('hr/activity-logs/search', [UserActivityLogController::class, 'search']);

        // Reports
        Route::post('reports/sales', [SalesReportController::class, 'sales']);
        Route::post('reports/daily-summary', [SalesReportController::class, 'dailySummary']);
        Route::post('reports/sales-item', [SalesReportController::class, 'salesItem']);
        Route::post('reports/payment-mode', [SalesReportController::class, 'paymentMode']);
        Route::post('reports/customer-due', [SalesReportController::class, 'customerDue']);
        Route::post('reports/profit', [SalesReportController::class, 'profit']);
        Route::post('reports/discount', [SalesReportController::class, 'discount']);

        // Cash Register
        Route::post('cash-register/open', [CashRegisterController::class, 'open']);
        Route::post('cash-register/close', [CashRegisterController::class, 'close']);
        Route::get('cash-register/current', [CashRegisterController::class, 'current']);
        Route::post('cash-register/search', [CashRegisterController::class, 'search']);
        Route::get('cash-register/details/{id}', [CashRegisterController::class, 'details']);
        Route::post('cash-register/cash-in', [CashRegisterController::class, 'cashIn']);
        Route::post('cash-register/cash-out', [CashRegisterController::class, 'cashOut']);

        // Notifications
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::get('notifications/unread', [NotificationController::class, 'unread']);
        Route::post('notifications/search', [NotificationController::class, 'search']);
        Route::post('notifications/mark-read', [NotificationController::class, 'markRead']);
        Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
        Route::post('notifications/delete', [NotificationController::class, 'delete']);

        // Notification Settings
        Route::get('notification-settings', [NotificationSettingController::class, 'index']);
        Route::post('notification-settings/update', [NotificationSettingController::class, 'update']);

        // Roles & Permissions
        Route::post('roles/search', [RoleController::class, 'search']);
        Route::post('roles/create', [RoleController::class, 'create']);
        Route::post('roles/update', [RoleController::class, 'update']);
        Route::post('roles/delete', [RoleController::class, 'delete']);
        Route::get('roles/{roleId}/permissions', [RoleController::class, 'getPermissions']);
        Route::post('roles/permissions/save', [RoleController::class, 'savePermissions']);
        Route::get('permissions/all', [RoleController::class, 'allPermissions']);

    });
});
