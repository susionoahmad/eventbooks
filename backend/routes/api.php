<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\RabController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\TaxController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\EventTaskController;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/register', [AuthController::class, 'register']);


    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth session
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::put('auth/password', [AuthController::class, 'changePassword']);

        // Master Data CRUDs
        Route::middleware('check.trial')->group(function () {
            Route::get('clients/next-code', [ClientController::class, 'getNextCode']);
            Route::get('vendors/next-code', [VendorController::class, 'getNextCode']);
            Route::apiResource('clients', ClientController::class);
            Route::apiResource('vendors', VendorController::class);
            Route::get('vendors/{vendor}/ktp', [VendorController::class, 'showKtp']);
            Route::get('vendors/{vendor}/npwp', [VendorController::class, 'showNpwp']);
            
            // Event CRUD
            Route::apiResource('events', EventController::class);

            // RAB Budgeting Sub-routes
            Route::get('events/{event}/rab', [RabController::class, 'index']);
            Route::post('events/{event}/rab', [RabController::class, 'store']);
            Route::put('events/{event}/rab/{rabItem}', [RabController::class, 'update']);
            Route::delete('events/{event}/rab/{rabItem}', [RabController::class, 'destroy']);

            // Event Documents routes
            Route::get('events/{event}/documents', [DocumentController::class, 'index']);
            Route::post('events/{event}/documents', [DocumentController::class, 'store']);
            Route::get('events/{event}/documents/{document}/download', [DocumentController::class, 'download']);
            Route::delete('events/{event}/documents/{document}', [DocumentController::class, 'destroy']);

            // Event Tasks routes
            Route::get('events/{event}/tasks', [EventTaskController::class, 'index']);
            Route::post('events/{event}/tasks', [EventTaskController::class, 'store']);
            Route::put('events/{event}/tasks/{task}', [EventTaskController::class, 'update']);
            Route::delete('events/{event}/tasks/{task}', [EventTaskController::class, 'destroy']);

            // Bookkeeping Ledger routes
            Route::apiResource('transactions', TransactionController::class);

            // Invoicing & milestone payments
            Route::get('invoices/next-code', [InvoiceController::class, 'getNextCode']);
            Route::apiResource('invoices', InvoiceController::class);
            Route::post('invoices/{invoice}/payments', [InvoiceController::class, 'recordPayment']);

            // Tax Obligations
            Route::get('taxes/alerts', [TaxController::class, 'alerts']);
            Route::get('taxes/calendar-events', [TaxController::class, 'calendarEvents']);
            Route::get('taxes', [TaxController::class, 'index']);
            Route::get('taxes/summary', [TaxController::class, 'summary']);
            Route::put('taxes/{tax}/status', [TaxController::class, 'updateStatus']);
            Route::post('taxes/{tax}/arsip', [TaxController::class, 'uploadArsip']);
            Route::delete('taxes/{tax}/arsip', [TaxController::class, 'deleteArsip']);

            // Dashboard Stats
            Route::get('dashboard/summary', [DashboardController::class, 'summary']);
            Route::get('dashboard/event-profitability', [DashboardController::class, 'eventProfitability']);
            Route::get('dashboard/cash-flow-by-method', [DashboardController::class, 'cashFlowByMethod']);

            // Global Search
            Route::get('search', [SearchController::class, 'globalSearch']);

            // Financial Reports
            Route::get('reports/profit-loss', [ReportController::class, 'profitLoss']);
            Route::get('reports/cash-flow', [ReportController::class, 'cashFlow']);
            Route::get('reports/ledger', [ReportController::class, 'ledger']);

            // Tenant & User Settings
            Route::get('tenant', [TenantController::class, 'show']);
            Route::put('tenant', [TenantController::class, 'update']);
            Route::post('tenant/setup', [TenantController::class, 'completeSetup']);
            Route::get('tenant/users', [TenantController::class, 'listUsers']);
            Route::post('tenant/users', [TenantController::class, 'inviteUser']);
            Route::put('tenant/users/{user}', [TenantController::class, 'updateUser']);
            Route::delete('tenant/users/{user}', [TenantController::class, 'destroyUser']);
            Route::put('tenant/users/{user}/toggle', [TenantController::class, 'toggleUserStatus']);
            Route::put('tenant/users/{user}/password', [TenantController::class, 'updateUserPassword']);
        });
    });
});


