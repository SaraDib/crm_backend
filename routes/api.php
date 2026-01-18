<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// WhatsApp Instance Webhook (no auth required)
Route::post('/whatsapp/status-update', [\App\Http\Controllers\Api\WhatsAppController::class, 'statusUpdate']);

// Protected Routes
Route::middleware(['auth:sanctum', 'company.context'])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    
    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::post('/notifications/mark-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{id}/mark-read', [\App\Http\Controllers\Api\NotificationController::class, 'markRead']);
    
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('customer-categories', \App\Http\Controllers\Api\CustomerCategoryController::class);
    Route::get('customers-export', [\App\Http\Controllers\Api\CustomerExportController::class, 'exportCsv']);

    Route::apiResource('interactions', \App\Http\Controllers\Api\InteractionController::class);
    Route::apiResource('documents', \App\Http\Controllers\Api\DocumentController::class);
    Route::apiResource('custom-fields', \App\Http\Controllers\Api\CustomFieldController::class);
    Route::apiResource('users', \App\Http\Controllers\Api\CompanyUserController::class);
    Route::get('roles', [\App\Http\Controllers\Api\RoleController::class, 'index']);
    
    Route::get('suppliers/all', [\App\Http\Controllers\Api\SupplierController::class, 'listAll']);
    Route::apiResource('suppliers', \App\Http\Controllers\Api\SupplierController::class);
    Route::apiResource('supplier-invoices', \App\Http\Controllers\Api\SupplierInvoiceController::class);
    
    // Billing
    Route::post('quotes/{quote}/convert', [\App\Http\Controllers\Api\QuoteController::class, 'convertToInvoice']);
    Route::get('quotes/{quote}/pdf', [\App\Http\Controllers\Api\QuoteController::class, 'downloadPdf']);
    Route::apiResource('quotes', \App\Http\Controllers\Api\QuoteController::class);
    Route::get('invoices/{invoice}/pdf', [\App\Http\Controllers\Api\InvoiceController::class, 'downloadPdf']);
    Route::post('invoices/{invoice}/credit-note', [\App\Http\Controllers\Api\InvoiceController::class, 'storeCreditNote']);
    Route::apiResource('invoices', \App\Http\Controllers\Api\InvoiceController::class);
    Route::apiResource('payments', \App\Http\Controllers\Api\PaymentController::class);
    
    Route::get('/dashboard/stats', [\App\Http\Controllers\Api\Company\DashboardController::class, 'stats']);
    
    Route::apiResource('products', \App\Http\Controllers\Api\ProductController::class);
    
    // Chat
    Route::get('/chat/contacts', [\App\Http\Controllers\ChatMessageController::class, 'getContacts']);
    Route::get('/chat/recent', [\App\Http\Controllers\ChatMessageController::class, 'getChats']);
    Route::post('/chat/mark-read', [\App\Http\Controllers\ChatMessageController::class, 'markAsRead']);
    Route::apiResource('chat', \App\Http\Controllers\ChatMessageController::class)->only(['index', 'store']);

    // Tickets (Company side)
    Route::post('tickets/{ticket}/reply', [\App\Http\Controllers\Api\SupportTicketController::class, 'reply']);
    Route::apiResource('tickets', \App\Http\Controllers\Api\SupportTicketController::class);
    
    Route::get('/subscription/current', [\App\Http\Controllers\Api\SubscriptionController::class, 'current']);
    Route::get('/subscription/plans', [\App\Http\Controllers\Api\SubscriptionController::class, 'availablePlans']);

    // WhatsApp Management (Company-specific)
    Route::prefix('whatsapp')->group(function () {
        Route::get('/status', [\App\Http\Controllers\Api\WhatsAppController::class, 'status']);
        Route::post('/start', [\App\Http\Controllers\Api\WhatsAppController::class, 'start']);
        Route::post('/stop', [\App\Http\Controllers\Api\WhatsAppController::class, 'stop']);
        Route::post('/restart', [\App\Http\Controllers\Api\WhatsAppController::class, 'restart']);
        Route::post('/send-document', [\App\Http\Controllers\Api\WhatsAppController::class, 'sendDocument']);
    });

    // Company Settings
    Route::get('/company-settings', [\App\Http\Controllers\Api\CompanySettingsController::class, 'show']);
    Route::post('/company-settings', [\App\Http\Controllers\Api\CompanySettingsController::class, 'update']);

    // Admin Specific Routes (Super Admin)
    Route::prefix('admin')->group(function () {
        Route::prefix('dashboard')->group(function () {
            Route::get('/stats', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'stats']);
            Route::get('/sales/stats', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'salesStats']);
        });
        
        Route::apiResource('/companies', \App\Http\Controllers\Api\Admin\CompanyController::class);
        Route::patch('/companies/{company}/toggle-status', [\App\Http\Controllers\Api\Admin\CompanyController::class, 'toggleStatus']);
        Route::post('/companies/{company}/assign-plan', [\App\Http\Controllers\Api\Admin\CompanyController::class, 'assignPlan']);
        
        // Company Users Management (Admin)
        Route::get('/companies/{company}/users', [\App\Http\Controllers\Api\Admin\CompanyUserManagementController::class, 'index']);
        Route::post('/companies/{company}/users', [\App\Http\Controllers\Api\Admin\CompanyUserManagementController::class, 'store']);
        Route::put('/companies/{company}/users/{user}', [\App\Http\Controllers\Api\Admin\CompanyUserManagementController::class, 'update']);
        Route::delete('/companies/{company}/users/{user}', [\App\Http\Controllers\Api\Admin\CompanyUserManagementController::class, 'destroy']);
        
        Route::apiResource('/plans', \App\Http\Controllers\Api\Admin\SubscriptionPlanController::class);
        
        Route::apiResource('/system-users', \App\Http\Controllers\Api\Admin\SystemUserController::class);
        
        Route::get('/settings', [\App\Http\Controllers\Api\Admin\SystemSettingController::class, 'index']);
        Route::post('/settings/bulk', [\App\Http\Controllers\Api\Admin\SystemSettingController::class, 'updateBulk']);

        Route::get('/permissions-grouped', [\App\Http\Controllers\Api\Admin\RoleController::class, 'permissions']);
        Route::apiResource('/permissions', \App\Http\Controllers\Api\Admin\PermissionController::class);
        Route::apiResource('/roles', \App\Http\Controllers\Api\Admin\RoleController::class);

        Route::get('/invoices', [\App\Http\Controllers\Api\Admin\SubscriptionInvoiceController::class, 'index']);
        Route::get('/invoices/{invoice}', [\App\Http\Controllers\Api\Admin\SubscriptionInvoiceController::class, 'show']);
        Route::get('invoices/{invoice}/pdf', [\App\Http\Controllers\Api\Admin\SubscriptionInvoiceController::class, 'downloadPdf']);
        Route::patch('/invoices/{invoice}/status', [\App\Http\Controllers\Api\Admin\SubscriptionInvoiceController::class, 'updateStatus']);
        Route::post('/invoices/{invoice}/credit-note', [\App\Http\Controllers\Api\Admin\SubscriptionManagementController::class, 'storeCreditNote']);

        Route::apiResource('/tickets', \App\Http\Controllers\Api\Admin\SupportTicketController::class);
        Route::post('/tickets/{ticket}/reply', [\App\Http\Controllers\Api\Admin\SupportTicketController::class, 'reply']);
        
        // Subscription Management
        Route::post('/subscriptions/create', [\App\Http\Controllers\Api\Admin\SubscriptionManagementController::class, 'createForCompany']);
        Route::get('/subscriptions/pending', [\App\Http\Controllers\Api\Admin\SubscriptionManagementController::class, 'pending']);
        Route::get('/subscriptions/statistics', [\App\Http\Controllers\Api\Admin\SubscriptionManagementController::class, 'statistics']);
        Route::post('/subscriptions/{id}/validate-payment', [\App\Http\Controllers\Api\Admin\SubscriptionManagementController::class, 'validatePayment']);
        Route::get('/subscriptions', [\App\Http\Controllers\Api\Admin\SubscriptionManagementController::class, 'allSubscriptions']);
    });
});
