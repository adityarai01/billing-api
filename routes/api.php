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
    Route::middleware(CheckApi::class)->group(function () {
        Route::post('logout', [\App\Http\Controllers\Api\LoginController::class, 'logout'])->name('logout');
        Route::post('me', [\App\Http\Controllers\Api\LoginController::class, 'me'])->name('me');
        Route::apiResource('categories', CategoryController::class)->except(['show']);
        Route::apiResource('brands', BrandController::class)->except(['show']);
        Route::apiResource('units', UnitController::class)->except(['show']);
        Route::get('products/meta', [ProductController::class, 'meta'])->name('products.meta');
        Route::post('products/upload-image', [ProductController::class, 'uploadImage'])->name('products.upload-image');
        Route::apiResource('products', ProductController::class);

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

        // Reports
        Route::post('reports/sales', [SalesReportController::class, 'sales']);
        Route::post('reports/sales-item', [SalesReportController::class, 'salesItem']);
        Route::post('reports/payment-mode', [SalesReportController::class, 'paymentMode']);
        Route::post('reports/customer-due', [SalesReportController::class, 'customerDue']);
        Route::post('reports/profit', [SalesReportController::class, 'profit']);
        Route::post('reports/discount', [SalesReportController::class, 'discount']);

    });
});
