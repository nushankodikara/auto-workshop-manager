<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JobCardController;
use App\Http\Controllers\ClientVehicleController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\FinanceController;
use Illuminate\Http\Request;

// Guest Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetCode'])->name('password.email');
Route::get('/reset-password', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

// PWA Routes
Route::get('/manifest.json', function () {
    $hasCustomLogo = file_exists(public_path('images/logo.png'));
    $iconPath = $hasCustomLogo ? asset('images/logo.png') : asset('images/generic-icon.png');

    return response()->json([
        'name' => config('app.name', 'Auto Workshop Manager'),
        'short_name' => config('app.name', 'Workshop'),
        'start_url' => '/',
        'display' => 'standalone',
        'background_color' => '#0f172a',
        'theme_color' => '#3b82f6',
        'icons' => [
            [
                'src' => $iconPath,
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any maskable'
            ],
            [
                'src' => $iconPath,
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any maskable'
            ]
        ]
    ]);
});

Route::get('/offline', function () {
    return view('errors.offline');
})->name('offline');



// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::match(['get', 'post'], '/insights', [DashboardController::class, 'insights'])->name('dashboard.insights');
    Route::get('/statistics', [DashboardController::class, 'statistics'])->name('dashboard.statistics');

    // Job Cards Kanban & Allocation
    Route::get('/job-cards', [JobCardController::class, 'board'])->name('job-cards.board');
    Route::get('/job-cards/{jobCard}', [JobCardController::class, 'show'])->name('job-cards.show');
    Route::post('/job-cards', [JobCardController::class, 'store'])->name('job-cards.store');
    Route::put('/job-cards/{jobCard}', [JobCardController::class, 'update'])->name('job-cards.update');
    Route::patch('/job-cards/{jobCard}/status', [JobCardController::class, 'updateStatus'])->name('job-cards.update-status');
    Route::post('/job-cards/{jobCard}/comments', [JobCardController::class, 'addComment'])->name('job-cards.comment');
    Route::post('/job-cards/{jobCard}/workers', [JobCardController::class, 'assignWorkers'])->name('job-cards.workers');
    Route::post('/job-cards/{jobCard}/parts', [JobCardController::class, 'allocateParts'])->name('job-cards.allocate-parts');
    Route::post('/job-cards/{jobCard}/services', [JobCardController::class, 'addService'])->name('job-cards.add-service');
    Route::delete('/job-cards/services/{service}', [JobCardController::class, 'deleteService'])->name('job-cards.delete-service');
    Route::patch('/job-cards/allocated-parts/{stockMovement}', [JobCardController::class, 'updateAllocatedPart'])->name('job-cards.update-allocated-part');
    Route::delete('/job-cards/allocated-parts/{stockMovement}', [JobCardController::class, 'deallocateParts'])->name('job-cards.deallocate-parts');

    // Clients & Vehicles CRUD
    Route::get('/clients', [ClientVehicleController::class, 'clientsIndex'])->name('clients.index');
    Route::get('/clients/{client}', [ClientVehicleController::class, 'clientShow'])->name('clients.show');
    Route::post('/clients', [ClientVehicleController::class, 'clientStore'])->name('clients.store');
    Route::put('/clients/{client}', [ClientVehicleController::class, 'clientUpdate'])->name('clients.update');
    Route::delete('/clients/{client}', [ClientVehicleController::class, 'clientDestroy'])->name('clients.destroy');
    Route::get('/vehicles', [ClientVehicleController::class, 'vehiclesIndex'])->name('vehicles.index');
    Route::get('/vehicles/{vehicle}/history', [ClientVehicleController::class, 'vehicleHistory'])->name('vehicles.history');
    Route::post('/vehicles', [ClientVehicleController::class, 'vehicleStore'])->name('vehicles.store');
    Route::put('/vehicles/{vehicle}', [ClientVehicleController::class, 'vehicleUpdate'])->name('vehicles.update');
    Route::delete('/vehicles/{vehicle}', [ClientVehicleController::class, 'vehicleDestroy'])->name('vehicles.destroy');

    // Inventory Stock & Movements
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::get('/inventory/{item}', [InventoryController::class, 'show'])->name('inventory.show');
    Route::put('/inventory/{item}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::patch('/inventory/{item}/adjust', [InventoryController::class, 'adjustStock'])->name('inventory.adjust');
    Route::post('/inventory/{item}/batch', [InventoryController::class, 'addBatch'])->name('inventory.add-batch');
    Route::delete('/inventory/{item}', [InventoryController::class, 'destroy'])->name('inventory.destroy');

    // Billing & Invoices
    Route::get('/job-cards/{jobCard}/billing/workspace', [BillingController::class, 'showWorkspace'])->name('billing.workspace');
    Route::post('/job-cards/{jobCard}/billing', [BillingController::class, 'store'])->name('billing.store');
    Route::get('/job-cards/{jobCard}/billing/invoice', [BillingController::class, 'show'])->name('billing.show');
    Route::patch('/billing/{bill}/status', [BillingController::class, 'updateStatus'])->name('billing.update-status');

    // Payroll Salary Slips
    Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('/payroll/create/{user}', [PayrollController::class, 'createWorkspace'])->name('payroll.create');
    Route::post('/payroll', [PayrollController::class, 'store'])->name('payroll.store');
    Route::get('/payroll/{payrollSlip}', [PayrollController::class, 'show'])->name('payroll.show');
    Route::patch('/payroll/{payrollSlip}/status', [PayrollController::class, 'updateStatus'])->name('payroll.update-status');

    // Attendance Tracker
    Route::post('/payroll/attendance', [PayrollController::class, 'attendanceStore'])->name('payroll.attendance.store');
    Route::get('/payroll/attendance/user/{user}', [PayrollController::class, 'employeeAttendanceIndex'])->name('payroll.attendance.employee');
    Route::post('/payroll/attendance/user/{user}', [PayrollController::class, 'employeeAttendanceStore'])->name('payroll.attendance.employee.store');

    // Employee CRUD
    Route::post('/employees', [PayrollController::class, 'employeeStore'])->name('employees.store');
    Route::put('/employees/{user}', [PayrollController::class, 'employeeUpdate'])->name('employees.update');
    Route::delete('/employees/{user}', [PayrollController::class, 'employeeDestroy'])->name('employees.destroy');

    // Employee Profile Utilization View
    Route::get('/employees/{user}', [PayrollController::class, 'employeeShow'])->name('employees.show');

    // Employee Archive / Unarchive
    Route::post('/employees/{user}/archive', [PayrollController::class, 'employeeArchive'])->name('employees.archive');
    Route::post('/employees/{user}/unarchive', [PayrollController::class, 'employeeUnarchive'])->name('employees.unarchive');

    // Outsourcing CRUD (Super Manager Only)
    Route::get('/outsourcing', [App\Http\Controllers\OutsourcingController::class, 'index'])->name('outsourcing.index');
    Route::post('/outsourcing', [App\Http\Controllers\OutsourcingController::class, 'store'])->name('outsourcing.store');
    Route::put('/outsourcing/{company}', [App\Http\Controllers\OutsourcingController::class, 'update'])->name('outsourcing.update');
    Route::delete('/outsourcing/{company}', [App\Http\Controllers\OutsourcingController::class, 'destroy'])->name('outsourcing.destroy');

    // Predefined Services CRUD (Super Manager Only)
    Route::get('/predefined-services', [App\Http\Controllers\PredefinedServiceController::class, 'index'])->name('services.index');
    Route::post('/predefined-services', [App\Http\Controllers\PredefinedServiceController::class, 'store'])->name('services.store');
    Route::put('/predefined-services/{service}', [App\Http\Controllers\PredefinedServiceController::class, 'update'])->name('services.update');
    Route::delete('/predefined-services/{service}', [App\Http\Controllers\PredefinedServiceController::class, 'destroy'])->name('services.destroy');

    // Double-entry accounting ledger & finance (Super Manager Only)
    Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
    Route::post('/finance/accounts', [FinanceController::class, 'storeAccount'])->name('finance.accounts.store');
    Route::post('/finance/ledger', [FinanceController::class, 'storeJournalEntry'])->name('finance.ledger.store');
    Route::put('/finance/entries/{journalEntry}', [FinanceController::class, 'updateJournalEntry'])->name('finance.entries.update');
    Route::delete('/finance/entries/{journalEntry}', [FinanceController::class, 'destroyJournalEntry'])->name('finance.entries.destroy');
    Route::get('/finance/export/accounts', [FinanceController::class, 'exportAccountsCsv'])->name('finance.export.accounts');
    Route::get('/finance/export/ledger', [FinanceController::class, 'exportLedgerCsv'])->name('finance.export.ledger');
    Route::get('/finance/export/customers', [FinanceController::class, 'exportCustomerBooksCsv'])->name('finance.export.customers');
    Route::post('/finance/reconcile', [FinanceController::class, 'reconcile'])->name('finance.reconcile');

    // System Settings & Database Backups (Super Manager Only)
    Route::get('/settings', [App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/backup', [App\Http\Controllers\SettingsController::class, 'backup'])->name('settings.backup');
    Route::post('/settings/restore', [App\Http\Controllers\SettingsController::class, 'restore'])->name('settings.restore');
    Route::get('/settings/backup/download/{filename}', [App\Http\Controllers\SettingsController::class, 'downloadBackup'])->name('settings.backup.download');
    Route::post('/settings/backup/upload-restore', [App\Http\Controllers\SettingsController::class, 'uploadRestore'])->name('settings.backup.upload-restore');
    Route::post('/settings/logo', [App\Http\Controllers\SettingsController::class, 'uploadLogo'])->name('settings.logo');
    Route::delete('/settings/logo', [App\Http\Controllers\SettingsController::class, 'deleteLogo'])->name('settings.logo.delete');
    Route::post('/settings/update', [App\Http\Controllers\SettingsController::class, 'updateSettings'])->name('settings.update');
    Route::post('/settings/shops', [App\Http\Controllers\SettingsController::class, 'storeShop'])->name('settings.shops.store');
    Route::delete('/settings/shops/{shop}', [App\Http\Controllers\SettingsController::class, 'deleteShop'])->name('settings.shops.delete');
    Route::post('/settings/roles', [App\Http\Controllers\SettingsController::class, 'storeRole'])->name('settings.roles.store');
    Route::put('/settings/roles/{role}', [App\Http\Controllers\SettingsController::class, 'updateRole'])->name('settings.roles.update');
    Route::delete('/settings/roles/{role}', [App\Http\Controllers\SettingsController::class, 'destroyRole'])->name('settings.roles.destroy');


    // Customer Broadcast Messaging (Super Manager Only)
    Route::get('/broadcast', [BroadcastController::class, 'index'])->name('broadcast.index');
    Route::post('/broadcast/send', [BroadcastController::class, 'send'])->name('broadcast.send');

});

// Public API Route (Exempt from auth & CSRF)
Route::match(['get', 'post'], '/api/tickets/status', [App\Http\Controllers\ApiController::class, 'getTicketStatus'])->name('api.tickets.status');

